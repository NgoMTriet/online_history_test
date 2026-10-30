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
    <style>
        /* CSS cho Menu ẩn (Dropdown Navigation) */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .user-nav-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-toggle-btn {
            background: #34495e;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .menu-toggle-btn:hover {
            background: #2c3e50;
        }

        /* Nav Menu xổ xuống */
        .nav-dropdown {
            display: none;
            position: absolute;
            top: 120%;
            right: 0;
            background-color: white;
            min-width: 220px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            border-radius: 8px;
            overflow: hidden;
            z-index: 1000;
            border: 1px solid #e2e8f0;
        }

        .nav-dropdown.show {
            display: block;
            animation: fadeIn 0.2s ease-in-out;
        }

        .nav-dropdown a {
            color: #2d3748;
            padding: 12px 18px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            font-weight: 500;
            border-bottom: 1px solid #f7fafc;
            transition: background 0.2s, color 0.2s;
        }

        .nav-dropdown a:last-child {
            border-bottom: none;
        }

        .nav-dropdown a:hover {
            background-color: #f8fafc;
            color: #3182ce;
        }

        .nav-dropdown a.logout-link {
            color: #e53e3e;
        }

        .nav-dropdown a.logout-link:hover {
            background-color: #fff5f5;
            color: #c53030;
        }

        .user-greeting-badge {
            background: #edf2f7;
            padding: 10px 18px;
            font-weight: bold;
            color: #2b6cb0;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.9rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="container header-container">

        <!-- LOGO -->
        <div class="logo">
            <a href="index.php" style="font-weight: bold; font-size: 1.4rem; color: #2c3e50; text-decoration: none;">
                🇻🇳 TestSuViet.com
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

// Bật/tắt Sidebar Giáo viên
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

// Thu gọn sidebar giáo viên khi load trang
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