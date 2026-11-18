<?php
require_once __DIR__ . '/../api/be_profile.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - SuViet</title>

    <link rel="stylesheet" href="../assets/css/profile.css">
    <?php if ($is_teacher): ?>
        <link rel="stylesheet" href="../assets/css/UI_teacher.css">
        <style>
            body { margin: 0; padding: 0; }
            .et-content .pf-wrap { max-width: 100%; margin: 0; padding: 0; }
        </style>
    <?php endif; ?>
</head>

<body>

<?php if ($is_teacher): ?>

    <!-- GIAO DIỆN GIÁO VIÊN -->
    <div class="et-overlay" id="etOverlay" onclick="etToggleSidebar()"></div>

    <?php include __DIR__ . '/../includes/teacher_sidebar.php'; ?>

    <div class="et-workspace">

        <header class="et-topbar">
            <div class="et-topbar-left">
                <button type="button"
                    class="et-mobile-toggle"
                    onclick="etToggleSidebar()"
                    aria-label="Mở menu">☰</button>

                <div>
                    <div class="et-crumb">TEACHER / GIÁO VIÊN</div>
                    <div class="et-top-title">Hồ sơ cá nhân</div>
                </div>
            </div>

            <div class="et-user">
                <span class="et-avatar">GV</span>
                <span class="et-user-name"><?= e($display_name) ?></span>
            </div>
        </header>

        <main class="et-content">
            <div class="container">

<?php else: ?>

    <!-- GIAO DIỆN HỌC SINH -->
    <?php include __DIR__ . '/../includes/menu.php'; ?>
    <div class="container">

<?php endif; ?>

