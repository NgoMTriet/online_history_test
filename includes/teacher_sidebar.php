<?php
// Xác định trang hiện tại để tô sáng menu đang chọn
$current_page = basename($_SERVER['PHP_SELF']);
?>
<head>
    <link rel="stylesheet" href="/../assets/css/UI_teacher.css">
</head>
<aside class="et-sidebar" id="etSidebar">
    <div>
        <a class="et-brand" href="teacher_dashboard.php">
            <span class="et-brand-icon">VN</span>
            <span>
                <strong>SuVietTeacher</strong>
                <small>Hệ thống của giáo viên</small>
            </span>
        </a>

        <nav class="et-nav">
            <div class="et-nav-label">Menu chính</div>

            <a href="teacher_dashboard.php"
               class="<?= $current_page === 'teacher_dashboard.php' ? 'active' : '' ?>">
                <span>Tổng quan</span>
            </a>

            <a href="teacher_exam.php"
               class="<?= $current_page === 'teacher_exam.php' ? 'active' : '' ?>">
                <span>Tạo đề thi</span>
            </a>

            <a href="teacher_question.php"
               class="<?= $current_page === 'teacher_question.php' ? 'active' : '' ?>">
                <span>Ngân hàng câu hỏi</span>
            </a>

            <a href="teacher_view_result.php"
               class="<?= $current_page === 'teacher_view_result.php' ? 'active' : '' ?>">
                <span>Kết quả làm bài</span>
            </a>

            <div class="et-nav-label" style="margin-top:25px">
                Tài khoản
            </div>

            <a href="profile.php"
               class="<?= $current_page === 'profile.php' ? 'active' : '' ?>">
                <span>Hồ sơ cá nhân</span>
            </a>
        </nav>
    </div>

    <div class="et-sidebar-foot">
         <a class="btn-logout" href="../api/auth.php?action=login">Đăng xuất</a>
    </div>
</aside>