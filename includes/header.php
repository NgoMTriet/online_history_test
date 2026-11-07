<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$displayName = $_SESSION['full_name']
    ?? $_SESSION['username']
    ?? 'Người dùng';

$isTeacher = $isLoggedIn
    && strtoupper($_SESSION['role'] ?? '') === 'TEACHER';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuViet.com</title>
    <link rel="stylesheet" href="assets/css/header.css?v=2.3">
</head>
<body>

<header class="header">
    <div class="container header-container">

        <!-- LOGO -->
        <div class="logo">           
            <a href="index.php">
                <img src="image/logo.png" alt="Logo SuViet">
            </a>
        </div>
        <!-- KHU VỰC MENU NÚT BẤM -->
        <div class="user-nav-wrapper">
            <button class="menu-toggle-btn" type="button" onclick="toggleMenu(event)" aria-label="Mở menu">
                <span>☰</span>
                <?php if ($isLoggedIn): ?>
                    <span style="font-size: 0.9rem;"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                    <span style="font-size: 0.9rem;">Menu</span>
                <?php endif; ?>
            </button>

            <!-- MENU XỔ XUỐNG (MENU ẨN) -->
            <nav class="nav-dropdown" id="navMenu">
                <?php if ($isLoggedIn): ?>
                    <div class="user-greeting-badge">
                        👤 <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <a href="index.php">🏠 Trang chủ</a>
                <a href="about.php">ℹ️ Giới thiệu</a>

                <?php if ($isTeacher): ?>
                    <a href ="teacher_dashboard.php">Trang chủ giáo viên</a>
                    <a href="teacher_question.php">📚 Ngân hàng câu hỏi</a>
                    <a href="teacher_exam.php">📝 Tạo đề thi</a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <a href="history.php">📜 Lịch sử làm bài</a>
                    <a href="profile.php">⚙️ Trang cá nhân</a>
                    <a href="../api/auth.php?action=logout" class="logout-link">🚪 Đăng xuất</a>
                <?php else: ?>
                    <a href="login.php">🔑 Đăng nhập</a>
                    <a href="register.php">📝 Đăng ký</a>
                <?php endif; ?>
            </nav>
        </div>

    </div>
</header>


<script>
// Bật/tắt Menu ẩn (Dropdown)
function toggleMenu(event) {
    if (event) event.stopPropagation();
    const nav = document.getElementById("navMenu");
    if (nav) {
        nav.classList.toggle("show");
    }
}

// Tự động đóng Menu ẩn khi người dùng nhấn chuột ra ngoài
document.addEventListener("click", function (e) {
    const nav = document.getElementById("navMenu");
    const button = document.querySelector(".menu-toggle-btn");
    
    if (nav && nav.classList.contains("show")) {
        if (!nav.contains(e.target) && !button.contains(e.target)) {
            nav.classList.remove("show");
        }
    }
});
</script>
</body>
</html>