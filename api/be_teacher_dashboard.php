<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

$user_role = strtoupper($_SESSION['role'] ?? '');
if (!isset($_SESSION['user_id']) || ($user_role !== 'TEACHER' &&$user_role !== 'ADMIN')) {
    header('Location: login.php');
    exit;
}

$teacher_id =$_SESSION['user_id'] ?? 0;
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Giáo viên';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['exam_id'])) {
    $delete_id = (int)$_GET['exam_id'];

    try {
        $stmt_del_results =$conn->prepare("DELETE FROM exam_results WHERE exam_id = :exam_id");
        $stmt_del_results->execute(['exam_id' =>$delete_id]);

        $stmt_del_exam =$conn->prepare("DELETE FROM exam WHERE exam_id = :exam_id");
        $stmt_del_exam->execute(['exam_id' =>$delete_id]);

        $_SESSION['flash_message'] = "Đã xóa thành công đề thi #{$delete_id} và toàn bộ dữ liệu làm bài liên quan!";
        $_SESSION['flash_type'] = "success";
    } catch (PDOException $e) {$_SESSION['flash_message'] = "Lỗi khi xóa đề thi: " . $e->getMessage();$_SESSION['flash_type'] = "error";
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_exam') {
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $duration = (int)($_POST['duration'] ?? 15);
    $examPassword = trim($_POST['exam_password'] ?? '');

    $startTimeRaw = trim($_POST['start_time'] ?? '');$startTime = !empty($startTimeRaw) ? date('Y-m-d H:i:s', strtotime($startTimeRaw)) : null;

    $endTimeRaw = trim($_POST['end_time'] ?? '');$endTime = !empty($endTimeRaw) ? date('Y-m-d H:i:s', strtotime($endTimeRaw)) : null;

    if ($exam_id <= 0 || $title === '') {$_SESSION['flash_message'] = "Dữ liệu không hợp lệ!";
        $_SESSION['flash_type'] = "error";
    } elseif ($startTime !== null && $endTime !== null && strtotime($endTime) <= strtotime($startTime)) {$_SESSION['flash_message'] = "⚠️ Thời gian đóng đề thi phải sau thời gian mở đề thi!";
        $_SESSION['flash_type'] = "error";
    } else {
        try {
            $stmt_update =$conn->prepare("
                UPDATE exam
                SET title = :title,
                    duration = :duration,
                    start_time = :start_time,
                    end_time = :end_time,
                    exam_password = :exam_password
                WHERE exam_id = :exam_id
            ");

            $stmt_update->execute([
                'title' => $title,
                'duration' => $duration,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'exam_password' => $examPassword !== '' ?$examPassword : null,
                'exam_id' => $exam_id
            ]);

            $_SESSION['flash_message'] = "Cập nhật thông tin đề thi #{$exam_id} thành công!";
            $_SESSION['flash_type'] = "success";
        } catch (PDOException $e) {$_SESSION['flash_message'] = "Lỗi khi cập nhật đề thi: " . $e->getMessage();$_SESSION['flash_type'] = "error";
        }
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

$flash_message =$_SESSION['flash_message'] ?? '';
$flash_type =$_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'],$_SESSION['flash_type']);

$stmt =$conn->query("SELECT COUNT(*) FROM exam");
$total_exams = (int)$stmt->fetchColumn();

$stmt =$conn->query("SELECT COUNT(*) FROM questions");
$total_questions = (int)$stmt->fetchColumn();

$stmt =$conn->query("SELECT COUNT(*) FROM exam_results");
$total_submissions = (int)$stmt->fetchColumn();

$stmt =$conn->query("SELECT AVG(score) FROM exam_results");
$avg_score = round((float)$stmt->fetchColumn(), 1);

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
    GROUP BY e.exam_id, s.subject_name
    ORDER BY e.exam_id DESC
    LIMIT 10
");
$stmt_exams->execute();
$recent_exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);
