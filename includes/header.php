
<?php
// Khởi tạo session để đọc thông tin người dùng
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);

$displayName = $_SESSION['full_name']
    ?? $_SESSION['username']
    ?? 'Người dùng';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TestSuViet.com</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2.1">
</head>

<body>

<header class="header">
    <div class="container header-container">

        <div class="logo">
            <a href="index.php">TestSuViet.com</a>
        </div>

        <button class="menu-toggle" onclick="toggleMenu()">
            ☰
        </button>

        <nav class="nav" id="navMenu">

            <a href="index.php">Trang chủ</a>

            <a href="#">Giới thiệu</a>

            <?php if ($isLoggedIn): ?>

                <a href="profile.php">
                    Xin chào, <?= htmlspecialchars(
                        $displayName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </a>

                <a href="../api/auth.php?action=logout">
                    Đăng xuất
                </a>

            <?php else: ?>

                <a href="login.php">Đăng nhập</a>

                <a href="register.php">Đăng ký</a>

            <?php endif; ?>

        </nav>
    </div>
</header>

<script>
function toggleMenu() {
    document.getElementById("navMenu").classList.toggle("show");
}
</script>
</body>
</html>
