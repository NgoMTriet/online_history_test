```php
<?php
// Kết nối database và khởi tạo session nếu cần
require_once __DIR__ . '/../config/database.php';

// Lấy mã lỗi trả về từ api/auth.php
$error = $_GET['error'] ?? '';
$message = '';

switch ($error) {
    case 'missing':
        $message = 'Vui lòng nhập đầy đủ tất cả thông tin.';
        break;

    case 'email':
        $message = 'Địa chỉ email không đúng định dạng. Vui lòng kiểm tra lại.';
        break;

    case 'password':
        $message = 'Mật khẩu phải có ít nhất 8 ký tự.';
        break;

    case 'confirm':
        $message = 'Mật khẩu xác nhận không khớp với mật khẩu đã nhập.';
        break;

    case 'exists':
        $message = 'Tên đăng nhập hoặc email đã tồn tại. Vui lòng sử dụng thông tin khác.';
        break;

    default:
        $message = '';
        break;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>

<body class="auth-page">
    <div class="container">
        <h2>ĐĂNG KÝ</h2>

        <?php if ($message !== ''): ?>
            <p class="message" style="color: red;">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="../api/auth.php?action=register">
            <input
                type="text"
                name="username"
                placeholder="Tên đăng nhập"
                required
            >

            <input
                type="email"
                name="email"
                placeholder="Email"
                required
            >

            <input
                type="text"
                name="full_name"
                placeholder="Họ và tên"
                required
            >

            <input
                type="password"
                name="password"
                placeholder="Mật khẩu (ít nhất 8 ký tự)"
                required
            >

            <input
                type="password"
                name="confirm_password"
                placeholder="Nhập lại mật khẩu"
                required
            >

            <select
                name="role"
                style="width: 100%; padding: 12px; margin-bottom: 15px;"
            >
                <option value="STUDENT">Học viên</option>
                <option value="TEACHER">Giáo viên</option>
            </select>

            <button type="submit">Đăng ký</button>
        </form>

        <p>
            Đã có tài khoản?
            <a href="login.php">Đăng nhập</a>
        </p>
    </div>
</body>
</html>
```