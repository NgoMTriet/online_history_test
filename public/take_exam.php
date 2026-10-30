<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/exam_attempts.php';

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

// 2. Kiểm tra thời gian mở đề
if (!empty($exam['start_time'])) {
    $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
    $now = new DateTime('now', $tz);
    $startTime = new DateTime($exam['start_time'], $tz);

    if ($now < $startTime) {
        $formattedStartTime = $startTime->format('H:i - d/m/Y');
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Chưa đến giờ làm bài</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f4f6f9;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .card {
                    background: white;
                    padding: 35px;
                    border-radius: 12px;
                    text-align: center;
                    max-width: 480px;
                    width: 90%;
                    box-sizing: border-box;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 24px;
                    background: #3498db;
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                }
                .submit-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 20000;
    background: rgba(0, 0, 0, 0.65);
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.submit-modal.show {
    display: flex;
}

.submit-modal-content {
    width: 100%;
    max-width: 420px;
    background: white;
    padding: 30px;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,.2);
}

.submit-modal-content h2 {
    color: #1e293b;
    margin-bottom: 15px;
}

.submit-modal-content p {
    color: #475569;
    line-height: 1.6;
    margin-bottom: 20px;
}
            </style>
        </head>
        <body>
            <div class="card">
                <h2>⏳ Chưa đến thời gian làm bài!</h2>
                <p>
                    Đề thi
                    <strong><?= htmlspecialchars($exam['title'] ?? 'Bài thi', ENT_QUOTES, 'UTF-8') ?></strong>
                    sẽ mở lúc
                    <strong><?= htmlspecialchars($formattedStartTime, ENT_QUOTES, 'UTF-8') ?></strong>.
                </p>
                <a href="index.php" class="btn">Quay lại trang chủ</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// 3. Kiểm tra số lượt làm bài
// max_attempts = 0: không giới hạn
// Đóng các lượt làm dở đã quá hạn, rồi tìm lượt đang làm của học viên (nếu có).
close_expired_attempts($conn);
$in_progress = find_in_progress_attempt($conn, $user_id, $exam_id);

$max_attempts = (int) ($exam['max_attempts'] ?? 0);

$stmt_check = $conn->prepare(
    'SELECT COUNT(*) FROM exam_results
     WHERE user_id = :user_id AND exam_id = :exam_id'
);

$stmt_check->execute([
    'user_id' => $user_id,
    'exam_id' => $exam_id
]);

$user_attempts = (int) $stmt_check->fetchColumn();

// Lượt đang làm dở thì được tiếp tục, không bị chặn vì hết lượt.
if ($max_attempts > 0 && $user_attempts >= $max_attempts && !$in_progress) {
    unset($_SESSION['exam_auth_' . $exam_id]);

    $safeTitle = htmlspecialchars(
        (string) ($exam['title'] ?? 'Bài thi'),
        ENT_QUOTES,
        'UTF-8'
    );
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Hết lượt làm bài</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f4f6f9;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }
            .card {
                background: white;
                padding: 35px;
                border-radius: 12px;
                text-align: center;
                max-width: 480px;
                width: 90%;
                box-sizing: border-box;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 8px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>🚫 Bạn đã hết lượt làm bài!</h2>
            <p>
                Đề thi <strong><?= $safeTitle ?></strong>
                cho phép làm tối đa <strong><?= $max_attempts ?></strong> lần.
                <br>
                Bạn đã thực hiện
                <strong><?= $user_attempts ?>/<?= $max_attempts ?></strong> lượt.
            </p>
            <a href="index.php" class="btn">Quay lại trang chủ</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

unset($_SESSION['exam_completed_' . $exam_id]);

// 4. Kiểm tra mật khẩu đề thi
$require_password = !empty($exam['exam_password']);
$password_error = '';

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
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Yêu cầu mật khẩu bài thi</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f4f6f9;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .card {
                    background: white;
                    padding: 30px;
                    border-radius: 12px;
                    text-align: center;
                    max-width: 400px;
                    width: 90%;
                    box-sizing: border-box;
                }
                .form-input {
                    width: 100%;
                    padding: 12px;
                    margin-bottom: 15px;
                    border: 1px solid #ccc;
                    border-radius: 8px;
                    box-sizing: border-box;
                }
                .btn-submit {
                    width: 100%;
                    padding: 12px;
                    background: #2563eb;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                }
                .error-msg {
                    color: #dc2626;
                    margin-bottom: 12px;
                }
            </style>
        </head>
        <body>
            <div class="card">
                <h2>🔒 Đề thi có mật khẩu</h2>
                <p>
                    Nhập mật khẩu để làm bài
                    <strong><?= htmlspecialchars($exam['title'] ?? 'Bài thi', ENT_QUOTES, 'UTF-8') ?></strong>.
                </p>

                <?php if ($password_error !== ''): ?>
                    <div class="error-msg">
                        <?= htmlspecialchars($password_error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input
                        type="password"
                        name="input_password"
                        class="form-input"
                        placeholder="Nhập mật khẩu đề thi"
                        required
                        autofocus
                    >
                    <button
                        type="submit"
                        name="submit_exam_password"
                        class="btn-submit"
                    >
                        Xác nhận làm bài
                    </button>
                </form>

                <p><a href="index.php">Quay lại trang chủ</a></p>
            </div>
        </body>
        </html>
        <?php
        exit;
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
// Nếu đang có lượt làm dở còn thời gian thì tiếp tục lượt đó.
$duration_seconds = max(1, (int) ($exam['duration'] ?? 15) * 60);

if ($in_progress) {
    $attempt_id = (int) $in_progress['result_id'];
    $remaining_seconds = max(1, $duration_seconds - (int) $in_progress['elapsed']);
} else {
    $attempt_id = start_attempt($conn, $user_id, $exam_id, count($raw_questions));
    $remaining_seconds = $duration_seconds;
}

// Xáo trộn theo mã lượt thi: tải lại trang (F5) vẫn ra đúng thứ tự cũ,
// còn mỗi lượt thi mới sẽ có thứ tự khác.
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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($exam['title'] ?? 'Làm bài thi', ENT_QUOTES, 'UTF-8') ?></title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, sans-serif;
}

body {
    background: #f4f6f9;
    color: #333;
    padding-bottom: 80px;
}

header {
    background: #2c3e50;
    color: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.exam-title {
    font-size: 1.2rem;
    font-weight: bold;
}

.timer-box {
    background: #e74c3c;
    color: white;
    padding: 9px 18px;
    border-radius: 25px;
    font-weight: bold;
    white-space: nowrap;
    font-size: 18px;
    box-shadow: 0 3px 10px rgba(231, 76, 60, .2);
}

.container {
    max-width: 1250px;
    margin: 30px auto;
    padding: 0 20px;
}

.exam-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 270px;
    gap: 24px;
    align-items: start;
}

.exam-main {
    min-width: 0;
}

.question-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px #0000000d;
    scroll-margin-top: 90px;
}

.question-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 15px;
    color: #2c3e50;
    line-height: 1.5;
}

.options-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.option-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    line-height: 1.5;
}

