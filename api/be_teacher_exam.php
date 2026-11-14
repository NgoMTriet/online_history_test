<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Bắt đầu Session nếu chưa chạy (Đảm bảo đọc được $_SESSION)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_exam') {
    // 🟢 LẤY USER_ID CỦA GIÁO VIÊN ĐANG ĐĂNG NHẬP TỪ SESSION
    $userId = $_SESSION['user_id'] ?? null;

    $title = trim($_POST['title'] ?? '');
    $grade = (int)($_POST['grade'] ?? 9);
    $duration = (int)($_POST['duration'] ?? 15);
    $maxAttempts = (int)($_POST['max_attempts'] ?? 0);
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

    if ($title === '') {
        $message = 'Vui lòng nhập tên đề thi.';
        $messageType = 'danger';
    } elseif (!in_array($grade, [6, 7, 8, 9], true)) {
        $message = 'Khối lớp không hợp lệ.';
        $messageType = 'danger';
    } elseif ($duration < 1 || $duration > 300) {
        $message = 'Thời gian làm bài phải từ 1 đến 300 phút.';
        $messageType = 'danger';
    } elseif ($maxAttempts < 0) {
        $message = 'Số lần làm bài không hợp lệ.';
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
                    $stmtCheck = $conn->prepare(
                        "SELECT q.question_id
                         FROM questions q
                         JOIN topics t ON q.topic_id = t.topic_id
                         WHERE t.grade = ?
                         AND q.question_id IN ($placeholders)"
                    );
                    $stmtCheck->execute(array_merge([$grade], $selectedQuestionIds));
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

                    $sql = "SELECT q.question_id
                            FROM questions q
                            JOIN topics t ON q.topic_id = t.topic_id
                            WHERE t.grade = :grade AND q.difficulty = :level";

                    if ($topicId > 0) {
                        $sql .= " AND q.topic_id = :topic_id";
                    }
                    $sql .= " ORDER BY RAND() LIMIT :limit";

                    $stmtRandom = $conn->prepare($sql);
                    $stmtRandom->bindValue(':grade', $grade, PDO::PARAM_INT);
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

            // 🟢 CẬP NHẬT CÂU SQL INSERT THÊM CỘT USER_ID
            $stmtExam = $conn->prepare(
                "INSERT INTO exam
                 (title, grade, duration, start_time, end_time, max_attempts, exam_password, total_questions, is_randomized, user_id)
                 VALUES (:title, :grade, :duration, :start_time, :end_time, :max_attempts, :exam_password, :total_questions, :is_randomized, :user_id)"
            );

            $stmtExam->execute([
                'title'           => $title,
                'grade'           => $grade,
                'duration'        => $duration,
                'start_time'      => $startTime,
                'end_time'        => $endTime,
                'max_attempts'    => $maxAttempts,
                'exam_password'   => $examPassword !== '' ? $examPassword : null,
                'total_questions' => $totalQuestions,
                'is_randomized'   => ($mode === 'auto') ? 1 : 0,
                'user_id'         => $userId // <--- Gắn ID người dùng vào đây
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

$stmtTopics = $conn->query("SELECT topic_id, topic_name, grade FROM topics ORDER BY grade ASC, topic_name ASC");
$allTopics = $stmtTopics->fetchAll(PDO::FETCH_ASSOC);

$stmtQuestions = $conn->query(
    "SELECT q.question_id, q.content, q.difficulty, t.grade, t.topic_name, t.topic_id
     FROM questions q
     JOIN topics t ON q.topic_id = t.topic_id
     ORDER BY t.grade ASC, q.question_id DESC"
);

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
        'd' => strtoupper($question['difficulty'] ?? 'EASY')
    ];
}