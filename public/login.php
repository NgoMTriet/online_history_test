<?php
require_once __DIR__ . '/../config/database.php';

// Không lưu trang đăng nhập vào bộ nhớ đệm
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$message = '';
$error = $_GET['error'] ?? '';

// Hiển thị thông báo theo kết quả xử lý từ auth.php
switch ($error) {
    case 'invalid':
        $message = 'Tên đăng nhập hoặc mật khẩu không đúng!';
        break;
    case 'locked':
        $message = 'Tài khoản của bạn đã bị khóa!';
        break;
    case 'pending':
        $message = 'Tài khoản giáo viên đang chờ Admin duyệt.';
        break;
    case 'account':
        $message = 'Tài khoản không còn tồn tại.';
        break;
    case 'action':
        $message = 'Thao tác không hợp lệ.';
        break;
}

if (isset($_GET['logout'])) {
    $message = 'Bạn đã đăng xuất thành công.';
}

if (isset($_GET['registered'])) {
    $successMessage = 'Đăng ký thành công! Vui lòng đăng nhập.';
} else {
    $successMessage = '';
}

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $successMessage = 'Đổi mật khẩu thành công! Vui lòng đăng nhập.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - TestSuViet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/css/auth.css?v=1">
   
</head>

<body class="login-page">

    <svg class="backdrop" viewBox="0 0 1000 1000" aria-hidden="true">
        <g fill="none" stroke="currentColor">
            <circle cx="500" cy="500" r="490" stroke-width="3"/>
            <circle cx="500" cy="500" r="440" stroke-width="2" stroke-dasharray="4 12"/>
            <circle cx="500" cy="500" r="390" stroke-width="3"/>
            <circle cx="500" cy="500" r="330" stroke-width="2" stroke-dasharray="4 12"/>
            <circle cx="500" cy="500" r="270" stroke-width="3"/>
            <circle cx="500" cy="500" r="200" stroke-width="2" stroke-dasharray="4 12"/>
            <circle cx="500" cy="500" r="130" stroke-width="3"/>
        </g>
    </svg>

    <div class="stack">

        <a class="brand" href="index.php">
            <svg width="48" height="48" viewBox="0 0 32 32" aria-hidden="true">
                <circle cx="16" cy="16" r="14.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="16" cy="16" r="11" fill="none" stroke="currentColor" stroke-width="1" stroke-dasharray="1.5 2.5"/>
                <polygon fill="currentColor" points="16,9 17.65,13.73 22.66,13.84 18.66,16.87 20.11,21.66 16,18.8 11.89,21.66 13.34,16.87 9.34,13.84 14.35,13.73"/>
            </svg>
            <span class="brand-name">TestSuViet</span>
        </a>

        <main class="card">
            <h1>Đăng nhập</h1>
            <p class="lead">Chào mừng bạn quay lại.</p>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success" role="status">
                    <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($message !== ''): ?>
                <div class="alert alert-error" role="alert">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="../api/auth.php?action=login">
                <div class="field">
                    <label for="account">Username hoặc Email</label>
                    <input
                        type="text"
                        id="account"
                        name="account"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Mật khẩu</label>
                    <div class="password-box">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-password" id="togglePassword"
                                aria-controls="password" aria-pressed="false">Hiện</button>
                    </div>
                </div>

                <a class="forgot" href="forgot_password.php">Quên mật khẩu?</a>

                <button type="submit" class="submit">Đăng nhập</button>
            </form>

            <p class="register">
                Chưa có tài khoản? <a href="register.php">Đăng ký</a>
            </p>
        </main>

        <p class="below"><a href="index.php">← Về trang chủ</a></p>

    </div>

<script>
    (function () {
        var input = document.getElementById('password');
        var button = document.getElementById('togglePassword');

        button.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.textContent = show ? 'Ẩn' : 'Hiện';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    })();
</script>
</body>
</html>