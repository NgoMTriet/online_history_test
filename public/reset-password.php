
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
    <title>Đặt lại mật khẩu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <h2>ĐẶT LẠI MẬT KHẨU</h2>

    <?php if ($message !== ''): ?>
        <p style="color:red;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
        <a href="forgot_password.php">Yêu cầu đường link mới</a>
    <?php else: ?>
        <p>Vui lòng nhập mật khẩu mới của bạn.</p>

        <form method="POST" action="../api/auth.php?action=reset">
            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
            >

            <input
                type="password"
                name="password"
                placeholder="Mật khẩu mới (ít nhất 8 ký tự)"
                minlength="8"
                required
            >

            <input
                type="password"
                name="confirm_password"
                placeholder="Xác nhận mật khẩu mới"
                minlength="8"
                required
            >

            <button type="submit">Đổi mật khẩu</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
