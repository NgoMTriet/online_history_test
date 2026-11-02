<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$message = '';
$isSuccess = false;

if (isset($_GET['sent'])) {
    $message = 'Nếu email đã đăng ký, hệ thống sẽ gửi liên kết đặt lại mật khẩu.';
    $isSuccess = true;
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] === 'email') {
        $message = 'Vui lòng nhập địa chỉ email hợp lệ.';
    } elseif ($_GET['error'] === 'mail') {
        $message = 'Không gửi được email. Vui lòng thử lại sau.';
    } elseif ($_GET['error'] === 'token') {
        $message = 'Liên kết không hợp lệ hoặc đã hết hạn.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - TestSuViet.com</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600&family=Noto+Serif:wght@700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="au-body">

<a class="au-brand" href="index.php"><span class="au-brand-mark">vn</span>TestSuViet.com</a>

<main class="au-card">
    <h1 class="au-title">Quên mật khẩu</h1>
    <p class="au-desc">Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết để bạn đặt lại mật khẩu.</p>

    <?php if ($message !== ''): ?>
        <div
            class="au-notice<?= $isSuccess ? ' au-notice-success' : '' ?>"
            role="<?= $isSuccess ? 'status' : 'alert' ?>"
        >
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="../api/auth.php?action=forgot">
        <div class="au-field">
            <label for="email">Email đã đăng ký</label>
            <input
                id="email"
                type="email"
                name="email"
                placeholder="ten@example.com"
                autocomplete="email"
                required
            >
        </div>

        <button class="au-btn" type="submit">Gửi liên kết đặt lại mật khẩu</button>
    </form>

    <a class="au-link" href="login.php">Quay lại đăng nhập</a>
</main>

</body>
</html>