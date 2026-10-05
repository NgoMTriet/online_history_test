// Đăng kí
<?php
// 1. KẾT NỐI DATABASE
// Nhúng file database.php để sử dụng kết nối MySQL ($conn)
// require_once giúp file chỉ được nhúng một lần
require_once "config/database.php";
// Khởi tạo biến lưu thông báo lỗi
$message = "";
// 2. KIỂM TRA NGƯỜI DÙNG GỬI FORM
// Kiểm tra phương thức gửi dữ liệu có phải POST hay không
// POST được sử dụng khi người dùng nhấn nút Đăng ký
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Lấy tên đăng nhập từ form và loại bỏ khoảng trắng đầu/cuối
    $username = trim($_POST["username"] ?? "");

    // Lấy email từ form và loại bỏ khoảng trắng đầu/cuối
    $email = trim($_POST["email"] ?? "");

    // Lấy mật khẩu người dùng nhập
    $password = $_POST["password"] ?? "";

    // Lấy mật khẩu xác nhận
    $confirm = $_POST["confirm"] ?? "";

    // 3. KIỂM TRA DỮ LIỆU ĐẦU VÀO

    // Kiểm tra người dùng có bỏ trống trường thông tin nào không
    if ($username === "" || $email === "" ||
        $password === "" || $confirm === "") {

        // Nếu thiếu thông tin thì thông báo lỗi
        $message = "Vui lòng nhập đầy đủ thông tin!";

    // Kiểm tra định dạng email có hợp lệ không
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        // Nếu email sai định dạng thì thông báo lỗi
        $message = "Email không hợp lệ!";

    // Kiểm tra mật khẩu có ít nhất 8 ký tự không
    } elseif (strlen($password) < 8) {

        // Nếu mật khẩu ngắn hơn 8 ký tự thì thông báo lỗi
        $message = "Mật khẩu phải có ít nhất 8 ký tự!";

    // So sánh mật khẩu và mật khẩu xác nhận
    } elseif ($password !== $confirm) {

        // Nếu hai mật khẩu không giống nhau thì thông báo lỗi
        $message = "Mật khẩu xác nhận không khớp!";

    } else {

        // 4. KIỂM TRA USERNAME VÀ EMAIL ĐÃ TỒN TẠI

        // Chuẩn bị câu lệnh SQL kiểm tra username hoặc email
        // Dấu ? là tham số đại diện, giúp tránh SQL Injection
        $check = $conn->prepare(
            "SELECT id FROM users WHERE username = ? OR email = ?"
        );

        // Truyền username và email vào câu lệnh SQL để thực thi
        $check->execute([$username, $email]);

        // Lấy một dòng dữ liệu nếu tìm thấy tài khoản trùng
        if ($check->fetch()) {

            // Nếu username hoặc email đã tồn tại thì thông báo lỗi
            $message = "Username hoặc email đã tồn tại!";

        } else {

            // 5. MÃ HÓA MẬT KHẨU

            // Sử dụng password_hash() để mã hóa mật khẩu
            // PASSWORD_DEFAULT tự chọn thuật toán mặc định an toàn của PHP
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // 6. THÊM TÀI KHOẢN VÀO DATABASE

            // Chuẩn bị câu lệnh SQL INSERT để thêm người dùng mới
            // Chỉ lưu mật khẩu đã mã hóa, không lưu mật khẩu gốc
            $stmt = $conn->prepare(
                "INSERT INTO users (username, email, password)
                 VALUES (?, ?, ?)"
            );

            // Truyền dữ liệu vào các dấu ? và thực thi câu lệnh
            $stmt->execute([
                $username,       // Tên đăng nhập
                $email,          // Địa chỉ email
                $hashedPassword  // Mật khẩu đã mã hóa
            ]);

            // 7. CHUYỂN SANG TRANG ĐĂNG NHẬP

            // Chuyển người dùng đến login.php
            // registered=1 dùng để thông báo đăng ký thành công
            header("Location: login.php?registered=1");

            // Dừng chương trình sau khi chuyển trang
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

    <!-- Nhúng file CSS để thiết kế giao diện -->
    <link rel="stylesheet" href="assets/style.css">

</head>

<body>

<!-- Khung chứa toàn bộ giao diện đăng ký -->
<div class="container">

    <!-- Tiêu đề trang -->
    <h2>ĐĂNG KÝ</h2>

    <!-- Hiển thị thông báo lỗi nếu có -->
    <!-- htmlspecialchars() giúp tránh chèn mã HTML/JavaScript độc hại -->
    <?= htmlspecialchars($message) ?>

    <!-- Form gửi dữ liệu bằng phương thức POST -->
    <form method="POST">

        <!-- Ô nhập tên đăng nhập -->
        <!-- name=username dùng để PHP nhận dữ liệu -->
        <input type="text" name="username"
               placeholder="Tên đăng nhập" required>

        <!-- Ô nhập email -->
        <input type="email" name="email"
               placeholder="Email" required>

        <!-- Ô nhập mật khẩu -->
        <input type="password" name="password"
               placeholder="Mật khẩu (ít nhất 8 ký tự)" required>

        <!-- Ô nhập lại mật khẩu để xác nhận -->
        <input type="password" name="confirm"
               placeholder="Nhập lại mật khẩu" required>

        <!-- Nút gửi dữ liệu đăng ký đến PHP -->
        <button type="submit">Đăng ký</button>

    </form>

    <!-- Liên kết chuyển sang trang đăng nhập -->
    <p>Đã có tài khoản?
        <a href="login.php">Đăng nhập</a>
    </p>

</div>

</body>
</html>

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


//Quên mật khẩu

<?php

// 1. KẾT NỐI DATABASE VÀ THƯ VIỆN GỬI EMAIL

// Nhúng file database.php để kết nối với MySQL
// Đồng thời khởi tạo Session để lưu thông tin người dùng
require_once "config/database.php";

// Nhúng thư viện PHPMailer được cài bằng Composer
// Thư viện này hỗ trợ gửi email thông qua SMTP
require_once "vendor/autoload.php";

// Khai báo sử dụng lớp PHPMailer để gửi email
use PHPMailer\PHPMailer\PHPMailer;

// Khai báo lớp Exception để xử lý lỗi gửi email
use PHPMailer\PHPMailer\Exception;

// Khởi tạo biến lưu thông báo lỗi
$message = "";


// 2. KIỂM TRA NGƯỜI DÙNG GỬI FORM

// Kiểm tra phương thức gửi dữ liệu có phải POST hay không
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Nhận email người dùng nhập từ biểu mẫu
    // trim() loại bỏ khoảng trắng ở đầu và cuối
    // ?? "" giúp tránh lỗi nếu dữ liệu không tồn tại
    $email = trim($_POST["email"] ?? "");


    // 3. KIỂM TRA EMAIL TRONG DATABASE

    // Chuẩn bị câu lệnh SQL tìm tài khoản theo email
    // Dấu ? là tham số đại diện để truyền dữ liệu an toàn
    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE email = ?"
    );

    // Truyền email vào câu lệnh SQL và thực thi
    $stmt->execute([$email]);

    // Lấy thông tin tài khoản tìm được
    $user = $stmt->fetch();


    // Kiểm tra email có tồn tại trong database không
    if (!$user) {

        // Nếu không tìm thấy tài khoản thì thông báo lỗi
        $message = "Email không tồn tại!";

    } else {

        // 4. TẠO MÃ OTP NGẪU NHIÊN

        // Tạo mã OTP ngẫu nhiên gồm 6 chữ số
        // random_int() tạo số ngẫu nhiên an toàn
        // Chuyển kết quả sang kiểu chuỗi để lưu trữ
        $otp = (string) random_int(100000, 999999);

        // Băm mã OTP trước khi lưu vào database
        // Không lưu mã OTP gốc để tăng tính bảo mật
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);


        // 5. LƯU OTP VÀ THỜI GIAN HẾT HẠN

        // Chuẩn bị câu lệnh SQL cập nhật thông tin OTP
        // reset_otp: lưu mã OTP đã băm
        // otp_expires: thời gian hết hạn OTP sau 5 phút
        // WHERE id = ?: chỉ cập nhật tài khoản tương ứng
        $stmt = $conn->prepare(
            "UPDATE users
             SET reset_otp = ?,
                 otp_expires = DATE_ADD(NOW(), INTERVAL 5 MINUTE)
             WHERE id = ?"
        );

        // Truyền mã OTP đã băm và ID người dùng vào SQL
        // Sau đó thực thi câu lệnh cập nhật
        $stmt->execute([$otpHash, $user["id"]]);


        // 6. KHỞI TẠO PHPMailer

        // Tạo đối tượng PHPMailer
        // true có nghĩa là bật cơ chế xử lý ngoại lệ
        $mail = new PHPMailer(true);

        // Sử dụng try-catch để xử lý lỗi trong quá trình gửi email
        try {

            // Chọn phương thức gửi email bằng SMTP
            $mail->isSMTP();

            // Khai báo máy chủ SMTP của Gmail
            $mail->Host = "smtp.gmail.com";

            // Bật xác thực tài khoản SMTP
            $mail->SMTPAuth = true;


            // 7. CẤU HÌNH TÀI KHOẢN GMAIL

            // Email Gmail dùng để gửi mã OTP
            // Thay bằng email Gmail thật của bạn
            $mail->Username = "your-email@gmail.com";

            // Mật khẩu ứng dụng Google App Password
            // Không sử dụng mật khẩu Gmail thông thường
            $mail->Password = "YOUR_GOOGLE_APP_PASSWORD";

            // Sử dụng mã hóa TLS để bảo vệ kết nối SMTP
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            // Cổng SMTP của Gmail khi sử dụng STARTTLS
            $mail->Port = 587;

            // Thiết lập bảng mã UTF-8 để hỗ trợ tiếng Việt
            $mail->CharSet = "UTF-8";


            // 8. THIẾT LẬP NGƯỜI GỬI VÀ NGƯỜI NHẬN

            // Khai báo địa chỉ và tên người gửi email
            $mail->setFrom(
                "nbnguyenktpm2411052@student.ctuet.edu.vn",
                "Website hỗ trợ"
            );

            // Thiết lập email người nhận OTP
            // Chính là email người dùng đã nhập
            $mail->addAddress($email);


            // 9. TẠO NỘI DUNG EMAIL OTP

            // Cho phép gửi email dưới dạng HTML
            $mail->isHTML(true);

            // Tiêu đề email gửi đến người dùng
            $mail->Subject = "Mã xác minh đổi mật khẩu";

            // Nội dung email chứa mã OTP
            // Biến $otp được chèn trực tiếp vào nội dung HTML
            $mail->Body = "
                <h2>Mã OTP của bạn</h2>
                <h1>$otp</h1>
                <p>Mã có hiệu lực trong 5 phút.</p>
                <p>Không chia sẻ mã này với người khác.</p>
            ";

            // 10. GỬI EMAIL VÀ CHUYỂN TRANG

            // Thực hiện gửi email chứa mã OTP
            $mail->send();

            // Lưu ID người dùng vào Session
            // Để xác định tài khoản cần xác minh OTP
            $_SESSION["reset_user_id"] = $user["id"];

            // Chuyển người dùng đến trang nhập mã OTP
            header("Location: verify-otp.php");

            // Dừng chương trình sau khi chuyển trang
            exit;

        // 11. XỬ LÝ LỖI GỬI EMAIL

        } catch (Exception $e) {

            // Nếu gửi email thất bại thì hiển thị thông báo
            $message = "Không gửi được email. Vui lòng kiểm tra SMTP.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <!-- Thiết lập bảng mã UTF-8 để hiển thị tiếng Việt -->
    <meta charset="UTF-8">

    <!-- Tiêu đề trang hiển thị trên trình duyệt -->
    <title>Quên mật khẩu</title>

    <!-- Nhúng file CSS để thiết kế giao diện -->
    <link rel="stylesheet" href="assets/style.css">

</head>

<body>

    <!-- Khung chứa giao diện quên mật khẩu -->
    <div class="container">

        <!-- Tiêu đề chức năng -->
        <h2>QUÊN MẬT KHẨU</h2>

        <!-- Hiển thị thông báo lỗi nếu có -->
        <!-- htmlspecialchars() giúp hiển thị dữ liệu an toàn -->
        <p class="message"><?= htmlspecialchars($message) ?></p>

        <!-- Form gửi email bằng phương thức POST -->
        <form method="POST">

            <!-- Ô nhập email đã đăng ký tài khoản -->
            <input type="email" name="email"
                   placeholder="Nhập email đăng ký" required>

            <!-- Nút gửi yêu cầu lấy mã OTP -->
            <button type="submit">Gửi mã OTP</button>

        </form>

        <!-- Liên kết quay lại trang đăng nhập -->
        <a href="login.php">Quay lại đăng nhập</a>

    </div>

</body>
</html>

// Đổi mật khẩu 

<?php
// Nạp file kết nối database và khởi động Session
require_once "config/database.php";

// Kiểm tra người dùng đã vượt qua bước xác minh OTP hay chưa
if (
    // Kiểm tra có tồn tại ID tài khoản cần đặt lại mật khẩu không
    !isset($_SESSION["reset_user_id"]) ||

    // Kiểm tra có tồn tại trạng thái xác minh OTP không
    !isset($_SESSION["otp_verified"]) ||

    // Kiểm tra OTP đã được xác minh thành công hay chưa
    $_SESSION["otp_verified"] !== true
) {
    // Nếu chưa xác minh OTP thì quay lại trang quên mật khẩu
    header("Location: forgot-password.php");

    // Dừng chương trình, không cho truy cập trang đặt lại mật khẩu
    exit;
}

// Biến lưu thông báo lỗi khi đặt lại mật khẩu
$message = "";

// Kiểm tra người dùng có gửi biểu mẫu bằng phương thức POST hay không
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Lấy mật khẩu mới người dùng nhập
    $password = $_POST["password"] ?? "";

    // Lấy mật khẩu xác nhận người dùng nhập
    $confirm = $_POST["confirm"] ?? "";

    // Kiểm tra mật khẩu mới có ít nhất 8 ký tự hay không
    if (strlen($password) < 8) {

        // Nếu mật khẩu dưới 8 ký tự thì thông báo lỗi
        $message = "Mật khẩu phải có ít nhất 8 ký tự!";

    // Kiểm tra mật khẩu mới và mật khẩu xác nhận có giống nhau không
    } elseif ($password !== $confirm) {

        // Nếu không giống nhau thì thông báo lỗi
        $message = "Mật khẩu xác nhận không khớp!";

    } else {

        // Mã hóa mật khẩu mới trước khi lưu vào database
        // PASSWORD_DEFAULT sử dụng thuật toán băm mặc định của PHP
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Chuẩn bị câu lệnh SQL cập nhật mật khẩu cho tài khoản
        $stmt = $conn->prepare(
            "UPDATE users
             SET password = ?,

                 // Xóa mã OTP cũ sau khi đổi mật khẩu thành công
                 reset_otp = NULL,

                 // Xóa thời hạn OTP cũ
                 otp_expires = NULL
             WHERE id = ?"
        );

        // Thực thi câu lệnh SQL
        // Tham số thứ nhất: mật khẩu mới đã mã hóa
        // Tham số thứ hai: ID tài khoản cần cập nhật
        $stmt->execute([
            $hashed,
            $_SESSION["reset_user_id"]
        ]);

        // Xóa thông tin xác minh OTP khỏi Session
        // Tránh sử dụng lại trạng thái xác minh cũ
        unset(
            $_SESSION["reset_user_id"],
            $_SESSION["otp_verified"]
        );

        // Đổi mật khẩu thành công thì chuyển về trang đăng nhập
        // reset=success là tham số thông báo trên URL
        header("Location: login.php?reset=success");

        // Dừng chương trình sau khi chuyển hướng
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- Khai báo bảng mã UTF-8 để hỗ trợ tiếng Việt -->
    <meta charset="UTF-8">

    <!-- Tiêu đề trang đặt lại mật khẩu -->
    <title>Đặt lại mật khẩu</title>

    <!-- Kết nối file CSS để định dạng giao diện -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- Khung chứa giao diện đặt lại mật khẩu -->
<div class="container">

    <!-- Tiêu đề trang -->
    <h2>ĐẶT LẠI MẬT KHẨU</h2>

    <!-- Hiển thị thông báo lỗi nếu có -->
    <!-- htmlspecialchars() giúp hiển thị nội dung an toàn -->
    <p class="message"><?= htmlspecialchars($message) ?></p>

    <!-- Form gửi mật khẩu mới đến chính trang reset-password.php -->
    <form method="POST">

        <!-- Ô nhập mật khẩu mới -->
        <!-- type="password" giúp che ký tự mật khẩu -->
        <!-- required yêu cầu người dùng phải nhập -->
        <input type="password" name="password"
               placeholder="Mật khẩu mới" required>

        <!-- Ô nhập lại mật khẩu mới để xác nhận -->
        <input type="password" name="confirm"
               placeholder="Xác nhận mật khẩu mới" required>

        <!-- Nút gửi yêu cầu đổi mật khẩu -->
        <button type="submit">Đổi mật khẩu</button>
    </form>
</div>

</body>
</html>

//Xác minh tài khoản

<?php
// Nạp file kết nối database và khởi động Session
require_once "config/database.php";

// Kiểm tra Session có lưu ID tài khoản cần xác minh OTP hay không
if (!isset($_SESSION["reset_user_id"])) {

    // Nếu không có ID tài khoản thì quay về trang quên mật khẩu
    header("Location: forgot-password.php");

    // Dừng chương trình sau khi chuyển hướng
    exit;
}

// Biến lưu thông báo lỗi khi xác minh OTP
$message = "";

// Kiểm tra người dùng có gửi biểu mẫu bằng phương thức POST hay không
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Lấy mã OTP người dùng nhập
    // trim() giúp loại bỏ khoảng trắng ở đầu và cuối
    $otp = trim($_POST["otp"] ?? "");

    // Chuẩn bị câu lệnh SQL lấy mã OTP đã mã hóa và thời hạn OTP
    // Chỉ lấy thông tin của tài khoản có ID tương ứng
    $stmt = $conn->prepare(
        "SELECT reset_otp, otp_expires
         FROM users WHERE id = ?"
    );

    // Thực thi câu lệnh SQL với ID tài khoản đang được lưu trong Session
    $stmt->execute([$_SESSION["reset_user_id"]]);

    // Lấy thông tin OTP từ database
    $user = $stmt->fetch();

    // Kiểm tra các điều kiện xác minh OTP:
    if (
        // 1. Tài khoản phải tồn tại trong database
        $user &&

        // 2. Mã OTP đã được tạo và lưu trong database
        $user["reset_otp"] &&

        // 3. Thời hạn OTP phải tồn tại
        $user["otp_expires"] &&

        // 4. OTP vẫn còn hạn sử dụng
        // strtotime() chuyển thời gian trong database thành timestamp
        // time() lấy thời gian hiện tại
        $user["otp_expires"] &&
        strtotime($user["otp_expires"]) > time() &&

        // 5. Kiểm tra OTP người dùng nhập có khớp với OTP đã mã hóa không
        password_verify($otp, $user["reset_otp"])
    ) {

        // Nếu tất cả điều kiện đều đúng
        // Lưu trạng thái đã xác minh OTP thành công vào Session
        $_SESSION["otp_verified"] = true;

        // Chuyển đến trang đặt lại mật khẩu
        header("Location: reset-password.php");

        // Dừng chương trình sau khi chuyển hướng
        exit;

    } else {

        // Nếu OTP sai, không tồn tại hoặc đã hết hạn
        // Hiển thị thông báo lỗi
        $message = "OTP không đúng hoặc đã hết hạn!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- Khai báo bảng mã UTF-8 để hiển thị tiếng Việt -->
    <meta charset="UTF-8">

    <!-- Tiêu đề trang xác minh OTP -->
    <title>Xác minh OTP</title>

    <!-- Kết nối file CSS để định dạng giao diện -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- Khung chứa giao diện xác minh OTP -->
<div class="container">

    <!-- Tiêu đề trang -->
    <h2>XÁC MINH OTP</h2>

    <!-- Thông báo cho người dùng biết mã OTP đã được gửi qua email -->
    <p>Mã OTP gồm 6 chữ số đã được gửi đến email của bạn.</p>

    <!-- Hiển thị thông báo lỗi nếu OTP không hợp lệ -->
    <!-- htmlspecialchars() giúp hiển thị nội dung an toàn -->
    <p class="message"><?= htmlspecialchars($message) ?></p>

    <!-- Form gửi mã OTP đến chính trang verify-otp.php -->
    <form method="POST">

        <!-- Ô nhập mã OTP -->
        <!-- type="text": cho phép nhập mã OTP -->
        <!-- name="otp": PHP sử dụng $_POST["otp"] để lấy dữ liệu -->
        <!-- maxlength="6": giới hạn tối đa 6 ký tự -->
        <!-- pattern="[0-9]{6}": yêu cầu đúng 6 chữ số -->
        <!-- required: bắt buộc phải nhập -->
        <input type="text" name="otp"
               placeholder="Nhập mã OTP"
               maxlength="6"
               pattern="[0-9]{6}"
               required>

        <!-- Nút gửi mã OTP để xác minh -->
        <button type="submit">Xác minh</button>
    </form>
</div>

</body>
</html>