<div class="pf-wrap">

    <div class="pf-head">
        <h1 class="pf-title">Thông tin cá nhân</h1>
        <p class="pf-subtitle">
            Xem và cập nhật hồ sơ, mật khẩu của bạn.
        </p>
    </div>

    <div class="pf-layout">

        <!-- THẺ TÓM TẮT -->
        <aside class="pf-card pf-summary">

            <div class="pf-summary-top">
                <div class="pf-seal" aria-hidden="true">
                    <?= e($initials) ?>
                </div>

                <h2 class="pf-name">
                    <?= e($user_info['full_name'] ?? '') ?>
                </h2>

                <p class="pf-username">
                    @<?= e($user_info['username'] ?? '') ?>
                </p>

                <span class="pf-role">
                    <?= e($role_label) ?>
                </span>
            </div>

            <dl class="pf-facts">

                <div class="pf-fact">
                    <dt>Email</dt>
                    <dd><?= e($user_info['email'] ?? '') ?></dd>
                </div>

                <?php if ($is_teacher): ?>

                    <div class="pf-fact">
                        <dt>Trường công tác</dt>
                        <dd<?= empty($teacher_info['school_name']) ? ' class="is-empty"' : '' ?>>
                            <?= !empty($teacher_info['school_name'])
                                ? e($teacher_info['school_name'])
                                : 'Chưa cập nhật' ?>
                        </dd>
                    </div>

                    <div class="pf-fact">
                        <dt>Khối giảng dạy</dt>
                        <dd<?= empty($teacher_info['teaching_grades']) ? ' class="is-empty"' : '' ?>>
                            <?= !empty($teacher_info['teaching_grades'])
                                ? e('Lớp ' . implode(', ', $teacher_info['teaching_grades']))
                                : 'Chưa cập nhật' ?>
                        </dd>
                    </div>

                    <div class="pf-fact">
                        <dt>Môn giảng dạy</dt>
                        <dd<?= empty($teacher_info['teaching_subjects']) ? ' class="is-empty"' : '' ?>>
                            <?= !empty($teacher_info['teaching_subjects'])
                                ? e(implode(', ', $teacher_info['teaching_subjects']))
                                : 'Chưa cập nhật' ?>
                        </dd>
                    </div>

                <?php else: ?>

                    <div class="pf-fact">
                        <dt>Lớp</dt>
                        <dd<?= empty($user_info['class_name']) ? ' class="is-empty"' : '' ?>>
                            <?= !empty($user_info['class_name'])
                                ? e($user_info['class_name'])
                                : 'Chưa cập nhật' ?>
                        </dd>
                    </div>

                <?php endif; ?>

                <div class="pf-fact">
                    <dt>Ngày sinh</dt>
                    <dd<?= empty($user_info['dob']) ? ' class="is-empty"' : '' ?>>
                        <?= !empty($user_info['dob'])
                            ? e(date('d/m/Y', strtotime($user_info['dob'])))
                            : 'Chưa cập nhật' ?>
                    </dd>
                </div>

            </dl>
        </aside>

        <!-- FORM CẬP NHẬT -->
        <form class="pf-card pf-form-card" method="POST">

            <input type="hidden" name="action" value="update_profile">

            <?php if ($is_teacher): ?>

                <section class="pf-section">

                    <h2 class="pf-section-title">Thông tin giảng dạy</h2>

                    <p class="pf-section-desc">
                        Cập nhật trường công tác, khối và môn học bạn đang giảng dạy.
                    </p>

                    <div class="pf-field">
                        <label for="school_name">Trường công tác</label>
                        <input
                            id="school_name"
                            type="text"
                            name="school_name"
                            placeholder="Ví dụ: THCS Nguyễn Trãi"
                            value="<?= e($teacher_info['school_name'] ?? '') ?>"
                            required
                        >
                    </div>

                    <!-- KHỐI GIẢNG DẠY -->
                    <div class="pf-field">
                        <label>Khối giảng dạy</label>

                        <details class="pf-multiselect">
                            <summary>
                                <span class="pf-select-text"
                                    data-placeholder="Chọn khối giảng dạy">
                                    Chọn khối giảng dạy
                                </span>
                                <span class="pf-select-arrow">▼</span>
                            </summary>

                            <div class="pf-dropdown-list">
                                <?php foreach (['6', '7', '8', '9'] as $grade): ?>
                                    <label class="pf-dropdown-option">
                                        <input
                                            type="checkbox"
                                            name="teaching_grades[]"
                                            value="<?= $grade ?>"
                                            <?= in_array($grade, $teacher_info['teaching_grades'] ?? [])
                                                ? 'checked'
                                                : '' ?>
                                        >
                                        <span>Lớp <?= $grade ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </div>

                    <!-- MÔN GIẢNG DẠY -->
                    <div class="pf-field">
                        <label>Môn giảng dạy</label>

                        <?php
                        $subjectOptions = [
                            'Ngữ văn',
                            'Toán',
                            'Ngoại ngữ 1',
                            'Giáo dục công dân',
                            'Lịch sử và Địa lí',
                            'Khoa học tự nhiên',
                            'Công nghệ',
                            'Tin học',
                            'Giáo dục thể chất',
                            'Âm nhạc',
                            'Mĩ thuật',
                            'Hoạt động trải nghiệm, hướng nghiệp',
                            'Nội dung giáo dục của địa phương',
                            'Tiếng dân tộc thiểu số',
                            'Ngoại ngữ 2'
                        ];
                        ?>

                        <details class="pf-multiselect">
                            <summary>
                                <span class="pf-select-text"
                                    data-placeholder="Chọn môn giảng dạy">
                                    Chọn môn giảng dạy
                                </span>
                                <span class="pf-select-arrow">▼</span>
                            </summary>

                            <div class="pf-dropdown-list">
                                <?php foreach ($subjectOptions as $subject): ?>
                                    <label class="pf-dropdown-option">
                                        <input
                                            type="checkbox"
                                            name="teaching_subjects[]"
                                            value="<?= e($subject) ?>"
                                            <?= in_array($subject, $teacher_info['teaching_subjects'] ?? [])
                                                ? 'checked'
                                                : '' ?>
                                        >
                                        <span><?= e($subject) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </div>

                </section>

            <?php endif; ?>

            <!-- THÔNG TIN TÀI KHOẢN -->
            <section class="pf-section">

                <h2 class="pf-section-title">Thông tin tài khoản</h2>

                <p class="pf-section-desc">
                    Tên tài khoản không thể thay đổi.
                </p>

                <div class="pf-field">
                    <label for="username">Tên tài khoản</label>
                    <input
                        id="username"
                        type="text"
                        value="<?= e($user_info['username'] ?? '') ?>"
                        disabled
                    >
                </div>

                <div class="pf-field">
                    <label for="full_name">Họ và tên</label>
                    <input
                        id="full_name"
                        type="text"
                        name="full_name"
                        value="<?= e($user_info['full_name'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="pf-field">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="<?= e($user_info['email'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="pf-row">

                    <?php if (!$is_teacher): ?>
                        <div class="pf-field">
                            <label for="class_name">Lớp học</label>
                            <input
                                id="class_name"
                                type="text"
                                name="class_name"
                                placeholder="Ví dụ: 9A1"
                                value="<?= e($user_info['class_name'] ?? '') ?>"
                            >
                        </div>
                    <?php endif; ?>

                    <div class="pf-field">
                        <label for="dob">Ngày sinh</label>
                        <input
                            id="dob"
                            type="date"
                            name="dob"
                            value="<?= e($user_info['dob'] ?? '') ?>"
                        >
                    </div>

                </div>

            </section>

            <!-- ĐỔI MẬT KHẨU -->
            <section class="pf-section">

                <h2 class="pf-section-title">Đổi mật khẩu</h2>

                <p class="pf-section-desc">
                    Bỏ trống nếu bạn không muốn đổi mật khẩu.
                </p>

                <div class="pf-field">
                    <label for="current_password">Mật khẩu hiện tại</label>
                    <input
                        id="current_password"
                        type="password"
                        name="current_password"
                        autocomplete="current-password"
                    >
                </div>

                <div class="pf-row">

                    <div class="pf-field">
                        <label for="new_password">Mật khẩu mới</label>
                        <input
                            id="new_password"
                            type="password"
                            name="new_password"
                            minlength="8"
                            autocomplete="new-password"
                        >
                        <p class="pf-hint">Ít nhất 8 ký tự.</p>
                    </div>

                    <div class="pf-field">
                        <label for="confirm_password">Xác nhận mật khẩu mới</label>
                        <input
                            id="confirm_password"
                            type="password"
                            name="confirm_password"
                            minlength="8"
                            autocomplete="new-password"
                        >
                    </div>

                </div>

            </section>

            <div class="pf-actions">
                <button class="pf-btn pf-btn-ghost" type="reset">
                    Hoàn tác
                </button>

                <button class="pf-btn pf-btn-primary" type="submit">
                    Lưu thay đổi
                </button>
            </div>

        </form>

    </div>
</div>

<?php if ($is_teacher): ?>
            </div>
        </main>
    </div>

    <script>
    function etToggleSidebar() {
        const sidebar = document.getElementById('etSidebar');
        const overlay = document.getElementById('etOverlay');
        if (sidebar) sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('show');
    }
    </script>
<?php else: ?>
    </div>
<?php endif; ?>

<script>
document.querySelectorAll('.pf-multiselect').forEach(function (dropdown) {

    const summaryText = dropdown.querySelector('.pf-select-text');

    const checkboxes = dropdown.querySelectorAll(
        'input[type="checkbox"]'
    );

    function updateSummary() {

        const selected = Array.from(checkboxes)
            .filter(function (checkbox) {
                return checkbox.checked;
            })
            .map(function (checkbox) {
                return checkbox.closest('label')
                    .querySelector('span')
                    .textContent.trim();
            });

        if (selected.length > 0) {
            summaryText.textContent = selected.join(', ');
            summaryText.classList.add('has-value');
        } else {
            summaryText.textContent = summaryText.dataset.placeholder;
            summaryText.classList.remove('has-value');
        }
    }

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateSummary);
    });

    updateSummary();
});
</script>

</body>
</html>
