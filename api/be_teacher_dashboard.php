<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

$user_role = strtoupper($_SESSION['role'] ?? '');
if (!isset($_SESSION['user_id']) || ($user_role !== 'TEACHER' && $user_role !== 'ADMIN')) {
    header('Location: login.php');
    exit;
}

$teacher_id = (int)($_SESSION['user_id'] ?? 0);
$teacher_name = trim((string)($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Giáo viên'));
if ($teacher_name === '') {
    $teacher_name = 'Giáo viên';
}

// 1. XỬ LÝ XÓA ĐỀ THI
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['exam_id'])) {
    $delete_id = (int)$_GET['exam_id'];

    try {
        $stmt_check = $conn->prepare("SELECT exam_id FROM exam WHERE exam_id = :exam_id AND user_id = :user_id");
        $stmt_check->execute(['exam_id' => $delete_id, 'user_id' => $teacher_id]);

        if ($stmt_check->fetch()) {
            $conn->beginTransaction();

            $stmt_del_eq = $conn->prepare("DELETE FROM exam_questions WHERE exam_id = :exam_id");
            $stmt_del_eq->execute(['exam_id' => $delete_id]);

            $stmt_del_results = $conn->prepare("DELETE FROM exam_results WHERE exam_id = :exam_id");
            $stmt_del_results->execute(['exam_id' => $delete_id]);

            $stmt_del_exam = $conn->prepare("DELETE FROM exam WHERE exam_id = :exam_id AND user_id = :user_id");
            $stmt_del_exam->execute(['exam_id' => $delete_id, 'user_id' => $teacher_id]);

            $conn->commit();

            $_SESSION['flash_message'] = "Đã xóa thành công đề thi #{$delete_id} và toàn bộ dữ liệu liên quan!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Bạn không có quyền xóa đề thi này!";
            $_SESSION['flash_type'] = "error";
        }
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['flash_message'] = "Lỗi khi xóa đề thi: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// 2. XỬ LÝ SỬA ĐỀ THI & CẬP NHẬT DANH SÁCH CÂU HỎI TRONG ĐỀ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_exam') {
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $exam_type = trim($_POST['exam_type'] ?? 'REGULAR');
    $duration = (int)($_POST['duration'] ?? 15);
    $examPassword = trim($_POST['exam_password'] ?? '');

    $allowedExamTypes = ['REGULAR', 'MIDTERM_1', 'FINAL_1', 'MIDTERM_2', 'FINAL_2'];
    if (!in_array($exam_type, $allowedExamTypes, true)) {
        $exam_type = 'REGULAR';
    }

    $startTimeRaw = trim($_POST['start_time'] ?? '');
    $startTime = !empty($startTimeRaw) ? date('Y-m-d H:i:s', strtotime($startTimeRaw)) : null;

    $endTimeRaw = trim($_POST['end_time'] ?? '');
    $endTime = !empty($endTimeRaw) ? date('Y-m-d H:i:s', strtotime($endTimeRaw)) : null;

    $rawQuestionIds = $_POST['question_ids'] ?? [];
    if (!is_array($rawQuestionIds)) {
        $rawQuestionIds = [];
    }
    $selectedQuestionIds = array_values(array_unique(array_filter(array_map('intval', $rawQuestionIds), fn($id) => $id > 0)));

    if ($exam_id <= 0 || $title === '') {
        $_SESSION['flash_message'] = "Dữ liệu không hợp lệ!";
        $_SESSION['flash_type'] = "error";
    } elseif ($startTime !== null && $endTime !== null && strtotime($endTime) <= strtotime($startTime)) {
        $_SESSION['flash_message'] = "⚠️ Thời gian đóng đề thi phải sau thời gian mở đề thi!";
        $_SESSION['flash_type'] = "error";
    } elseif (empty($selectedQuestionIds)) {
        $_SESSION['flash_message'] = "⚠️ Đề thi phải có ít nhất 1 câu hỏi!";
        $_SESSION['flash_type'] = "error";
    } else {
        try {
            // Kiểm tra đề thi có thuộc giáo viên này không và lấy grade, subject_id
            $stmtExamInfo = $conn->prepare("
                SELECT e.exam_id, e.grade, e.subject_id, s.subject_name
                FROM exam e
                LEFT JOIN subjects s ON e.subject_id = s.subject_id
                WHERE e.exam_id = :exam_id AND e.user_id = :user_id
                LIMIT 1
            ");
            $stmtExamInfo->execute(['exam_id' => $exam_id, 'user_id' => $teacher_id]);
            $examInfo = $stmtExamInfo->fetch(PDO::FETCH_ASSOC);

            if (!$examInfo) {
                throw new Exception("Bạn không có quyền sửa đề thi này!");
            }

            $examGrade = (int)$examInfo['grade'];
            $examSubjectId = (int)($examInfo['subject_id'] ?? 0);
            $examSubjectNorm = mb_strtolower(trim((string)($examInfo['subject_name'] ?? '')), 'UTF-8');

            if ($examSubjectId > 0) {
                $subjectMatchSql = "(t.subject_id = ? OR LOWER(TRIM(IFNULL(ts.subject_name, ''))) = ?)";
                $subjectParams = [$examSubjectId, $examSubjectNorm];
            } else {
                $subjectMatchSql = "(t.subject_id IS NULL OR t.subject_id = 0)";
                $subjectParams = [];
            }

            // Lọc chỉ giữ các question_id hợp lệ thuộc quyền sở hữu của giáo viên & đúng khối/môn
            $placeholders = implode(',', array_fill(0, count($selectedQuestionIds), '?'));
            $stmtValidQ = $conn->prepare("
                SELECT q.question_id
                FROM questions q
                JOIN topics t ON q.topic_id = t.topic_id
                LEFT JOIN subjects ts ON t.subject_id = ts.subject_id
                WHERE t.grade = ?
                  AND {$subjectMatchSql}
                  AND q.user_id = ?
                  AND q.question_id IN ($placeholders)
            ");
            $stmtValidQ->execute(array_merge([$examGrade], $subjectParams, [$teacher_id], $selectedQuestionIds));
            $validQuestionIds = array_map('intval', $stmtValidQ->fetchAll(PDO::FETCH_COLUMN));

            if (empty($validQuestionIds)) {
                throw new Exception("Danh sách câu hỏi được chọn không hợp lệ!");
            }

            $conn->beginTransaction();

            // Cập nhật thông tin chung & tổng số câu hỏi mới
            $stmt_update = $conn->prepare("
                UPDATE exam
                SET title = :title,
                    exam_type = :exam_type,
                    duration = :duration,
                    start_time = :start_time,
                    end_time = :end_time,
                    exam_password = :exam_password,
                    total_questions = :total_questions
                WHERE exam_id = :exam_id AND user_id = :user_id
            ");

            $stmt_update->execute([
                'title' => $title,
                'exam_type' => $exam_type,
                'duration' => $duration,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'exam_password' => $examPassword !== '' ? $examPassword : null,
                'total_questions' => count($validQuestionIds),
                'exam_id' => $exam_id,
                'user_id' => $teacher_id
            ]);

            // Cập nhật lại danh sách câu hỏi trong exam_questions
            $stmtDelEq = $conn->prepare("DELETE FROM exam_questions WHERE exam_id = :exam_id");
            $stmtDelEq->execute(['exam_id' => $exam_id]);

            $stmtInsEq = $conn->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (:exam_id, :question_id)");
            foreach ($validQuestionIds as $qid) {
                $stmtInsEq->execute([
                    'exam_id' => $exam_id,
                    'question_id' => $qid
                ]);
            }

            $conn->commit();

            $_SESSION['flash_message'] = "Cập nhật đề thi #{$exam_id} (" . count($validQuestionIds) . " câu hỏi) thành công!";
            $_SESSION['flash_type'] = "success";
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['flash_message'] = "Lỗi khi cập nhật đề thi: " . $e->getMessage();
            $_SESSION['flash_type'] = "error";
        }
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// 3. THỐNG KÊ RIÊNG THEO TỪNG GIÁO VIÊN
$stmt = $conn->prepare("SELECT COUNT(*) FROM exam WHERE user_id = :user_id");
$stmt->execute(['user_id' => $teacher_id]);
$total_exams = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE user_id = :user_id");
$stmt->execute(['user_id' => $teacher_id]);
$total_questions = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM exam_results er 
    JOIN exam e ON er.exam_id = e.exam_id 
    WHERE e.user_id = :user_id
");
$stmt->execute(['user_id' => $teacher_id]);
$total_submissions = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT AVG(er.score) 
    FROM exam_results er 
    JOIN exam e ON er.exam_id = e.exam_id 
    WHERE e.user_id = :user_id
");
$stmt->execute(['user_id' => $teacher_id]);
$avg_score = round((float)$stmt->fetchColumn(), 1);

// 4. DANH SÁCH ĐỀ THI CỦA RIÊNG GIÁO VIÊN (KÈM SỐ LƯỢNG CHỦ ĐỀ & DANH SÁCH ID CÂU HỎI)
$stmt_exams = $conn->prepare("
    SELECT 
        e.*, 
        s.subject_name,
        COUNT(DISTINCT er.result_id) AS total_attempts,
        COUNT(DISTINCT t.topic_id) AS topic_count,
        GROUP_CONCAT(DISTINCT t.topic_name ORDER BY t.topic_name ASC SEPARATOR ', ') AS topic_names,
        GROUP_CONCAT(DISTINCT eq.question_id ORDER BY eq.question_id ASC SEPARATOR ',') AS question_ids_str
    FROM exam e
    LEFT JOIN subjects s ON e.subject_id = s.subject_id
    LEFT JOIN exam_results er ON e.exam_id = er.exam_id
    LEFT JOIN exam_questions eq ON e.exam_id = eq.exam_id
    LEFT JOIN questions q ON eq.question_id = q.question_id
    LEFT JOIN topics t ON q.topic_id = t.topic_id
    WHERE e.user_id = :user_id
    GROUP BY e.exam_id, s.subject_name
    ORDER BY e.exam_id DESC
    LIMIT 10
");
$stmt_exams->execute(['user_id' => $teacher_id]);
$recent_exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

// 5. LẤY NGÂN HÀNG CÂU HỎI CỦA GIÁO VIÊN ĐỂ PHỤC VỤ CHỈNH SỬA CÂU HỎI TRONG MODAL
$stmt_bank = $conn->prepare("
    SELECT 
        q.question_id,
        q.content,
        q.difficulty,
        t.topic_id,
        t.topic_name,
        t.grade,
        IFNULL(t.subject_id, 0) AS subject_id,
        LOWER(TRIM(IFNULL(ts.subject_name, ''))) AS subject_norm
    FROM questions q
    JOIN topics t ON q.topic_id = t.topic_id
    LEFT JOIN subjects ts ON t.subject_id = ts.subject_id
    WHERE q.user_id = :user_id
    ORDER BY t.topic_name ASC, q.question_id DESC
");
$stmt_bank->execute(['user_id' => $teacher_id]);
$teacher_questions_bank = $stmt_bank->fetchAll(PDO::FETCH_ASSOC);
