<?php
// Sửa đường dẫn kết nối database bằng __DIR__ để tránh lỗi Not Found / Stream
require_once __DIR__ . '/../config/database.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $full_name = trim($_POST["full_name"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm"] ?? "";
    $role = strtoupper($_POST["role"] ?? "STUDENT"); // Khớp Enum CSDL: STUDENT, TEACHER, ADMIN

    if ($username === "" || $email === "" || $full_name === "" || $password === "" || $confirm === "") {
        $message = "Vui lòng nhập đầy đủ thông tin!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email không hợp lệ!";
    } elseif (strlen($password) < 8) {
        $message = "Mật khẩu phải có ít nhất 8 ký tự!";
    } elseif ($password !== $confirm) {
        $message = "Mật khẩu xác nhận không khớp!";
    } else {
        // Kiểm tra username/email đã tồn tại trong CSDL chưa
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);

        if ($check->fetch()) {
            $message = "Username hoặc email đã tồn tại!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Lưu dữ liệu vào bảng users
            $stmt = $conn->prepare(
                "INSERT INTO users (username, password_hash, full_name, email, role, status)
                 VALUES (?, ?, ?, ?, ?, 'ACTIVE')"
            );
            $stmt->execute([$username, $hashedPassword, $full_name, $email, $role]);

            // Chuyển hướng về login.php
            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body class="auth-page">
<div class="container">
    <h2>ĐĂNG KÝ</h2>
    <p class="message" style="color:red;"><?= htmlspecialchars($message) ?></p>

    <!-- Để method="POST" không có action để form gửi trực tiếp tới chính nó -->
    <form method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="full_name" placeholder="Họ và tên" required>
        <input type="password" name="password" placeholder="Mật khẩu (ít nhất 8 ký tự)" required>
        <input type="password" name="confirm" placeholder="Nhập lại mật khẩu" required>
        
        <select name="role" style="width: 100%; padding: 12px; margin-bottom: 15px;">
            <option value="STUDENT">Học viên</option>
            <option value="TEACHER">Giáo viên</option>
        </select>

        <button type="submit">Đăng ký</button>
    </form>
    <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
</div>
</body>
</html>