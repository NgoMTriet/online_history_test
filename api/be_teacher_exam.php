<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Bắt đầu Session nếu chưa chạy (Đảm bảo đọc được $_SESSION)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// LẤY USER_ID CỦA GIÁO VIÊN ĐANG ĐĂNG NHẬP Ở ĐẦU FILE ĐỂ DÙNG CHO TOÀN BỘ TRANG
$userId = (int)($_SESSION['user_id'] ?? 0);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_exam') {
    $title = trim($_POST['title'] ?? '');
    $grade = (int)($_POST['grade'] ?? 9);
    $duration = (int)($_POST['duration'] ?? 15);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $examPassword = trim($_POST['exam_password'] ?? '');
    $mode = $_POST['create_mode'] ?? 'manual';

    $startTimeRaw = trim($_POST['start_time'] ?? '');
    $startTime = null;
    if ($startTimeRaw !== '') {
        $timestampStart = strtotime($startTimeRaw);
        if ($timestampStart !== false) {
            $startTime = date('Y-m-d H:i:s', $timestampStart);
        }
    }

    $endTimeRaw = trim($_POST['end_time'] ?? '');
    $endTime = null;
    if ($endTimeRaw !== '') {
        $timestampEnd = strtotime($endTimeRaw);
        if ($timestampEnd !== false) {
            $endTime = date('Y-m-d H:i:s', $timestampEnd);
        }
    }

    // Môn học phải thuộc đúng khối lớp đã chọn
    $subjectValid = false;
    if ($subjectId > 0) {
        $stmtSubject = $conn->prepare("SELECT COUNT(*) FROM subjects WHERE subject_id = :sid AND grade = :grade");
        $stmtSubject->execute(['sid' => $subjectId, 'grade' => $grade]);
        $subjectValid = (int)$stmtSubject->fetchColumn() > 0;
    }

    if ($userId <= 0) {
        $message = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
        $messageType = 'danger';
    } elseif ($title === '') {
        $message = 'Vui lòng nhập tên đề thi.';
        $messageType = 'danger';
    } elseif (!in_array($grade, [6, 7, 8, 9], true)) {
        $message = 'Khối lớp không hợp lệ.';
        $messageType = 'danger';
    } elseif (!$subjectValid) {
        $message = 'Vui lòng chọn môn học hợp lệ cho khối lớp này.';
        $messageType = 'danger';
    } elseif ($duration < 1 || $duration > 300) {
        $message = 'Thời gian làm bài phải từ 1 đến 300 phút.';
        $messageType = 'danger';
    } elseif ($startTime !== null && $endTime !== null && strtotime($endTime) <= strtotime($startTime)) {
        $message = 'Thời gian đóng đề thi phải sau thời gian mở đề thi.';
        $messageType = 'danger';
    } elseif (!in_array($mode, ['manual', 'auto'], true)) {
        $message = 'Phương thức chọn câu hỏi không hợp lệ.';
        $messageType = 'danger';
    } else {
        try {
            $selectedQuestionIds = [];

            if ($mode === 'manual') {
                $rawIds = $_POST['question_ids'] ?? [];
                if (!is_array($rawIds)) { $rawIds = []; }

                $selectedQuestionIds = array_values(
                    array_unique(
                        array_filter(array_map('intval', $rawIds), fn($id) => $id > 0)
                    )
                );

                if (count($selectedQuestionIds) > 0) {
                    $placeholders = implode(',', array_fill(0, count($selectedQuestionIds), '?'));
                    // ĐÃ THÊM: AND q.user_id = ? để chỉ lấy câu hỏi của chính giáo viên này
                    $stmtCheck = $conn->prepare(
                        "SELECT q.question_id
                         FROM questions q
                         JOIN topics t ON q.topic_id = t.topic_id
                         WHERE t.grade = ?
                         AND t.subject_id = ?
                         AND q.user_id = ?
                         AND q.question_id IN ($placeholders)"
                    );
                    $stmtCheck->execute(array_merge([$grade, $subjectId, $userId], $selectedQuestionIds));
                    $validIds = array_map('intval', $stmtCheck->fetchAll(PDO::FETCH_COLUMN));
                    $selectedQuestionIds = array_values(array_intersect($selectedQuestionIds, $validIds));
                }
            } else {
                $topicId = (int)($_POST['auto_topic_id'] ?? 0);
                $countEasy = max(0, (int)($_POST['count_easy'] ?? 0));
                $countMedium = max(0, (int)($_POST['count_medium'] ?? 0));
                $countHard = max(0, (int)($_POST['count_hard'] ?? 0));

                if ($countEasy + $countMedium + $countHard === 0) {
                    throw new Exception('Vui lòng nhập số lượng cho ít nhất 1 mức độ câu hỏi.');
                }

                $levelsMap = [
                    'EASY' => $countEasy,
                    'MEDIUM' => $countMedium,
                    'HARD' => $countHard
                ];

                foreach ($levelsMap as $level => $count) {
                    if ($count <= 0) continue;

                    // ĐÃ THÊM: AND q.user_id = :user_id để chỉ bốc ngẫu nhiên câu hỏi của giáo viên này
                    $sql = "SELECT q.question_id
                            FROM questions q
                            JOIN topics t ON q.topic_id = t.topic_id
                            WHERE t.grade = :grade 
                            AND t.subject_id = :subject_id 
                            AND q.user_id = :user_id 
                            AND q.difficulty = :level";

                    if ($topicId > 0) {
                        $sql .= " AND q.topic_id = :topic_id";
                    }
                    $sql .= " ORDER BY RAND() LIMIT :limit";

                    $stmtRandom = $conn->prepare($sql);
                    $stmtRandom->bindValue(':grade', $grade, PDO::PARAM_INT);
                    $stmtRandom->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
                    $stmtRandom->bindValue(':user_id', $userId, PDO::PARAM_INT);
                    $stmtRandom->bindValue(':level', $level, PDO::PARAM_STR);
                    $stmtRandom->bindValue(':limit', $count, PDO::PARAM_INT);

                    if ($topicId > 0) {
                        $stmtRandom->bindValue(':topic_id', $topicId, PDO::PARAM_INT);
                    }

                    $stmtRandom->execute();
                    $fetchedIds = array_map('intval', $stmtRandom->fetchAll(PDO::FETCH_COLUMN));
                    $selectedQuestionIds = array_merge($selectedQuestionIds, $fetchedIds);
                }
            }

            $totalQuestions = count($selectedQuestionIds);
            if ($totalQuestions === 0) {
                throw new Exception('Không tìm thấy câu hỏi phù hợp với cấu hình.');
            }

            $conn->beginTransaction();

            $stmtExam = $conn->prepare(
                "INSERT INTO exam
                 (title, grade, subject_id, duration, start_time, end_time, exam_password, total_questions, is_randomized, user_id)
                 VALUES (:title, :grade, :subject_id, :duration, :start_time, :end_time, :exam_password, :total_questions, :is_randomized, :user_id)"
            );

            $stmtExam->execute([
                'title'           => $title,
                'grade'           => $grade,
                'subject_id'      => $subjectId,
                'duration'        => $duration,
                'start_time'      => $startTime,
                'end_time'        => $endTime,
                'exam_password'   => $examPassword !== '' ? $examPassword : null,
                'total_questions' => $totalQuestions,
                'is_randomized'   => ($mode === 'auto') ? 1 : 0,
                'user_id'         => $userId
            ]);

            $examId = (int)$conn->lastInsertId();

            $stmtLink = $conn->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (:exam_id, :question_id)");
            foreach ($selectedQuestionIds as $questionId) {
                $stmtLink->execute(['exam_id' => $examId, 'question_id' => $questionId]);
            }

            $conn->commit();
            $message = "Tạo đề thi thành công! Đề thi gồm {$totalQuestions} câu hỏi.";
            $messageType = 'success';
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// ĐÃ SỬA: Chỉ lấy danh sách chủ đề của giáo viên đang đăng nhập
$stmtTopics = $conn->prepare("SELECT topic_id, topic_name, grade, subject_id FROM topics WHERE user_id = :user_id ORDER BY grade ASC, topic_name ASC");
$stmtTopics->execute(['user_id' => $userId]);
$allTopics = $stmtTopics->fetchAll(PDO::FETCH_ASSOC);

$stmtSubjects = $conn->query("SELECT subject_id, subject_name, grade FROM subjects ORDER BY grade ASC, subject_name ASC");
$allSubjects = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);

// ĐÃ SỬA: Chỉ lấy danh sách câu hỏi của giáo viên đang đăng nhập
$stmtQuestions = $conn->prepare(
    "SELECT q.question_id, q.content, q.difficulty, t.grade, t.topic_name, t.topic_id, t.subject_id
     FROM questions q
     JOIN topics t ON q.topic_id = t.topic_id
     WHERE q.user_id = :user_id
     ORDER BY t.grade ASC, q.question_id DESC"
);
$stmtQuestions->execute(['user_id' => $userId]);

$questionsBank = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);
$questionCounts = [6 => 0, 7 => 0, 8 => 0, 9 => 0];
$jsQuestionsData = [];

foreach ($questionsBank as $question) {
    $g = (int)$question['grade'];
    if (isset($questionCounts[$g])) {
        $questionCounts[$g]++;
    }
    $jsQuestionsData[] = [
        'g' => (int)$question['grade'],
        't' => (int)$question['topic_id'],
        's' => (int)$question['subject_id'],
        'd' => strtoupper($question['difficulty'] ?? 'EASY')
    ];
}
