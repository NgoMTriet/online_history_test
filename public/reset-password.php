<?php
require_once "config/database.php";

if (!isset($_SESSION["reset_user_id"]) || !isset($_SESSION["otp_verified"]) || $_SESSION["otp_verified"] !== true) {
    header("Location: forgot-password.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm"] ?? "";

    if (strlen($password) < 8) {
        $message = "Mật khẩu phải có ít nhất 8 ký tự!";
    } elseif ($password !== $confirm) {
        $message = "Mật khẩu xác nhận không khớp!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Cập nhật trường password_hash mới
        $stmt = $conn->prepare(
            "UPDATE users
             SET password_hash = ?, reset_otp = NULL, otp_expires = NULL
             WHERE user_id = ?"
        );
        $stmt->execute([$hashed, $_SESSION["reset_user_id"]]);

        unset($_SESSION["reset_user_id"], $_SESSION["otp_verified"]);
        header("Location: login.php?reset=success");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>ĐẶT LẠI MẬT KHẨU</h2>
    <p class="message" style="color:red;"><?= htmlspecialchars($message) ?></p>

    <form method="POST">
        <input type="password" name="password" placeholder="Mật khẩu mới" required>
        <input type="password" name="confirm" placeholder="Xác nhận mật khẩu mới" required>
        <button type="submit">Đổi mật khẩu</button>
    </form>
</div>
</body>
</html>