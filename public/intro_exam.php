<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/exam_attempts.php';

$exam_id = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);

if (!$exam_id || $exam_id <= 0) {
    die('Đề thi không hợp lệ. Vui lòng quay lại danh sách đề thi.');
}

if (isset($_SESSION['exam_completed_' . $exam_id])) {
    unset($_SESSION['exam_completed_' . $exam_id]);
}

$stmt = $conn->prepare("
    SELECT 
        exam_id,
        title,
        grade,
        visibility,
        school_name,
        target_classes,
        duration,
        total_questions,
        max_attempts,
        start_time,
        end_time,
        user_id
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

// Kiểm tra quyền truy cập đề thi theo phạm vi (PUBLIC / SCHOOL / GRADE / CLASS)
$examVis   = !empty($exam['visibility']) ? $exam['visibility'] : 'PUBLIC';
$uidCheck  = (int)($_SESSION['user_id'] ?? 0);
$roleCheck = strtoupper((string)($_SESSION['role'] ?? ''));

if ($examVis !== 'PUBLIC' && $roleCheck !== 'ADMIN' && (int)$exam['user_id'] !== $uidCheck) {
    if ($uidCheck <= 0) {
        header('Location: login.php');
        exit;
    }

    $stmtUserScope = $conn->prepare("SELECT school_name, class_name FROM users WHERE user_id = ? LIMIT 1");
    $stmtUserScope->execute([$uidCheck]);
    $uScope = $stmtUserScope->fetch(PDO::FETCH_ASSOC) ?: [];

    $uSchool = mb_strtolower(trim((string)($uScope['school_name'] ?? '')), 'UTF-8');
    $uClass  = mb_strtolower(preg_replace('/\s+/', '', preg_replace('/^lớp\s*/iu', '', trim((string)($uScope['class_name'] ?? '')))), 'UTF-8');
    $eSchool = mb_strtolower(trim((string)($exam['school_name'] ?? '')), 'UTF-8');

    $studentGrade = 0;
    if (preg_match('/^([6-9])/', $uClass, $m)) {
        $studentGrade = (int)$m[1];
    }

    $schoolOk = ($uSchool === '' || $eSchool === '' || $uSchool === $eSchool);

    $canAccess = false;
    if ($examVis === 'SCHOOL') {
        $canAccess = ($uSchool !== '' && $uSchool === $eSchool);
    } elseif ($examVis === 'GRADE') {
        $canAccess = ($studentGrade > 0 && $studentGrade === (int)$exam['grade'] && $schoolOk);
    } elseif ($examVis === 'CLASS') {
        $targetList = array_map(
            fn($c) => mb_strtolower(preg_replace('/\s+/', '', preg_replace('/^lớp\s*/iu', '', trim($c))), 'UTF-8'),
            explode(',', (string)($exam['target_classes'] ?? ''))
        );
        $canAccess = ($uClass !== '' && in_array($uClass, $targetList, true) && $schoolOk);
    }

    if (!$canAccess) {
        http_response_code(403);
        die('<div style="padding:24px;font-family:sans-serif;color:#991b1b;background:#fee2e2;border-radius:8px;margin:20px;">
                ⛔ Bạn không thuộc trường, khối hoặc lớp được phép tham gia đề thi này.
             </div>');
    }
}

// Bỏ giới hạn số lượt làm đề thi
$maxAttempts = 0;

$userId = $_SESSION['user_id'] ?? null;
$attemptCount = 0;
$hasInProgress = false;

if ($userId !== null) {
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

$attemptsText = $attemptCount . ' lượt đã làm (Không giới hạn)';

$stmtAttempts = $conn->prepare("
    SELECT COUNT(*)
    FROM exam_results
    WHERE exam_id = :exam_id
");

$stmtAttempts->execute([
    'exam_id' => $exam_id
]);

$totalAttempts = (int) $stmtAttempts->fetchColumn();

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

$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));

$startTime = !empty($exam['start_time'])
    ? new DateTimeImmutable($exam['start_time'], new DateTimeZone('Asia/Ho_Chi_Minh'))
    : null;

$endTime = !empty($exam['end_time'])
    ? new DateTimeImmutable($exam['end_time'], new DateTimeZone('Asia/Ho_Chi_Minh'))
    : null;

$examNotOpened = $startTime !== null && $now < $startTime;
$examClosed = $endTime !== null && $now >= $endTime;

$canStartExam = !$examNotOpened
    && $questionCount > 0
    && ($hasInProgress || !$examClosed);

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
        <div class="ex-brand"><span>EXAM365</span>.com</div>
        <small class="ex-tagline">Hệ thống thi trắc nghiệm THCS</small>
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
                <div class="ex-info-item is-wide">
                    <dt>Thời gian làm bài</dt>
                    <dd><?= $duration ?> phút</dd>
                </div>

                <div class="ex-info-item is-wide">
                    <dt>Số câu hỏi</dt>
                    <dd><?= $questionCount ?> câu</dd>
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