.option-item:hover {
    background: #f8f9fa;
}

.option-item input {
    flex-shrink: 0;
}

.question-sidebar {
    position: sticky;
    top: 90px;
    align-self: start;
}

.question-navigation {
    background: white;
    padding: 18px;
    border-radius: 12px;
    box-shadow: 0 4px 15px #00000012;
}

.question-navigation h3 {
    margin-bottom: 14px;
    color: #2c3e50;
    font-size: 17px;
}

.question-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

.question-number {
    width: 100%;
    height: 40px;
    border: 1px solid #fca5a5;
    border-radius: 8px;
    background: #fee2e2;
    color: #b91c1c;
    font-weight: bold;
    cursor: pointer;
}

.question-number.answered {
    background: #dcfce7;
    border-color: #86efac;
    color: #166534;
}

.question-number.current {
    outline: 3px solid #93c5fd;
    border-color: #2563eb;
}

.question-number.unanswered-warning {
    animation: warningPulse 1s ease-in-out 2;
    border: 2px solid #dc2626;
}

@keyframes warningPulse {
    50% {
        background: #fca5a5;
        transform: scale(1.06);
    }
}

.question-progress {
    font-size: 13px;
    color: #475569;
    margin-top: 12px;
}

.question-legend {
    display: flex;
    flex-direction: column;
    gap: 9px;
    margin-top: 16px;
    font-size: 13px;
}

