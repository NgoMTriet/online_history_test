<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Yêu cầu đăng nhập để xem lịch sử của đúng tài khoản.
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_page = 'history.php';
$user_id = (int) $_SESSION['user_id'];
$is_teacher = strtoupper((string)($_SESSION['role'] ?? '')) === 'TEACHER';
$detail_attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : null;

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Hiển thị điểm gọn: 7.50 -> 7.5, 8.00 -> 8
function fmt_score($score): string
{
    return rtrim(rtrim(number_format((float)$score, 2, '.', ''), '0'), '.');
}

$user_columns = $conn->query('SHOW COLUMNS FROM `users`')->fetchAll(PDO::FETCH_COLUMN);
$name_candidates = ['full_name', 'fullname', 'name', 'username', 'user_name', 'ho_ten', 'hoten'];
$name_column = null;
foreach ($name_candidates as $candidate) {
    if (in_array($candidate, $user_columns, true)) {
        $name_column = $candidate;
        break;
    }
}
if ($name_column === null) {
    $name_expression = "CONCAT('Học viên #', r.user_id)";
} else {
    $safe_name_column = '`' . str_replace('`', '``', $name_column) . '`';
    $name_expression = "COALESCE(NULLIF(u.$safe_name_column, ''), CONCAT('Học viên #', r.user_id))";
}

$student_name = '';
if ($name_column !== null) {
    $name_stmt =$conn->prepare("SELECT `$name_column` FROM `users` WHERE `user_id` = :user_id LIMIT 1");
    $name_stmt->execute(['user_id' =>$user_id]);
    $student_name = (string)($name_stmt->fetchColumn() ?: '');
}
if ($student_name === '') {
    $student_name =$_SESSION['full_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'Người dùng';
}

// Danh sách lịch sử: bỏ qua các lượt đang làm dở (IN_PROGRESS)
$sql_history = "
    SELECT r.result_id AS attempt_id, r.user_id AS student_id,
           $name_expression AS student_name,
           e.title AS exam_title, r.submitted_at,
           CONCAT(FLOOR(r.completion_time / 60), ' phút ', MOD(r.completion_time, 60), ' giây') AS completion_time,
           r.score, (r.status = 'PASSED') AS passed
    FROM exam_results r
    JOIN exam e ON r.exam_id = e.exam_id
    LEFT JOIN `users` u ON u.user_id = r.user_id
";
$where = ["r.status <> 'IN_PROGRESS'"];
if (!$is_teacher) {$where[] = 'r.user_id = :user_id';
}
$sql_history .= ' WHERE ' . implode(' AND ', $where);$sql_history .= ' ORDER BY r.submitted_at DESC';

$stmt = $conn->prepare($sql_history);
if (!$is_teacher) {
    $stmt->execute(['user_id' =>$user_id]);
} else {
    $stmt->execute();
}
$history_list =$stmt->fetchAll();

// Thống kê nhanh cho đầu trang
$total_attempts = count($history_list);$score_sum = 0.0;
$passed_count = 0;
$best_score = null;
foreach ($history_list as$row) {
    $score_value = (float)$row['score'];
    $score_sum +=$score_value;
    if ($row['passed']) {$passed_count++;
    }
    $best_score = $best_score === null ? $score_value : max($best_score,$score_value);
}
$avg_score =$total_attempts > 0 ? $score_sum / $total_attempts : 0;
$pass_rate =$total_attempts > 0 ? (int)round($passed_count * 100 / $total_attempts) : 0;

// Chi tiết lượt thi khi chọn
$attempt_detail = null;
$cnt_correct = 0;
$cnt_wrong = 0;
$cnt_blank = 0;

if ($detail_attempt_id) {
    $stmt_info =$conn->prepare("
        SELECT r.result_id AS attempt_id, r.user_id AS student_id,
               e.title AS exam_title, r.score, r.submitted_at,
               CONCAT(FLOOR(r.completion_time / 60), ' phút ', MOD(r.completion_time, 60), ' giây') AS completion_time
        FROM exam_results r
        JOIN exam e ON r.exam_id = e.exam_id
        WHERE r.result_id = :r_id
          AND r.status <> 'IN_PROGRESS'
          AND (:is_teacher = 1 OR r.user_id = :u_id)
    ");
    $stmt_info->execute([
        'r_id' => $detail_attempt_id,
        'is_teacher' => $is_teacher ? 1 : 0,
        'u_id' => $user_id
    ]);
    $info =$stmt_info->fetch();

    if ($info) {
        $detail_student_name =$student_name;
        if ($is_teacher &&$name_column !== null) {
            $detail_name_stmt =$conn->prepare("SELECT `$name_column` FROM `users` WHERE `user_id` = :uid LIMIT 1");
            $detail_name_stmt->execute(['uid' => (int)$info['student_id']]);$detail_student_name = (string)($detail_name_stmt->fetchColumn() ?: ('Học viên #' . $info['student_id']));
        } elseif ($is_teacher) {
            $detail_student_name = 'Học viên #' . $info['student_id'];
        }
        $stmt_qs =$conn->prepare("
            SELECT q.question_id, q.content AS question, q.explanation, sa.selected_answer_id
            FROM student_answers sa
            JOIN questions q ON sa.question_id = q.question_id
            WHERE sa.result_id = :r_id
        ");
        $stmt_qs->execute(['r_id' =>$detail_attempt_id]);
        $raw_qs =$stmt_qs->fetchAll();

        $questions = [];
        $stmt_ans =$conn->prepare("SELECT answer_id, content, is_correct FROM answers WHERE question_id = :q_id ORDER BY answer_id");

        foreach ($raw_qs as$rq) {
            $stmt_ans->execute(['q_id' =>$rq['question_id']]);
            $ans_list =$stmt_ans->fetchAll();

            $options = [];$correct_key = '';
            $selected_key = '';$labels = ['A', 'B', 'C', 'D'];

            foreach ($ans_list as$idx => $a) {$key = $labels[$idx] ?? $a['answer_id'];$options[$key] =$a['content'];
                if ($a['is_correct']) $correct_key =$key;
                if ($a['answer_id'] ==$rq['selected_answer_id']) $selected_key =$key;
            }

            $questions[] = [
                'id' => $rq['question_id'],
                'question' => $rq['question'],
                'options' => $options,
                'selected' => $selected_key,
                'correct' => $correct_key,
                'explanation' => $rq['explanation']
            ];
        }

        $attempt_detail = [
            'attempt_id' => $info['attempt_id'],
            'student_id' => $info['student_id'],
            'student_name' => $detail_student_name,
            'exam_title' => $info['exam_title'],
            'score' => $info['score'],
            'submitted_at' => $info['submitted_at'],
            'completion_time' => $info['completion_time'],
            'questions' => $questions
        ];

        // Đếm số câu đúng / sai / chưa chọn
        foreach ($questions as$q) {
            if ($q['selected'] === '') {$cnt_blank++;
            } elseif ($q['selected'] === $q['correct']) {$cnt_correct++;
            } else {
                $cnt_wrong++;
            }
        }
    }
}
