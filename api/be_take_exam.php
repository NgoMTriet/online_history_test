<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/exam_attempts.php';

/**
 * Lý do KHÔNG cho bắt đầu lượt làm mới:
 *   ''                  -> được làm
 *   'exam_closed'       -> đề đã đóng
 *   'attempts_exceeded' -> đã dùng hết số lượt (max_attempts > 0)
 * Mọi lượt (kể cả làm dở / thoát giữa chừng) đều được tính, giống intro_exam.php.
 */
function exam_new_attempt_block_reason(PDO $conn, array $exam, int $user_id): string
{
    if (!empty($exam['end_time'])) {
        $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
        if (new DateTime('now', $tz) >= new DateTime($exam['end_time'], $tz)) {
            return 'exam_closed';
        }
    }

    return '';
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$exam_id = (int) ($_GET['exam_id'] ?? 0);

if ($exam_id <= 0) {
    header('Location: index.php');
    exit;
}

// 1. Lấy thông tin đề thi
$stmt = $conn->prepare('SELECT * FROM exam WHERE exam_id = :id');
$stmt->execute(['id' => $exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die('Đề thi không tồn tại!');
}

$gate_state = 'ready';
$formattedStartTime = '';
$safeTitle = htmlspecialchars((string) ($exam['title'] ?? 'Bài thi'), ENT_QUOTES, 'UTF-8');
$password_error = '';
$password_error = '';

// 2. Kiểm tra thời gian mở đề
if (!empty($exam['start_time'])) {
    $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
    $now = new DateTime('now', $tz);
    $startTime = new DateTime($exam['start_time'], $tz);

    if ($now < $startTime) {
        $formattedStartTime = $startTime->format('H:i - d/m/Y');
        $gate_state = 'not_started';
        return;
    }
}
// 3. Kiểm tra lượt thi đang làm dở
close_expired_attempts($conn);
$in_progress = find_in_progress_attempt($conn, $user_id, $exam_id);

// Chưa có lượt đang làm: kiểm tra đề đã đóng / đã hết số lượt chưa
if (!$in_progress) {
    $block_reason = exam_new_attempt_block_reason($conn, $exam, $user_id);
    if ($block_reason !== '') {
        $gate_state = $block_reason;
        return;
    }
}

unset($_SESSION['exam_completed_' . $exam_id]);
unset($_SESSION['exam_completed_' . $exam_id]);

// 4. Kiểm tra mật khẩu đề thi
$require_password = !empty($exam['exam_password']);

if ($require_password) {
    $session_pass_key = 'exam_auth_' . $exam_id;

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['submit_exam_password'])
    ) {
        $input_password = trim($_POST['input_password'] ?? '');

        if ($input_password === $exam['exam_password']) {
            $_SESSION[$session_pass_key] = true;
        } else {
            $password_error = 'Mật khẩu bài thi không chính xác!';
        }
    }

    if (empty($_SESSION[$session_pass_key])) {
        $gate_state = 'password_required';
        return;
    }
}

// 5. Lấy danh sách câu hỏi
$stmt_q = $conn->prepare(
    'SELECT q.question_id, q.content
     FROM questions q
     JOIN exam_questions eq ON q.question_id = eq.question_id
     WHERE eq.exam_id = :exam_id
     ORDER BY q.question_id'
);
$stmt_q->execute(['exam_id' => $exam_id]);
$raw_questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

if (empty($raw_questions)) {
    die('Đề thi này chưa có câu hỏi nào!');
}

// Tính lượt làm bài ngay khi bắt đầu (thoát giữa chừng vẫn mất lượt).
$duration_seconds = max(1, (int) ($exam['duration'] ?? 15) * 60);

if ($in_progress) {
    $attempt_id = (int) $in_progress['result_id'];
    $remaining_seconds = max(1, $duration_seconds - (int) $in_progress['elapsed']);
} else {
    // Khoá theo (học sinh, đề): mở 2 tab cùng lúc cũng không vượt quá số lượt cho phép
    $lock_name = 'exam_attempt_' . $user_id . '_' . $exam_id;
    $got_lock  = (int) $conn->query('SELECT GET_LOCK(' . $conn->quote($lock_name) . ', 5)')->fetchColumn() === 1;

    try {
        // Kiểm tra lại bên trong khoá
        $in_progress = find_in_progress_attempt($conn, $user_id, $exam_id);

        if ($in_progress) {
            $attempt_id = (int) $in_progress['result_id'];
            $remaining_seconds = max(1, $duration_seconds - (int) $in_progress['elapsed']);
        } else {
            $block_reason = exam_new_attempt_block_reason($conn, $exam, $user_id);
            if ($block_reason !== '') {
                $gate_state = $block_reason;
            } else {
                $attempt_id = start_attempt($conn, $user_id, $exam_id, count($raw_questions));
                $remaining_seconds = $duration_seconds;
            }
        }
    } finally {
        if ($got_lock) {
            $conn->query('SELECT RELEASE_LOCK(' . $conn->quote($lock_name) . ')')->fetchColumn();
        }
    }

    if ($gate_state !== 'ready') {
        return;
    }
}

// Xáo trộn theo mã lượt thi: tải lại trang (F5) vẫn ra đúng thứ tự cũ.
mt_srand($attempt_id * 7919 + 13);
shuffle($raw_questions);

// 6. Lấy danh sách đáp án
$stmt_ans = $conn->prepare(
    'SELECT * FROM answers WHERE question_id = :q_id ORDER BY answer_id'
);

$questions_for_client = [];

foreach ($raw_questions as $q) {
    $stmt_ans->execute(['q_id' => $q['question_id']]);
    $answers = $stmt_ans->fetchAll(PDO::FETCH_ASSOC);

    shuffle($answers);

    $options = [];
    $labels = ['A', 'B', 'C', 'D'];

    foreach ($answers as $idx => $a) {
        $options[] = [
            'id' => $a['answer_id'],
            'label' => $labels[$idx] ?? (string) ($idx + 1),
            'content' => $a['content']
        ];
    }

    $questions_for_client[] = [
        'id' => $q['question_id'],
        'question' => $q['content'],
        'options' => $options
    ];
}

// Trả bộ sinh số ngẫu nhiên về trạng thái bình thường.
mt_srand();