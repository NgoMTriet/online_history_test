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
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Giáo viên';

// 1. XỬ LÝ XÓA ĐỀ THI
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['exam_id'])) {
    $delete_id = (int)$_GET['exam_id'];

    try {
        $stmt_check = $conn->prepare("SELECT exam_id FROM exam WHERE exam_id = :exam_id AND user_id = :user_id");
        $stmt_check->execute(['exam_id' => $delete_id, 'user_id' => $teacher_id]);

        if ($stmt_check->fetch()) {
            $stmt_del_results = $conn->prepare("DELETE FROM exam_results WHERE exam_id = :exam_id");
            $stmt_del_results->execute(['exam_id' => $delete_id]);

            $stmt_del_exam = $conn->prepare("DELETE FROM exam WHERE exam_id = :exam_id AND user_id = :user_id");
            $stmt_del_exam->execute(['exam_id' => $delete_id, 'user_id' => $teacher_id]);

            $_SESSION['flash_message'] = "Đã xóa thành công đề thi #{$delete_id} và toàn bộ dữ liệu làm bài liên quan!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Bạn không có quyền xóa đề thi này!";
            $_SESSION['flash_type'] = "error";
        }
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Lỗi khi xóa đề thi: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// 2. XỬ LÝ SỬA ĐỀ THI (ĐÃ BỔ SUNG CẬP NHẬT EXAM_TYPE)
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

    if ($exam_id <= 0 || $title === '') {
        $_SESSION['flash_message'] = "Dữ liệu không hợp lệ!";
        $_SESSION['flash_type'] = "error";
    } elseif ($startTime !== null && $endTime !== null && strtotime($endTime) <= strtotime($startTime)) {
        $_SESSION['flash_message'] = "⚠️ Thời gian đóng đề thi phải sau thời gian mở đề thi!";
        $_SESSION['flash_type'] = "error";
    } else {
        try {
            $stmt_update = $conn->prepare("
                UPDATE exam
                SET title = :title,
                    exam_type = :exam_type,
                    duration = :duration,
                    start_time = :start_time,
                    end_time = :end_time,
                    exam_password = :exam_password
                WHERE exam_id = :exam_id AND user_id = :user_id
            ");

            $stmt_update->execute([
                'title' => $title,
                'exam_type' => $exam_type,
                'duration' => $duration,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'exam_password' => $examPassword !== '' ? $examPassword : null,
                'exam_id' => $exam_id,
                'user_id' => $teacher_id
            ]);

            if ($stmt_update->rowCount() > 0) {
                $_SESSION['flash_message'] = "Cập nhật thông tin đề thi #{$exam_id} thành công!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Không có thay đổi nào hoặc bạn không có quyền sửa đề thi này!";
                $_SESSION['flash_type'] = "error";
            }
        } catch (PDOException $e) {
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

// 4. DANH SÁCH ĐỀ THI CỦA RIÊNG GIÁO VIÊN ĐANG ĐĂNG NHẬP
$stmt_exams = $conn->prepare("
    SELECT 
        e.*, 
        s.subject_name,
        COUNT(DISTINCT er.result_id) AS total_attempts,
        GROUP_CONCAT(DISTINCT t.topic_name SEPARATOR ', ') AS topic_names
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
