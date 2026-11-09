<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/exam_attempts.php';

// Lấy ID đề thi từ URL
$exam_id = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);

if (!$exam_id || $exam_id <= 0) {
    die('Đề thi không hợp lệ. Vui lòng quay lại danh sách đề thi.');
}

// 🔄 XÓA CỜ HOÀN THÀNH KHI VÀO TRANG GIỚI THIỆU ĐỂ SẴN SÀNG CHO LƯỢT THI MỚI
if (isset($_SESSION['exam_completed_' . $exam_id])) {
    unset($_SESSION['exam_completed_' . $exam_id]);
}

// Lấy thông tin đề thi
$stmt = $conn->prepare("
    SELECT 
        exam_id,
        title,
        grade,
        duration,
        total_questions,
        max_attempts,
        start_time,
        end_time
    FROM exam
    WHERE exam_id = :exam_id
    LIMIT 1
");
$stmt->execute(['exam_id' => $exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    http_response_code(404);
    die('Không tìm thấy đề thi.');
}

// Lấy số lượt làm bài tối đa
$maxAttempts = (int)($exam['max_attempts'] ?? 0);

// Đếm số lượt học sinh hiện tại đã làm đề thi này
$userId = $_SESSION['user_id'] ?? null;
$attemptCount = 0;
$hasInProgress = false;

if ($userId !== null) {
    // Đóng các lượt làm dở đã quá hạn, rồi kiểm tra học viên có lượt đang làm không.
    close_expired_attempts($conn);
    $hasInProgress = find_in_progress_attempt($conn, (int) $userId, $exam_id) !== null;

    $stmtAttempts = $conn->prepare("
        SELECT COUNT(*)
        FROM exam_results
        WHERE user_id = :user_id
          AND exam_id = :exam_id
    ");

    $stmtAttempts->execute([
        'user_id' => $userId,
        'exam_id' => $exam_id
    ]);

    $attemptCount = (int) $stmtAttempts->fetchColumn();
}

// Tạo nội dung hiển thị
$attemptsText = $maxAttempts === 0
    ? $attemptCount . ' lượt đã làm (Không giới hạn)'
    : $attemptCount . '/' . $maxAttempts . ' lượt';

// Đếm tổng số lượt làm bài của đề thi
$stmtAttempts = $conn->prepare("
    SELECT COUNT(*)
    FROM exam_results
    WHERE exam_id = :exam_id
");

$stmtAttempts->execute([
    'exam_id' => $exam_id
]);

$totalAttempts = (int) $stmtAttempts->fetchColumn();

// Đếm số câu hỏi thực tế trong đề
$stmtCount = $conn->prepare("
    SELECT COUNT(*)
    FROM exam_questions
    WHERE exam_id = :exam_id
");
$stmtCount->execute(['exam_id' => $exam_id]);
$questionCount = (int) $stmtCount->fetchColumn();

$title = $exam['title'] ?? 'Bài thi Lịch sử';
$grade = $exam['grade'] ?? '';
$duration = max(1, (int) ($exam['duration'] ?? 1));
// Lấy thời gian hiện tại theo múi giờ Việt Nam
$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));

// Lấy thời gian mở đề
$startTime = !empty($exam['start_time'])
    ? new DateTimeImmutable($exam['start_time'], new DateTimeZone('Asia/Ho_Chi_Minh'))
    : null;

// Lấy thời gian khóa đề
$endTime = !empty($exam['end_time'])
    ? new DateTimeImmutable($exam['end_time'], new DateTimeZone('Asia/Ho_Chi_Minh'))
    : null;

// Kiểm tra trạng thái đề thi
$examNotOpened = $startTime !== null && $now < $startTime;

$examClosed = $endTime !== null && $now >= $endTime;

// Kiểm tra số lượt làm bài
$hasAttemptsLeft = $maxAttempts === 0 || $attemptCount < $maxAttempts;

// Kiểm tra học sinh có được bắt đầu thi không
// Lượt đang làm dở được tiếp tục dù đề đã khóa hoặc đã dùng hết lượt.
$canStartExam = !$examNotOpened
    && $questionCount > 0
    && ($hasInProgress || (!$examClosed && $hasAttemptsLeft));

// Định dạng thời gian hiển thị
$startTimeText = $startTime
    ? $startTime->format('d/m/Y H:i')
    : 'Không thiết lập';

$endTimeText = $endTime
    ? $endTime->format('d/m/Y H:i')
    : 'Không thiết lập';
$h = static fn($value) => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin đề thi - <?= $h($title) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600&family=Noto+Serif:wght@700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/../assets/css/intro_exam.css">
</head>

<body class="ex-body">
<header class="ex-topbar">
    <div class="ex-topbar-inner">
        <div class="ex-brand"><span>SuViet</span>.com</div>
        <small class="ex-tagline">Hệ thống thi trắc nghiệm Lịch sử THCS</small>
    </div>
</header>

<main class="ex-page">

    <section class="ex-card">
        <div class="ex-hero">
            <div class="ex-stamp">
                <?= $grade !== '' ? 'Lớp ' . $h($grade) : 'Đề thi Lịch sử' ?>
            </div>

            <h1 class="ex-title"><?= $h($title) ?></h1>
            <p>Vui lòng kiểm tra thông tin trước khi bắt đầu làm bài.</p>
        </div>

        <div class="ex-content">
            <dl class="ex-info">
                <div class="ex-info-item">
                    <dt>Thời gian làm bài</dt>
                    <dd><?= $duration ?> phút</dd>
                </div>

                <div class="ex-info-item">
                    <dt>Số câu hỏi</dt>
                    <dd><?= $questionCount ?> câu</dd>
                </div>

                <div class="ex-info-item is-full">
                    <dt>Lượt làm bài</dt>
                    <dd class="is-small"><?= $h($attemptsText) ?></dd>
                </div>

                <div class="ex-info-item is-wide">
                    <dt>Thời gian mở đề</dt>
                    <dd class="is-small"><?= $h($startTimeText) ?></dd>
                </div>

                <div class="ex-info-item is-wide">
                    <dt>Thời gian khóa đề</dt>
                    <dd class="is-small"><?= $h($endTimeText) ?></dd>
                </div>
            </dl>

            <?php if ($hasInProgress): ?>

                <div class="ex-notice ex-notice--info" role="status">
                    Bạn đang có một lượt làm bài chưa hoàn thành. Bấm "Tiếp tục làm bài"
                    để quay lại, thời gian còn lại vẫn được tính từ lúc bạn bắt đầu.
                </div>

            <?php elseif ($examNotOpened): ?>

                <div class="ex-notice ex-notice--error" role="alert">
                    Đề thi chưa mở. Vui lòng quay lại vào lúc
                    <?= $h($startTimeText) ?>.
                </div>

            <?php elseif ($examClosed): ?>

                <div class="ex-notice ex-notice--error" role="alert">
                    Đề thi đã kết thúc vào lúc
                    <?= $h($endTimeText) ?>.
                    Bạn không thể bắt đầu lượt thi mới.
                </div>

            <?php elseif (!$hasAttemptsLeft): ?>

                <div class="ex-notice ex-notice--error" role="alert">
                    Bạn đã sử dụng hết số lượt làm bài cho phép.
                </div>

            <?php elseif ($questionCount > 0): ?>

                <div class="ex-notice ex-notice--ok" role="status">
                    Đề thi hiện đang mở. Bạn có thể bắt đầu làm bài.
                </div>

            <?php endif; ?>

            <?php if ($questionCount === 0): ?>
                <div class="ex-notice ex-notice--error" role="alert">
                    Đề thi này chưa có câu hỏi. Vui lòng chọn đề khác hoặc liên hệ giáo viên.
                </div>
            <?php endif; ?>

            <h2 class="ex-section-title">Lưu ý trước khi thi</h2>

            <ul class="ex-rules">
                <li class="ex-rule">
                    <div>
                        <strong>Đọc kỹ từng câu hỏi</strong>
                        <p>Chọn đáp án phù hợp nhất cho mỗi câu hỏi.</p>
                    </div>
                </li>

                <li class="ex-rule">
                    <div>
                        <strong>Chú ý thời gian</strong>
                        <p>Thời gian làm bài được giới hạn theo thông tin của đề thi.</p>
                    </div>
                </li>

                <li class="ex-rule">
                    <div>
                        <strong>Kiểm tra trước khi nộp</strong>
                        <p>Hãy kiểm tra các lựa chọn trước khi nộp bài thi.</p>
                    </div>
                </li>
            </ul>

            <div class="ex-notice ex-notice--warn">
                Khi bắt đầu, bạn sẽ được chuyển đến trang làm bài.
                Hãy chỉ bắt đầu khi bạn đã sẵn sàng.
                Lượt làm bài được tính ngay khi bắt đầu, kể cả khi bạn thoát giữa chừng.
            </div>

            <form action="take_exam.php" method="GET" id="start-form">
                <input
                    type="hidden"
                    name="exam_id"
                    value="<?= (int) $exam['exam_id'] ?>"
                >

                <label class="ex-confirm">
                    <input type="checkbox" id="ready-check">
                    <span>Tôi đã đọc thông tin và sẵn sàng làm bài thi.</span>
                </label>

                <div class="ex-actions">
                    <a href="index.php" class="ex-btn ex-btn-back">Quay lại</a>

                    <button
                        type="submit"
                        class="ex-btn ex-btn-start"
                        id="start-button"
                        disabled
                    >
                        <?= $hasInProgress ? 'Tiếp tục làm bài →' : 'Bắt đầu thi →' ?>
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
    const readyCheck = document.getElementById('ready-check');
    const startButton = document.getElementById('start-button');

    const questionCount = <?= $questionCount ?>;
    const canStartExam = <?= $canStartExam ? 'true' : 'false' ?>;

    readyCheck.addEventListener('change', function () {
        startButton.disabled = !this.checked
            || questionCount <= 0
            || !canStartExam;
    });

    document.getElementById('start-form').addEventListener('submit', function (event) {
        if (!readyCheck.checked || questionCount <= 0 || !canStartExam) {
            event.preventDefault();

            alert('Hiện tại bạn chưa thể bắt đầu bài thi.');
        }
    });
</script>
</body>
</html>