```php
<?php

// Kết nối đến file database.php để sử dụng kết nối CSDL ($conn)
// Đồng thời file này có thể khởi tạo session
require_once "config/database.php";


// Biến dùng để lưu thông báo lỗi hoặc thông báo cho người dùng
$message = "";


// Kiểm tra xem người dùng có gửi form bằng phương thức POST hay không
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Lấy tên đăng nhập hoặc email từ form
    // trim() dùng để xóa khoảng trắng thừa ở đầu và cuối
    // ?? "" nghĩa là nếu không có dữ liệu thì lấy chuỗi rỗng
    $account = trim($_POST["account"] ?? "");

    // Lấy mật khẩu người dùng nhập vào
    $password = $_POST["password"] ?? "";


    // Kiểm tra người dùng có bỏ trống tài khoản hoặc mật khẩu không
    if ($account === "" || $password === "") {

        // Nếu bỏ trống thì hiển thị thông báo
        $message = "Vui lòng nhập đầy đủ thông tin.";

    } else {

        // Câu lệnh SQL tìm người dùng
        // Có thể đăng nhập bằng username hoặc email
        // LIMIT 1 chỉ lấy tối đa 1 tài khoản
        $sql = "SELECT * FROM users
                WHERE username = ? OR email = ?
                LIMIT 1";


        // Chuẩn bị câu SQL để thực thi
        // prepare() giúp hạn chế SQL Injection
        $stmt = $conn->prepare($sql);


        // Thực thi câu SQL
        // Giá trị $account được truyền vào cả username và email
        $stmt->execute([$account, $account]);


        // Lấy thông tin người dùng tìm được
        // Nếu không tìm thấy thì $user sẽ là false
        $user = $stmt->fetch();


        // Kiểm tra tài khoản có tồn tại hay không
        if (!$user) {

            // Không tìm thấy tài khoản
            $message = "Tên đăng nhập hoặc email không tồn tại.";


        // Nếu tài khoản tồn tại thì kiểm tra trạng thái tài khoản
        // LOCKED nghĩa là tài khoản đã bị khóa
        } elseif ($user["status"] === "LOCKED") {

            // Thông báo tài khoản bị khóa
            $message = "Tài khoản của bạn đã bị khóa.";


        // Kiểm tra mật khẩu người dùng nhập có đúng không
        // password_verify() so sánh mật khẩu nhập vào
        // với mật khẩu đã được mã hóa trong database
        } elseif (!password_verify($password, $user["password_hash"])) {

            // Nếu mật khẩu sai thì thông báo
            $message = "Mật khẩu không chính xác.";


        } else {

            // Nếu chạy đến đây nghĩa là:
            // 1. Tài khoản tồn tại
            // 2. Tài khoản không bị khóa
            // 3. Mật khẩu chính xác


            // Lưu user_id vào session
            // Dùng để xác định người dùng hiện tại
            $_SESSION["user_id"] = $user["user_id"];


            // Lưu username vào session
            $_SESSION["username"] = $user["username"];


            // Lưu họ tên vào session
            $_SESSION["full_name"] = $user["full_name"];


            // Lưu quyền của tài khoản vào session
            // Ví dụ: STUDENT, TEACHER hoặc ADMIN
            $_SESSION["role"] = $user["role"];


            // Đăng nhập thành công
            // Chuyển người dùng sang trang dashboard
            header("Location: dashboard.php");


            // Dừng chương trình sau khi chuyển trang
            exit;
        }
    }
}
?>

<!-- Phần HTML bắt đầu từ đây -->
<!DOCTYPE html>

<!-- Khai báo ngôn ngữ của trang là tiếng Việt -->
<html lang="vi">

<head>

    <!-- Thiết lập bảng mã UTF-8 để hiển thị tiếng Việt -->
    <meta charset="UTF-8">

    <!-- Tiêu đề hiển thị trên tab trình duyệt -->
    <title>Đăng nhập</title>

</head>

<body>

    <!-- Tiêu đề trang -->
    <h2>Đăng nhập</h2>


    <!--
        Kiểm tra xem có thông báo lỗi hay không.
        Nếu $message không rỗng thì hiển thị thông báo.
    -->
    <?php if ($message !== ""): ?>

        <!-- Hiển thị thông báo màu đỏ -->
        <p style="color:red;">

            <!--
                htmlspecialchars() giúp hiển thị nội dung an toàn
                và tránh việc chèn mã HTML/JavaScript.
            -->
            <?= htmlspecialchars($message) ?>

        </p>

    <?php endif; ?>


    <!-- Form đăng nhập -->
    <!-- method="POST" dùng để gửi dữ liệu đến chính file này -->
    <form method="POST">


        <!-- Nhãn nhập tài khoản -->
        <label>Tên đăng nhập hoặc Email:</label><br>


        <!--
            Ô nhập username hoặc email
            name="account" để PHP lấy dữ liệu bằng $_POST["account"]
            required bắt buộc người dùng phải nhập
        -->
        <input
            type="text"
            name="account"
            required
        >


        <!-- Xuống dòng và tạo khoảng cách -->
        <br><br>


        <!-- Nhãn nhập mật khẩu -->
        <label>Mật khẩu:</label><br>


        <!--
            Ô nhập mật khẩu
            type="password" giúp che nội dung mật khẩu
            name="password" để PHP lấy dữ liệu bằng $_POST["password"]
        -->
        <input
            type="password"
            name="password"
            required
        >


        <!-- Xuống dòng và tạo khoảng cách -->
        <br><br>


        <!-- Nút gửi form đăng nhập -->
        <button type="submit">Đăng nhập</button>

    </form>


    <!-- Tạo khoảng cách -->
    <br>


    <!--
        Liên kết đến trang đăng ký
        Nếu chưa có tài khoản thì người dùng có thể đăng ký
    -->
    <a href="register.php">
        Chưa có tài khoản? Đăng ký
    </a>


    <!-- Xuống dòng -->
    <br>


    <!--
        Liên kết đến trang quên mật khẩu
    -->
    <a href="forgot-password.php">
        Quên mật khẩu?
    </a>


</body>
</html>
```
