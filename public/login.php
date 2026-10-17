
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
    <link rel="stylesheet" href="assets/css/style.css?v=4">

    <style>
        .home-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 16px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }

        .home-link:hover {
            background: #217dbb;
        }

        .message {
            margin: 12px 0;
            color: #dc3545;
        }

        .success {
            margin: 12px 0;
            color: #198754;
        }
    </style>
</head>

<body class="login-page">
    <div class="container">

        
<a href="index.php" class="back-home">
    <span class="back-icon">←</span>
    <span>Trang chủ</span>
</a>


        <h2>ĐĂNG NHẬP</h2>

        <?php if ($successMessage !== ''): ?>
            <p class="success">
                <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <p class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="/api/auth.php?action=login">
            <input
                type="text"
                name="account"
                placeholder="Username hoặc Email"
                autocomplete="username"
                required
            >

            <input
                type="password"
                name="password"
                placeholder="Mật khẩu"
                autocomplete="current-password"
                required
            >

            <button type="submit">Đăng nhập</button>
        </form>

        <p>
            <a href="forgot_password.php">Quên mật khẩu?</a>
        </p>

        <p>
            Chưa có tài khoản?
            <a href="register.php">Đăng ký</a>
        </p>

    </div>
</body>
</html>
