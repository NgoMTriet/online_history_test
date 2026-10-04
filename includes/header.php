<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TestSuViet.com</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header class="header">
    <div class="container header-container">

        <div class="logo">
            <a href="index.php">TestSuViet.com</a>
        </div>

        <!-- Nút 3 gạch -->
        <button class="menu-toggle" onclick="toggleMenu()">
            ☰
        </button>

        <nav class="nav" id="navMenu">
            <a href="index.php">Trang chủ</a>
            <a href="#">Giới thiệu</a>
        </nav>
    </div>
</header>
<script>
function toggleMenu() {
    document.getElementById("navMenu").classList.toggle("show");
}
</script>