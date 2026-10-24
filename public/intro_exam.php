<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Lấy ID đề thi từ URL
$exam_id = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);

if (!$exam_id || $exam_id <= 0) {
    die('Đề thi không hợp lệ. Vui lòng quay lại danh sách đề thi.');
}

// Lấy thông tin đề thi
$stmt = $conn->prepare("
    SELECT exam_id, title, grade, duration, total_questions, max_attempts
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

if ($userId !== null) {
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

// Số lượt làm tối đa
$max_attempts = 1;

// Đếm số lượt học sinh đã làm đề thi này
$sql = "
    SELECT COUNT(*)
    FROM exam_results
    WHERE user_id = :user_id
      AND exam_id = :exam_id
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':user_id' => $_SESSION['user_id'],
    ':exam_id' => $exam_id
]);

$attempt_count = (int) $stmt->fetchColumn();

// Đếm số câu hỏi thực tế trong đề
$stmtCount = $conn->prepare("
    SELECT COUNT(*)
    FROM exam_questions
    WHERE exam_id = :exam_id
");
$stmtCount->execute(['exam_id' => $exam_id]);
$questionCount = (int) $stmtCount->fetchColumn();

// Dùng số câu hỏi thực tế; nếu bảng liên kết không có câu hỏi,
// thông báo để người dùng không vào một bài thi trống.
$title = $exam['title'] ?? 'Bài thi Lịch sử';
$grade = $exam['grade'] ?? '';
$duration = max(1, (int) ($exam['duration'] ?? 1));
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

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f3f6fb;
            color: #203247;
            min-height: 100vh;
        }

        .topbar {
            background: #173653;
            color: #fff;
            padding: 18px 24px;
        }

        .topbar-inner {
            max-width: 1000px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            font-size: 22px;
            font-weight: 800;
        }

        .brand span {
            color: #f2c879;
        }

        .topbar small {
            display: block;
            margin-top: 4px;
            color: #d7e3ef;
        }

        .page {
            max-width: 760px;
            margin: 38px auto;
            padding: 0 18px 40px;
        }

        .back-link {
            display: inline-block;
            color: #536b83;
            text-decoration: none;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .back-link:hover {
            color: #173653;
        }

        .exam-card {
            background: #fff;
            border: 1px solid #e1e8f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 12px 35px rgba(23, 54, 83, .08);
        }

        .hero {
            background: linear-gradient(135deg, #173653, #28577c);
            color: #fff;
            padding: 32px 28px;
            text-align: center;
        }

        .grade-badge {
            display: inline-block;
            background: #fff0d4;
            color: #805719;
            border-radius: 30px;
            padding: 7px 15px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .hero h1 {
            font-size: clamp(24px, 4vw, 32px);
            overflow-wrap: anywhere;
            margin-bottom: 10px;
        }

        .hero p {
            color: #dce9f4;
            line-height: 1.6;
        }

        .content {
            padding: 26px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 27px;
        }

        .info-item {
            padding: 19px;
            background: #f5f8fc;
            border: 1px solid #e8eef5;
            border-radius: 14px;
        }

        .info-label {
            color: #687c90;
            font-size: 14px;
            margin-bottom: 9px;
        }

        .info-value {
            font-size: 23px;
            font-weight: 800;
            color: #173653;
        }

        .section-title {
            font-size: 18px;
            margin-bottom: 16px;
        }

        .rules {
            display: grid;
            gap: 17px;
            margin-bottom: 25px;
        }

        .rule {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.5;
        }

        .rule-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            background: #eaf3ff;
            font-size: 18px;
        }

        .rule strong {
            display: block;
            margin-bottom: 3px;
        }

        .rule p {
            color: #687c90;
            font-size: 14px;
        }

        .notice {
            background: #fff8e9;
            color: #785a20;
            border: 1px solid #f2dfb6;
            border-radius: 12px;
            padding: 13px 15px;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 22px;
        }

        .confirm {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
            margin-bottom: 22px;
            cursor: pointer;
        }

        .confirm input {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            accent-color: #173653;
            flex-shrink: 0;
        }

        .actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            min-height: 49px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            padding: 13px 20px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: .2s;
        }

        .btn-back {
            flex: 1;
            border: 1px solid #d7e0ea;
            background: #fff;
            color: #536b83;
        }

        .btn-start {
            flex: 2;
            border: none;
            background: #173653;
            color: #fff;
        }

        .btn-start:hover:not(:disabled) {
            background: #245276;
            transform: translateY(-1px);
        }

        .btn-start:disabled {
            background: #aab8c6;
            cursor: not-allowed;
        }

        .empty {
            text-align: center;
            color: #a33a3a;
            background: #fff0f0;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        @media (max-width: 520px) {
            .topbar-inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .page {
                margin-top: 22px;
            }

            .hero {
                padding: 26px 18px;
            }

            .content {
                padding: 20px 17px;
            }

            .info-grid {
                gap: 10px;
            }

            .info-item {
                padding: 14px;
            }

            .info-value {
                font-size: 20px;
            }

            .actions {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<header class="topbar">
    <div class="topbar-inner">
        <div>
            <div class="brand">Test<span>SuViet</span>.com</div>
            <small>Hệ thống thi trắc nghiệm Lịch sử THCS</small>
        </div>
    </div>
</header>

<main class="page">
    <a class="back-link" href="index.php">← Quay lại danh sách đề thi</a>

    <section class="exam-card">
        <div class="hero">
            <div class="grade-badge">
                <?= $grade !== '' ? 'Lớp ' . $h($grade) : 'Đề thi Lịch sử' ?>
            </div>

            <h1><?= $h($title) ?></h1>
            <p>Vui lòng kiểm tra thông tin trước khi bắt đầu làm bài.</p>
        </div>

        <div class="content">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">⏱ Thời gian làm bài</div>
                    <div class="info-value"><?= $duration ?> phút</div>
                </div>

                <div class="info-item">
                    <div class="info-label">👥 Số lượt làm tối đa</div>
                    <div class="info-value">
                        <?= htmlspecialchars($attemptsText, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">📝 Số câu hỏi</div>
                    <div class="info-value"><?= $questionCount ?> câu</div>
                </div>
            </div>

            <?php if ($questionCount === 0): ?>
                <div class="empty">
                    Đề thi này chưa có câu hỏi. Vui lòng chọn đề khác hoặc liên hệ giáo viên.
                </div>
            <?php endif; ?>

            <h2 class="section-title">Lưu ý trước khi thi</h2>

            <div class="rules">
                <div class="rule">
                    <div class="rule-icon">📖</div>
                    <div>
                        <strong>Đọc kỹ từng câu hỏi</strong>
                        <p>Chọn đáp án phù hợp nhất cho mỗi câu hỏi.</p>
                    </div>
                </div>

                <div class="rule">
                    <div class="rule-icon">⏳</div>
                    <div>
                        <strong>Chú ý thời gian</strong>
                        <p>Thời gian làm bài được giới hạn theo thông tin của đề thi.</p>
                    </div>
                </div>

                <div class="rule">
                    <div class="rule-icon">📨</div>
                    <div>
                        <strong>Kiểm tra trước khi nộp</strong>
                        <p>Hãy kiểm tra các lựa chọn trước khi nộp bài thi.</p>
                    </div>
                </div>
            </div>

            <div class="notice">
                ⚠️ Khi bắt đầu, bạn sẽ được chuyển đến trang làm bài.
                Hãy chỉ bắt đầu khi bạn đã sẵn sàng.
            </div>

            <form action="take_exam.php" method="GET" id="start-form">
                <input
                    type="hidden"
                    name="exam_id"
                    value="<?= (int) $exam['exam_id'] ?>"
                >

                <label class="confirm">
                    <input type="checkbox" id="ready-check">
                    <span>Tôi đã đọc thông tin và sẵn sàng làm bài thi.</span>
                </label>

                <div class="actions">
                    <a href="index.php" class="btn btn-back">Quay lại</a>

                    <button
                        type="submit"
                        class="btn btn-start"
                        id="start-button"
                        disabled
                    >
                        Bắt đầu thi →
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

    readyCheck.addEventListener('change', function () {
        startButton.disabled = !this.checked || questionCount <= 0;
    });

    document.getElementById('start-form').addEventListener('submit', function (event) {
        if (!readyCheck.checked || questionCount <= 0) {
            event.preventDefault();
            alert('Vui lòng xác nhận đã sẵn sàng và kiểm tra đề thi có câu hỏi.');
        }
    });
</script>
</body>
</html>
