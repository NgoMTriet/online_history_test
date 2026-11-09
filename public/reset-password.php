<?php
require_once __DIR__ . '/../config/database.php';

$token = trim($_GET['token'] ?? '');
$message = '';

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $message = 'Đường link đặt lại mật khẩu không hợp lệ!';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - SuViet</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600&family=Noto+Serif:wght@700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="au-body">

<a class="au-brand" href="index.php"><span class="au-brand-mark">vn</span>SuViet.com</a>

<main class="au-card">
    <h1 class="au-title">Đặt lại mật khẩu</h1>

    <?php if ($message !== ''): ?>
        <p class="au-desc">Link này không dùng được. Hãy yêu cầu một link mới để tiếp tục.</p>

        <div class="au-notice" role="alert">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>

        <a class="au-btn" href="forgot_password.php">Yêu cầu đường link mới</a>
    <?php else: ?>
        <p class="au-desc">Vui lòng nhập mật khẩu mới của bạn.</p>

        <form method="POST" action="../api/auth.php?action=reset">
            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
            >

            <div class="au-field">
                <label for="password">Mật khẩu mới</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
                <p class="au-hint">Ít nhất 8 ký tự.</p>
            </div>

            <div class="au-field">
                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                <input
                    id="confirm_password"
                    type="password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button class="au-btn" type="submit">Đổi mật khẩu</button>
        </form>

        <a class="au-link" href="login.php">Quay lại đăng nhập</a>
    <?php endif; ?>
</main>

</body>
</html>