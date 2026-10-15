
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
    <title>TestSuViet.com</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2.2">
</head>
<body>

<header class="header">
    <div class="container header-container">

        <div class="logo">
            <a href="index.php">TestSuViet.com</a>
        </div>

        <button class="menu-toggle"
                type="button"
                onclick="toggleMenu()"
                aria-label="Mở menu">
            ☰
        </button>

        <nav class="nav" id="navMenu">
            <a href="index.php">Trang chủ</a>
            <a href="about.php">Giới thiệu</a>

            <?php if ($isLoggedIn): ?>
                <a href="profile.php">
                    Xin chào,
                    <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a href="../api/auth.php?action=logout">Đăng xuất</a>
            <?php else: ?>
                <a href="login.php">Đăng nhập</a>
                <a href="register.php">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if ($isTeacher): ?>
 <aside id="teacherSidebar" class="teacher-sidebar">
    <a href="index.php" class="sidebar-item" title="Trang chủ">
        <span>🏠</span>
        <span class="sidebar-text">Trang chủ</span>
    </a>

    <a href="teacher_question.php"
       class="sidebar-item"
       title="Ngân hàng câu hỏi">
        <span>📚</span>
        <span class="sidebar-text">Ngân hàng câu hỏi</span>
    </a>

    <a href="teacher_exam.php"
       class="sidebar-item"
       title="Tạo đề thi">
        <span>📝</span>
        <span class="sidebar-text">Tạo đề thi</span>
    </a>
</aside>
    <button
        type="button"
        class="teacher-sidebar-toggle"
        onclick="toggleTeacherSidebar()"
        aria-controls="teacherSidebar"
        aria-expanded="true">
        ☰
    </button>
<?php endif; ?>

<script>

function toggleMenu() {
    const nav = document.getElementById("navMenu");

    if (nav) {
        nav.classList.toggle("show");
    }
}

function toggleTeacherSidebar() {
    const sidebar = document.getElementById("teacherSidebar");
    const button = document.querySelector(".teacher-sidebar-toggle");

    if (!sidebar) return;

    sidebar.classList.toggle("collapsed");
    document.body.classList.toggle("teacher-sidebar-hidden");

    const isCollapsed = sidebar.classList.contains("collapsed");

    if (button) {
        button.setAttribute("aria-expanded", String(!isCollapsed));
    }
}

// Luôn thu gọn thanh bên khi tải lại trang
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("teacherSidebar");
    const button = document.querySelector(".teacher-sidebar-toggle");

    if (sidebar) {
        sidebar.classList.add("collapsed");
        document.body.classList.add("teacher-sidebar-hidden");
    }

    if (button) {
        button.setAttribute("aria-expanded", "false");
    }
});

</script>
</body>
</html>