.question-legend span {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-box {
    width: 13px;
    height: 13px;
    display: inline-block;
    border-radius: 3px;
    flex-shrink: 0;
}

/* Nút nộp bài trong thanh danh sách câu hỏi */
.submit-exam-container {
    margin-top: 20px;
}

.submit-exam-btn {
    width: 100%;
    padding: 13px 15px;
    background: #16a34a;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background .2s, transform .2s;
}

.submit-exam-btn:hover {
    background: #15803d;
    transform: translateY(-1px);
}

.submit-exam-btn:disabled {
    opacity: .65;
    cursor: wait;
    transform: none;
}

/* Nút thoát bài thi */
.btn-exit-header {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    background: #ffffff;
    color: #dc2626;
    border: 1px solid rgba(255,255,255,.55);
    text-decoration: none;
    padding: 9px 17px;
    border-radius: 999px;
    font-weight: 700;
    white-space: nowrap;
    box-shadow: 0 3px 10px rgba(0,0,0,.12);
    transition: background .2s, color .2s, transform .2s;
}

.btn-exit-header:hover {
    background: #dc2626;
    color: #ffffff;
    transform: translateY(-1px);
}

/* Bảng xác nhận thoát bài */
.exit-modal,
.submit-modal {
    display: none;
    position: fixed;
    inset: 0;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(15, 23, 42, .68);
    backdrop-filter: blur(3px);
}

.exit-modal {
    z-index: 10000;
}

.submit-modal {
    z-index: 11000;
}

.exit-modal.show,
.submit-modal.show {
    display: flex;
}

.exit-modal-content,
.submit-modal-content {
    width: 100%;
    max-width: 440px;
    padding: 30px;
    border-radius: 18px;
    background: #ffffff;
    text-align: center;
    box-shadow: 0 22px 65px rgba(0,0,0,.28);
    animation: exitModalAppear .22s ease-out;
}

.exit-modal-icon,
.submit-modal-icon {
    display: grid;
    place-items: center;
    width: 62px;
    height: 62px;
    margin: 0 auto 15px;
    border-radius: 50%;
    font-size: 30px;
}

.exit-modal-icon {
    background: #fff1f2;
    color: #dc2626;
}

.submit-modal-icon {
    background: #dcfce7;
    color: #16a34a;
}

.exit-modal-content h2,
.submit-modal-content h2 {
    margin: 0 0 12px;
    color: #1e293b;
    font-size: 22px;
}

.exit-modal-content p,
.submit-modal-content p {
    margin: 7px 0;
    color: #475569;
    line-height: 1.6;
    font-size: 15px;
}

.exit-modal-content .exit-warning {
    color: #dc2626;
    font-size: 13px;
}

.exit-modal-actions,
.submit-modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.exit-modal-actions button,
.submit-modal-actions button {
    flex: 1;
    min-height: 44px;
    padding: 10px 14px;
    border: 0;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: filter .2s, transform .2s;
}

.exit-modal-actions button:hover,
.submit-modal-actions button:hover {
    filter: brightness(.95);
    transform: translateY(-1px);
}

.btn-stay,
.btn-cancel-submit {
    background: #e2e8f0;
    color: #334155;
}

.btn-confirm-exit {
    background: #dc2626;
    color: #ffffff;
}

.btn-confirm-submit {
    background: #16a34a;
    color: #ffffff;
}

@keyframes exitModalAppear {
    from {
        opacity: 0;
        transform: translateY(10px) scale(.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 800px) {
    .exam-layout {
        grid-template-columns: 1fr;
    }

    .question-sidebar {
        position: static;
        grid-row: 1;
    }

    .question-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
    }

    .container {
        margin: 18px auto;
        padding: 0 12px;
    }

    header {
        padding: 12px 15px;
        flex-wrap: wrap;
    }

    .exam-title {
        font-size: 1rem;
    }

    .timer-box {
        font-size: 13px;
        padding: 7px 10px;
    }

    .btn-exit-header {
        padding: 7px 10px;
        font-size: 13px;
    }

    .exit-modal-actions,
    .submit-modal-actions {
        flex-direction: column;
    }
}
</style>
</head>
<body>

<header>
    <div class="exam-title">
        <?= htmlspecialchars($exam['title'] ?? 'Bài thi', ENT_QUOTES, 'UTF-8') ?>
    </div>

    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <div class="timer-box">
            ⏱️ Thời gian còn lại:
            <span id="timer-display">00:00</span>
        </div>

        <a href="index.php" id="btn-exit" class="btn-exit-header">
            <span aria-hidden="true">↩</span> Thoát
        </a>
    </div>
</header>

<div class="container">
<form id="exam-form" method="POST" action="result.php">

    <input type="hidden" name="action" value="submit_exam">

    <input type="hidden" name="exam_id" value="<?= $exam_id ?>">

    <input
        type="hidden"
        name="completion_time"
        id="completion_time_input"
        value="0"
    >

    <div class="exam-layout">

        <!-- Khu vực hiển thị câu hỏi -->
        <main class="exam-main">

            <?php foreach ($questions_for_client as $index => $q): ?>

            <section
                class="question-card"
                id="question-<?= $index ?>"
            >

                <div class="question-title">
                    Câu <?= $index + 1 ?>:
                    <?= htmlspecialchars($q['question'], ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div class="options-list">

                    <?php foreach ($q['options'] as $option): ?>

                    <label class="option-item">

                        <input
                            type="radio"
                            name="answers[<?= (int) $q['id'] ?>]"
                            value="<?= (int) $option['id'] ?>"
                            style="margin-right:10px"
                        >

                        <strong>
                            <?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?>.
                        </strong>

                        &nbsp;

                        <?= htmlspecialchars($option['content'], ENT_QUOTES, 'UTF-8') ?>

                    </label>

                    <?php endforeach; ?>

                </div>

            </section>

            <?php endforeach; ?>

        </main>

        <!-- Thanh danh sách câu hỏi -->
        <aside class="question-sidebar">

            <div class="question-navigation">

                <h3>Danh sách câu hỏi</h3>

                <div class="question-grid">

                    <?php foreach ($questions_for_client as $index => $q): ?>

                    <button
                        type="button"
                        class="question-number"
                        data-question="<?= $index ?>"
                        aria-label="Đi đến câu <?= $index + 1 ?>"
                    >
                        <?= $index + 1 ?>
                    </button>

                    <?php endforeach; ?>

                </div>

                <div class="question-progress" id="question-progress">
                    Đã làm 0/<?= count($questions_for_client) ?> câu
                </div>

                <!-- Chú thích trạng thái câu hỏi -->
                <div class="question-legend">

                    <span>
                        <i
                            class="legend-box"
                            style="background:#fee2e2"
                        ></i>
                        Chưa làm
                    </span>

                    <span>
                        <i
                            class="legend-box"
                            style="background:#dcfce7"
                        ></i>
                        Đã làm
                    </span>

                    <span>
                        <i
                            class="legend-box"
                            style="background:#dbeafe"
                        ></i>
                        Đang xem
                    </span>

                </div>

                <!-- Nút nộp bài nằm trong thanh danh sách -->
                <div class="submit-exam-container">

                    <button
                        type="button"
                        id="submit-exam-btn"
                        class="submit-exam-btn"
                    >
                        Nộp bài
                    </button>

                </div>

            </div>

        </aside>

    </div>

</form>
</div>

<!-- Bảng xác nhận thoát bài thi -->
<div
    class="exit-modal"
    id="exitModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="exitModalTitle"
    aria-hidden="true"
>

    <div class="exit-modal-content">

        <div class="exit-modal-icon" aria-hidden="true">
            ⚠️
        </div>

        <h2 id="exitModalTitle">
            Xác nhận thoát bài thi
        </h2>

        <p>
            Bạn có chắc chắn muốn thoát khỏi bài thi không?
        </p>

        <p class="exit-warning">
            Lượt làm bài này sẽ được tính và bài làm chưa nộp sẽ không được chấm điểm.
        </p>

        <div class="exit-modal-actions">

            <button
                type="button"
                class="btn-stay"
                id="btnStay"
            >
                Ở lại làm bài
            </button>

            <button
                type="button"
                class="btn-confirm-exit"
                id="btnConfirmExit"
            >
                Thoát bài thi
            </button>

        </div>

    </div>

</div>

<!-- Bảng xác nhận nộp bài -->
<div
    class="submit-modal"
    id="submitModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="submitModalTitle"
    aria-hidden="true"
>
    <div class="submit-modal-content">

        <div class="submit-modal-icon">
            ✓
        </div>

        <h2 id="submitModalTitle">
            Xác nhận nộp bài
        </h2>

        <p>
            Bạn có chắc chắn muốn nộp bài thi không?
        </p>

        <p>
            Sau khi nộp bài, bạn sẽ không thể thay đổi câu trả lời.
        </p>

        <div class="submit-modal-actions">

            <button
                type="button"
                class="btn-cancel-submit"
                id="btnCancelSubmit"
            >
                Tiếp tục làm bài
            </button>

            <button
                type="button"
                class="btn-confirm-submit"
                id="btnConfirmSubmit"
            >
                Xác nhận nộp bài
            </button>

        </div>

    </div>
</div>

<!-- Bảng thông báo câu chưa hoàn thành -->
<div
    id="unansweredModal"
    class="submit-modal"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
>
    <div class="submit-modal-content">

        <div
            class="submit-modal-icon"
            style="background:#fef3c7;color:#d97706;"
        >
            !
        </div>

        <h2>Bạn chưa hoàn thành bài thi!</h2>

        <p id="unansweredMessage">
            Bạn vẫn còn câu hỏi chưa được trả lời.
        </p>

        <div class="submit-modal-actions">

            <button
                type="button"
                id="btnCloseUnanswered"
                class="btn-cancel-submit"
            >
                Tiếp tục làm bài
            </button>

        </div>

    </div>
</div>

<script>
let totalSeconds = <?= (int) $remaining_seconds ?>;

// Mã lượt thi, dùng làm khóa lưu đáp án tạm trong trình duyệt.
const answersStorageKey = 'exam_answers_<?= (int) $attempt_id ?>';

// Các phần tử giao diện
const timerDisplay = document.getElementById('timer-display');

const examForm = document.getElementById('exam-form');

const completionTimeInput = document.getElementById(
    'completion_time_input'
);

// Các phần tử bảng thoát bài
const btnExit = document.getElementById('btn-exit');

const exitModal = document.getElementById('exitModal');

const btnStay = document.getElementById('btnStay');

const btnConfirmExit = document.getElementById('btnConfirmExit');

// Các phần tử bảng nộp bài
const submitBtn = document.getElementById('submit-exam-btn');

const submitModal = document.getElementById('submitModal');

const btnCancelSubmit = document.getElementById('btnCancelSubmit');

const btnConfirmSubmit = document.getElementById('btnConfirmSubmit');

// Các phần tử danh sách câu hỏi
const questionButtons = document.querySelectorAll('.question-number');

const questionCards = document.querySelectorAll('.question-card');

const questionProgress = document.getElementById('question-progress');

const startTime = Date.now();

let currentQuestion = 0;

let isSubmitting = false;

let timerInterval = null;


// Khi quay lại trang làm bài bằng nút Back của trình duyệt,
// tải lại trang để tránh sử dụng giao diện làm bài cũ.
window.addEventListener('pageshow', function(event) {

    const navigation = performance.getEntriesByType('navigation')[0];

    if (
        event.persisted ||
        (navigation && navigation.type === 'back_forward')
    ) {
        window.location.reload();
    }

});


// Tính thời gian đã làm bài.
function calculateTimeSpent() {

    completionTimeInput.value = Math.round(
        (Date.now() - startTime) / 1000
    );

}


// Tìm những câu chưa chọn đáp án.
function getUnansweredQuestions() {

    const unanswered = [];

    questionCards.forEach((card, index) => {

        const selected = card.querySelector(
            'input[type="radio"]:checked'
        );

        if (!selected) {
            unanswered.push(index);
        }

    });

    return unanswered;

}


// Cập nhật màu sắc thanh số câu và số câu đã làm.
function updateQuestionNavigation() {

    let answeredCount = 0;

    questionButtons.forEach((button, index) => {

        const card = questionCards[index];

        const selected = card
            ? card.querySelector('input[type="radio"]:checked')
            : null;

        if (selected) {
            answeredCount++;
        }

        button.classList.toggle('answered', !!selected);

        button.classList.toggle(
            'current',
            index === currentQuestion
        );

        button.setAttribute(
            'aria-label',
            `Câu ${index + 1}: ${selected ? 'đã làm' : 'chưa làm'}`
        );

    });

    questionProgress.textContent =
        `Đã làm ${answeredCount}/${questionCards.length} câu`;

}


// Bấm số câu để cuộn đến câu tương ứng.
questionButtons.forEach((button, index) => {

    button.addEventListener('click', function() {

        currentQuestion = index;

        questionCards[index].scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

        updateQuestionNavigation();

    });

});


// Khi chọn đáp án, cập nhật thanh câu hỏi.
document.querySelectorAll(
    '#exam-form input[type="radio"]'
).forEach(radio => {

    radio.addEventListener('change', function() {

        const card = radio.closest('.question-card');

        if (card) {
            card.classList.remove('missing-answer');
        }

        updateQuestionNavigation();

        saveAnswers();

    });

});


// Lưu đáp án đã chọn vào trình duyệt để không mất khi tải lại trang.
function saveAnswers() {

    const saved = {};

    document.querySelectorAll(
        '#exam-form input[type="radio"]:checked'
    ).forEach(radio => {
        saved[radio.name] = radio.value;
    });

    try {
        localStorage.setItem(answersStorageKey, JSON.stringify(saved));
    } catch (error) {
        // Trình duyệt chặn lưu trữ thì bỏ qua, bài thi vẫn làm bình thường.
    }

}


// Xóa đáp án đã lưu khi lượt thi kết thúc (nộp bài hoặc thoát).
function clearSavedAnswers() {

    try {
        localStorage.removeItem(answersStorageKey);
    } catch (error) {
    }

}


// Khôi phục đáp án đã chọn khi vào lại lượt thi đang làm dở.
(function restoreAnswers() {

    try {

        const saved = JSON.parse(
            localStorage.getItem(answersStorageKey) || '{}'
        );

        Object.keys(saved).forEach(name => {

            const radio = document.querySelector(
                '#exam-form input[type="radio"][name="' +
                CSS.escape(name) + '"][value="' +
                CSS.escape(String(saved[name])) + '"]'
            );

            if (radio) {
                radio.checked = true;
            }

        });

    } catch (error) {
    }

    updateQuestionNavigation();

})();


// Kiểm tra câu bỏ trống trước khi nộp.
function validateAllAnswers() {

    const unanswered = getUnansweredQuestions();

    if (unanswered.length === 0) {
        return true;
    }

    // Lấy số thứ tự các câu chưa làm
    const questionNumbers = unanswered.map(index => index + 1);

    // Hiển thị nội dung thông báo
    document.getElementById('unansweredMessage').textContent =
        `Bạn vẫn còn ${unanswered.length} câu hỏi chưa được trả lời: ` +
        `Câu ${questionNumbers.join(', ')}. ` +
        `Vui lòng hoàn thành các câu hỏi còn thiếu trước khi nộp bài.`;

    // Hiển thị bảng thông báo
    document.getElementById('unansweredModal').classList.add('show');

    // Đánh dấu câu chưa làm
    unanswered.forEach(index => {
        questionButtons[index].classList.add('unanswered-warning');
    });

    return false;
}
// Đóng bảng xác nhận nộp bài.
function closeSubmitModal() {

    submitModal.classList.remove('show');

    submitModal.setAttribute('aria-hidden', 'true');

}


// Mở bảng xác nhận nộp bài.
function openSubmitModal() {

    submitModal.classList.add('show');

    submitModal.setAttribute('aria-hidden', 'false');

    btnCancelSubmit.focus();

}


// Gửi bài lên result.php.
function submitExam(forceSubmit = false) {

    if (isSubmitting) {
        return;
    }

    // Không cho nộp nếu còn câu bỏ trống,
    // trừ trường hợp hết giờ tự động nộp.
    if (!forceSubmit && !validateAllAnswers()) {
        return;
    }

    isSubmitting = true;

    calculateTimeSpent();

    if (submitBtn) {

        submitBtn.disabled = true;

        submitBtn.textContent = 'Đang nộp bài...';

    }

    if (btnConfirmSubmit) {

        btnConfirmSubmit.disabled = true;

        btnConfirmSubmit.textContent = 'Đang nộp bài...';

    }

    fetch('result.php', {

        method: 'POST',

        body: new FormData(examForm)

    })

    .then(response => {

        if (!response.ok) {

            throw new Error(
                'Máy chủ trả về lỗi HTTP ' + response.status
            );

        }

        return response.json();

    })

    .then(data => {

        if (data.success && data.redirect_url) {

            clearSavedAnswers();

            window.location.replace(data.redirect_url);

        } else {

            alert(data.message || 'Có lỗi xảy ra khi nộp bài.');

            isSubmitting = false;

            submitBtn.disabled = false;

            submitBtn.textContent = 'Nộp bài';

            btnConfirmSubmit.disabled = false;

            btnConfirmSubmit.textContent = 'Xác nhận nộp bài';

        }

    })

    .catch(error => {

        console.error(error);

        alert(
            'Không thể kết nối đến máy chủ. ' +
            'Vui lòng kiểm tra lại và thử nộp bài.'
        );

        isSubmitting = false;

        submitBtn.disabled = false;

        submitBtn.textContent = 'Nộp bài';

        btnConfirmSubmit.disabled = false;

        btnConfirmSubmit.textContent = 'Xác nhận nộp bài';

    });

}


// Bấm nút Nộp bài trên thanh danh sách câu hỏi.
submitBtn.addEventListener('click', function() {

    if (isSubmitting) {
        return;
    }

    // Nếu còn câu chưa làm, hiện bảng thông báo
    if (!validateAllAnswers()) {
        return;
    }

    // Nếu đã làm hết, hiện bảng xác nhận nộp bài
    openSubmitModal();

});
// =====================================
// XỬ LÝ BẢNG THÔNG BÁO CÂU CHƯA LÀM
// =====================================

const unansweredModal = document.getElementById('unansweredModal');

const btnCloseUnanswered = document.getElementById('btnCloseUnanswered');

// Bấm Tiếp tục làm bài để đóng bảng thông báo
btnCloseUnanswered.addEventListener('click', function(event) {

    event.preventDefault();

    // Đóng bảng thông báo
    unansweredModal.classList.remove('show');

    unansweredModal.setAttribute('aria-hidden', 'true');

    // Xóa hiệu ứng cảnh báo ở các câu chưa làm
    questionButtons.forEach(button => {
        button.classList.remove('unanswered-warning');
    });

    // Không nộp bài, không khóa giao diện
    isSubmitting = false;

});

// Bấm Tiếp tục làm bài.
btnCancelSubmit.addEventListener('click', function() {

    closeSubmitModal();

    submitBtn.focus();

});


// =====================================
// XÁC NHẬN NỘP BÀI
// =====================================

btnConfirmSubmit.addEventListener('click', function() {

    if (isSubmitting) {
        return;
    }

    closeSubmitModal();

    submitExam(false);

});

// Bấm vùng tối bên ngoài bảng xác nhận để đóng.
submitModal.addEventListener('click', function(event) {

    if (event.target === submitModal) {

        closeSubmitModal();

        submitBtn.focus();

    }

});


// Mở bảng xác nhận thoát khi bấm nút Thoát.
btnExit.addEventListener('click', function(event) {

    event.preventDefault();

    if (isSubmitting) {
        return;
    }

    exitModal.classList.add('show');

    exitModal.setAttribute('aria-hidden', 'false');

    btnStay.focus();

});


// Đóng bảng xác nhận thoát.
function closeExitModal() {

    exitModal.classList.remove('show');

    exitModal.setAttribute('aria-hidden', 'true');

    btnExit.focus();

}


// Chọn ở lại: đóng bảng và tiếp tục làm bài.
btnStay.addEventListener('click', closeExitModal);


// Xác nhận thoát: chuyển về trang chủ, không tự nộp bài.
btnConfirmExit.addEventListener('click', function() {

    // Báo cho máy chủ kết thúc lượt làm bài (lượt vẫn được tính).
    const exitData = new FormData();
    exitData.append('action', 'abandon_exam');
    exitData.append('exam_id', examForm.elements['exam_id'].value);

    btnConfirmExit.disabled = true;

    fetch('result.php', {
        method: 'POST',
        body: exitData,
        keepalive: true
    })
    .catch(() => {})
    .finally(() => {
        clearSavedAnswers();
        window.location.href = btnExit.href;
    });

});


// Bấm vùng tối bên ngoài bảng thoát để đóng.
exitModal.addEventListener('click', function(event) {

    if (event.target === exitModal) {

        closeExitModal();

    }

});


// Nhấn ESC để đóng bảng đang mở.
document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        if (submitModal.classList.contains('show')) {

            closeSubmitModal();

            submitBtn.focus();

        } else if (exitModal.classList.contains('show')) {

            closeExitModal();

        }

    }

});


// Đồng hồ đếm ngược dựa trên mốc thời gian thực.
const examDeadline = Date.now() + (totalSeconds * 1000);

let timeExpiredHandled = false;


function updateTimer() {

    const remainingMilliseconds = Math.max(
        0,
        examDeadline - Date.now()
    );

    totalSeconds = Math.ceil(
        remainingMilliseconds / 1000
    );

    const minutes = Math.floor(totalSeconds / 60);

    const seconds = totalSeconds % 60;

    timerDisplay.textContent =
        String(minutes).padStart(2, '0') + ':' +
        String(seconds).padStart(2, '0');

    if (totalSeconds <= 0 && !timeExpiredHandled) {

        timeExpiredHandled = true;

        clearInterval(timerInterval);

        alert(
            'Đã hết giờ làm bài! ' +
            'Hệ thống sẽ tự động nộp bài với các đáp án hiện có.'
        );

        // Khi hết giờ, tự động nộp kể cả còn câu chưa làm.
        closeSubmitModal();

        submitExam(true);

    }

}


// Khởi tạo giao diện và đồng hồ.
updateQuestionNavigation();

updateTimer();

timerInterval = setInterval(updateTimer, 250);

</script>

</body>
</html>