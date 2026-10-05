<?php
// 1. KHAI BÁO THÔNG TIN DATABASE
// Địa chỉ máy chủ MySQL (Laragon chạy trên máy hiện tại)
$host = "localhost";
// Tên database đã tạo trong phpMyAdmin
$dbname = "auth_db";
// Tên tài khoản đăng nhập MySQL
$username = "root";
// Mật khẩu MySQL (để trống nếu chưa thiết lập mật khẩu)
$password = "";
// 2. KẾT NỐI PHP VỚI MYSQL
try {
    // Tạo đối tượng PDO để kết nối đến database
    $conn = new PDO(
        // Chuỗi kết nối: MySQL, địa chỉ host, tên database và bảng mã UTF8MB4
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        // Tên tài khoản MySQL
        $username,
        // Mật khẩu MySQL
        $password,
        [
            // Thiết lập PDO tự động phát sinh ngoại lệ khi xảy ra lỗi
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Thiết lập chế độ lấy dữ liệu mặc định dạng mảng kết hợp
            // Có thể truy cập dữ liệu bằng tên cột, ví dụ: $user["email"]
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC

        ]
    );
// 3. XỬ LÝ LỖI KẾT NỐI DATABASE
} catch (PDOException $e) {
    // Nếu kết nối thất bại, hiển thị thông báo lỗi và dừng chương trình
    // getMessage() lấy nội dung chi tiết của lỗi
    die("Lỗi kết nối database: " . $e->getMessage());
}
// 4. KHỞI TẠO SESSION
// Kiểm tra xem Session đã được khởi tạo hay chưa
if (session_status() === PHP_SESSION_NONE) {
    // Nếu Session chưa tồn tại thì tiến hành khởi tạo
    // Session dùng để lưu thông tin đăng nhập của người dùng
    session_start();
}
?>
```
