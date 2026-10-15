<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    // Đọc user_id theo CSDL mới
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = "Email không tồn tại!";
    } else {
        $otp = (string) random_int(100000, 999999);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

        // Lưu thông tin OTP ứng với user_id
        $stmt = $conn->prepare(
            "UPDATE users
             SET reset_otp = ?, otp_expires = DATE_ADD(NOW(), INTERVAL 5 MINUTE)
             WHERE user_id = ?"
        );
        $stmt->execute([$otpHash, $user["user_id"]]);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Username = "your-email@gmail.com";
            $mail->Password = "YOUR_GOOGLE_APP_PASSWORD";
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = "UTF-8";

            $mail->setFrom("support@testsuviet.com", "Hệ thống hỗ trợ");
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Mã xác minh đổi mật khẩu";
            $mail->Body = "<h2>Mã OTP của bạn: $otp</h2><p>Mã có hiệu lực trong 5 phút.</p>";

            $mail->send();
            $_SESSION["reset_user_id"] = $user["user_id"];
            header("Location: verify-otp.php");
            exit;
        } catch (Exception $e) {
            $message = "Không gửi được email. Vui lòng thử lại.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>QUÊN MẬT KHẨU</h2>
    <p class="message" style="color:red;"><?= htmlspecialchars($message) ?></p>
    <form method="POST">
        <input type="email" name="email" placeholder="Nhập email đăng ký" required>
        <button type="submit">Gửi mã OTP</button>
    </form>
    <a href="login.php">Quay lại đăng nhập</a>
</div>
</body>
</html>