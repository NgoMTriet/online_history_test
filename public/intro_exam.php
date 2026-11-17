
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/exam_attempts.php';

$exam_id = (int)($_GET['exam_id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($exam_id <= 0 || $user_id <= 0) {
    header('Location: login.php');
    exit;
}

// Lấy thông tin đề thi
$stmt = $conn->prepare("
    SELECT exam_id, title, grade, duration, total_questions,
           start_time, end_time
    FROM exam
    WHERE exam_id = ?
");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    echo "Không tìm thấy đề thi.";
    exit;
}

// Xóa trạng thái hoàn thành cũ trong session
unset($_SESSION['exam_completed_' . $exam_id]);

// Đếm tổng số lượt thi của học sinh
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM exam_results
    WHERE user_id = ? AND exam_id = ?
");
$stmt->execute([$user_id, $exam_id]);
$attemptCount = (int)$stmt->fetchColumn();

// Đóng những lượt thi đã hết thời gian
close_expired_attempts($conn);

// Kiểm tra học sinh có bài thi đang làm dở không
$inProgressAttempt = find_in_progress_attempt(
    $conn,
    $user_id,
    $exam_id
);

$hasInProgress = !empty($inProgressAttempt);

// Đếm số câu hỏi thực tế trong đề thi
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM exam_questions
    WHERE exam_id = ?
");
$stmt->execute([$exam_id]);
$questionCount = (int)$stmt->fetchColumn();

// Kiểm tra thời gian mở và đóng đề
$now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));

$examNotOpened = false;
$examClosed = false;

if (!empty($exam['start_time'])) {
    $startTime = new DateTime($exam['start_time'], new DateTimeZone('Asia/Ho_Chi_Minh'));
    $examNotOpened = $now < $startTime;
}

if (!empty($exam['end_time'])) {
    $endTime = new DateTime($exam['end_time'], new DateTimeZone('Asia/Ho_Chi_Minh'));
    $examClosed = $now > $endTime;
}

// Cho phép thi không giới hạn số lần
$canStartExam = !$examNotOpened
    && $questionCount > 0
    && ($hasInProgress || !$examClosed);

// Thông tin số lượt đã thi, chỉ để hiển thị
$attemptsText = $attemptCount . ' lượt đã thi (Không giới hạn)';

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giới thiệu đề thi</title>

    <link rel="stylesheet" href="assets/css/intro_exam.css">
</head>

<body>

<div class="container">

    <h1><?php echo htmlspecialchars($exam['title']); ?></h1>

    <div class="exam-info">
        <p>
            <strong>Khối lớp:</strong>
            <?php echo htmlspecialchars((string)$exam['grade']); ?>
        </p>

        <p>
            <strong>Thời gian làm bài:</strong>
            <?php echo (int)$exam['duration']; ?> phút
        </p>

        <p>
            <strong>Số câu hỏi:</strong>
            <?php echo (int)$questionCount; ?> câu
        </p>

        <p>
            <strong>Số lượt đã thi:</strong>
            <?php echo htmlspecialchars($attemptsText); ?>
        </p>
    </div>

    <?php if ($hasInProgress): ?>

        <div class="alert alert-info">
            Bạn đang có một bài thi chưa hoàn thành.
            Khi nhấn bắt đầu, hệ thống sẽ tiếp tục bài thi đó.
        </div>

    <?php endif; ?>

    <?php if ($examNotOpened): ?>

        <div class="alert alert-warning">
            Đề thi chưa đến thời gian mở.
        </div>

    <?php elseif ($examClosed && !$hasInProgress): ?>

        <div class="alert alert-danger">
            Đề thi đã kết thúc, bạn không thể bắt đầu lượt thi mới.
        </div>

    <?php elseif ($questionCount <= 0): ?>

        <div class="alert alert-danger">
            Đề thi hiện chưa có câu hỏi.
        </div>

    <?php else: ?>

        <div class="alert alert-success">
            Bạn có thể bắt đầu làm bài thi.
        </div>

    <?php endif; ?>

    <?php if ($questionCount > 0 && !$examNotOpened && (!$examClosed || $hasInProgress)): ?>

        <form action="take_exam.php" method="GET" id="startExamForm">
            <input type="hidden" name="exam_id"
                   value="<?php echo $exam_id; ?>">

            <div class="confirm-box">
                <label>
                    <input type="checkbox" id="confirmCheck">
                    Tôi đã đọc thông tin và sẵn sàng bắt đầu làm bài.
                </label>
            </div>

            <button type="submit" id="startButton" disabled>
                <?php echo $hasInProgress ? 'Tiếp tục làm bài' : 'Bắt đầu thi'; ?>
            </button>
        </form>

    <?php endif; ?>

    <a href="index.php" class="back-button">Quay lại</a>

</div>

<script>
const confirmCheck = document.getElementById('confirmCheck');
const startButton = document.getElementById('startButton');

if (confirmCheck && startButton) {
    confirmCheck.addEventListener('change', function () {
        startButton.disabled = !this.checked;
    });
}
</script>

</body>
</html>
