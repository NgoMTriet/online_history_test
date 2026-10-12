<?php
require_once __DIR__ . '/../config/database.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $account = trim($_POST["account"] ?? "");
    $password = $_POST["password"] ?? "";

    // Lấy thông tin user bằng CSDL mới
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$account, $account]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password_hash"])) {
        if ($user["status"] === "LOCKED") {
            $message = "Tài khoản của bạn đã bị khóa!";
        } else {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["role"] = $user["role"];

            // SỬA TẠI ĐÂY: Chuyển hướng theo vai trò (Role)
            if ($user["role"] === "ADMIN" || $user["role"] === "TEACHER") {
                header("Location: admin.dashboard.php"); // Nếu là Admin / Giáo viên
            } else {
                header("Location: index.php"); // Nếu là Học viên
            }
            exit;
        }
    } else {
        $message = "Tên đăng nhập hoặc mật khẩu không đúng!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title>Đăng nhập</title>
        <link rel="stylesheet" href="assets/css/style.css?v=3">
    </head>
    <body class="login-page">
        <div class="container">
            <h2>ĐĂNG NHẬP</h2>
            <?php if (isset($_GET["registered"])): ?>
                <p class="success" style="color:green;">Đăng ký thành công! Vui lòng đăng nhập.</p>
            <?php endif; ?>
            <p class="message" style="color:red;"><?= htmlspecialchars($message) ?></p>

            <form method="POST">
                <input type="text" name="account" placeholder="Username hoặc Email" required>
                <input type="password" name="password" placeholder="Mật khẩu" required>
                <button type="submit">Đăng nhập</button>
            </form>

            <p><a href="forgot-password.php">Quên mật khẩu?</a></p>
            <p>Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
        </div>
    </body>
</html>