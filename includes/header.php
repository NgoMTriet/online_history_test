<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TestSuViet.com</title>
    <!-- CSS tính tương đối từ thư mục public/ -->
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
</head>

<body>

<header class="header">
    <div class="container header-container">
        <div class="logo">
            <!-- Sửa từ public/index.php thành index.php -->
            <a href="index.php">
                TestSuViet.com
            </a>
        </div>

        <button class="menu-toggle" onclick="toggleMenu()">
            ☰
        </button>

        <nav class="nav" id="navMenu">
            <!-- Sửa các đường dẫn bỏ chữ 'public/' -->
            <a href="index.php">
                Trang chủ
            </a>
            <a href="#">
                Giới thiệu
            </a>
            <a href="login.php">
                Đăng nhập
            </a>
            <a href="register.php">
                Đăng ký
            </a>
        </nav>
    </div>
</header>

<script>
function toggleMenu() {
    document.getElementById("navMenu").classList.toggle("show");
}
</script>
