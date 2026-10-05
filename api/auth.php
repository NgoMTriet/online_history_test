// login
<?php
// Nạp file kết nối cơ sở dữ liệu và khởi tạo Session
require_once "config/database.php";

// Biến lưu thông báo lỗi đăng nhập
$message = "";

// Kiểm tra người dùng có gửi biểu mẫu bằng phương thức POST hay không
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Lấy tài khoản người dùng nhập (username hoặc email)
    // trim() giúp loại bỏ khoảng trắng ở đầu và cuối
    // Nếu không có dữ liệu thì mặc định là chuỗi rỗng
    $account = trim($_POST["account"] ?? "");

    // Lấy mật khẩu người dùng nhập
    $password = $_POST["password"] ?? "";

    // Chuẩn bị câu lệnh SQL tìm người dùng theo username hoặc email
    // Dấu ? là tham số sẽ được truyền vào sau
    // LIMIT 1 giúp chỉ lấy tối đa một tài khoản
    $stmt = $conn->prepare(
        "SELECT * FROM users
         WHERE username = ? OR email = ?
         LIMIT 1"
    );

    // Truyền tài khoản vào cả hai vị trí:
    // Vị trí thứ nhất dùng để tìm username
    // Vị trí thứ hai dùng để tìm email
    $stmt->execute([$account, $account]);

    // Lấy thông tin tài khoản tìm được từ database
    // Nếu không tìm thấy, $user sẽ là false
    $user = $stmt->fetch();

    // Kiểm tra:
    // 1. Tài khoản có tồn tại hay không
    // 2. Mật khẩu nhập vào có khớp với mật khẩu đã mã hóa trong database không
    if ($user && password_verify($password, $user["password"])) {

        // Tạo lại Session ID để tăng bảo mật sau khi đăng nhập
        // true giúp xóa Session ID cũ
        session_regenerate_id(true);

        // Lưu ID người dùng vào Session để xác định người đang đăng nhập
        $_SESSION["user_id"] = $user["id"];

        // Lưu tên người dùng vào Session để hiển thị ở các trang khác
        $_SESSION["username"] = $user["username"];

        // Đăng nhập thành công thì chuyển đến trang cá nhân
        header("Location: dashboard.php");

        // Dừng chương trình sau khi chuyển hướng
        exit;

    } else {

        // Nếu tài khoản không tồn tại hoặc mật khẩu sai
        // thì hiển thị thông báo lỗi
        $message = "Tên đăng nhập hoặc mật khẩu không đúng!";
    }
}

// Logout
<?php
// Khởi động Session để truy cập thông tin đăng nhập hiện tại
session_start();

// Xóa toàn bộ dữ liệu được lưu trong Session
// Ví dụ: user_id, username
$_SESSION = [];

// Hủy Session hiện tại
// Sau lệnh này, dữ liệu Session sẽ không còn được duy trì
session_destroy();

// Chuyển người dùng về trang đăng nhập sau khi đăng xuất
header("Location: login.php");

// Dừng chương trình PHP sau khi chuyển hướng
exit;


?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <title>Đăng nhập</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Khung chứa toàn bộ giao diện đăng nhập -->
<div class="container">

    <!-- Tiêu đề trang -->
    <h2>ĐĂNG NHẬP</h2>

    <!-- Kiểm tra URL có tham số registered hay không -->
    <!-- Nếu có, hiển thị thông báo đăng ký thành công -->
    <?php if (isset($_GET["registered"])): ?>
        <p class="success">Đăng ký thành công!</p>
    <?php endif; ?>

    <!-- Hiển thị thông báo lỗi nếu đăng nhập không thành công -->
    <!-- htmlspecialchars() giúp tránh hiển thị nội dung HTML không an toàn -->
    <p class="message"><?= htmlspecialchars($message) ?></p>

    <!-- Form gửi thông tin đăng nhập đến chính trang login.php -->
    <!-- method="POST" giúp gửi dữ liệu qua phương thức POST -->
    <form method="POST">

        <!-- Ô nhập username hoặc email -->
        <!-- name="account" dùng để PHP lấy dữ liệu từ $_POST["account"] -->
        <!-- required yêu cầu người dùng phải nhập -->
        <input type="text" name="account"
               placeholder="Username hoặc Email" required>

        <!-- Ô nhập mật khẩu -->
        <!-- type="password" giúp che ký tự mật khẩu -->
        <input type="password" name="password"
               placeholder="Mật khẩu" required>

        <!-- Nút gửi thông tin đăng nhập -->
        <button type="submit">Đăng nhập</button>
    </form>

    <!-- Liên kết đến chức năng quên mật khẩu -->
    <p><a href="forgot-password.php">Quên mật khẩu?</a></p>

    <!-- Liên kết đến trang đăng ký nếu chưa có tài khoản -->
    <p>Chưa có tài khoản?
        <a href="register.php">Đăng ký</a>
    </p>
</div>

</body>
</html>
```
