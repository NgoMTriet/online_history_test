<?php
require_once "config/database.php";

if (!isset($_SESSION["reset_user_id"])) {
    header("Location: forgot-password.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp = trim($_POST["otp"] ?? "");

    $stmt = $conn->prepare("SELECT reset_otp, otp_expires FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION["reset_user_id"]]);
    $user = $stmt->fetch();

    if ($user && $user["reset_otp"] && $user["otp_expires"] && 
        strtotime($user["otp_expires"]) > time() && 
        password_verify($otp, $user["reset_otp"])) {

        $_SESSION["otp_verified"] = true;
        header("Location: reset-password.php");
        exit;
    } else {
        $message = "OTP không đúng hoặc đã hết hạn!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác minh OTP</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>XÁC MINH OTP</h2>
    <p>Mã OTP đã được gửi đến email của bạn.</p>
    <p class="message" style="color:red;"><?= htmlspecialchars($message) ?></p>

    <form method="POST">
        <input type="text" name="otp" placeholder="Nhập mã OTP" maxlength="6" pattern="[0-9]{6}" required>
        <button type="submit">Xác minh</button>
    </form>
</div>
</body>
</html>