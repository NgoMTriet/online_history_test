<?php
require_once __DIR__ . '/../api/be_profile.php';

// Chỉ bao gồm menu nếu KHÔNG PHẢI là trang profile của giáo viên
if (!$is_teacher) {
    include __DIR__ . '/../includes/menu.php';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - SuViet</title>

    <link rel="stylesheet" href="/../assets/css/profile.css">
</head>

<body<?= $is_teacher ? '' : ' class="pf-body"' ?>>

<?php if ($is_teacher): ?>

    <!-- GIAO DIỆN GIÁO VIÊN -->
    <div class="et-overlay" id="etOverlay" onclick="etToggleSidebar()"></div>

    <div class="et-shell">

        <?php include __DIR__ . '/../includes/teacher_sidebar.php'; ?>

        <div class="et-workspace">

            <header class="et-topbar">
                <div class="et-topbar-left">
                    <button type="button" class="et-mobile-toggle"
                        onclick="etToggleSidebar()" aria-label="Mở menu">☰</button>

                    <div>
                        <div class="et-crumb">TEACHER / GIÁO VIÊN</div>
                        <div class="et-top-title">Hồ sơ cá nhân</div>
                    </div>
                </div>

                <div class="et-user">
                    <span class="et-avatar"><?= e($initials) ?></span>
                    <span class="et-user-name"><?= e($display_name) ?></span>
                </div>
            </header>

<main>

<?php endif; ?>
      <div class="pf-wrap">
        <div class="pf-head">
            <h1 class="pf-title">Thông tin cá nhân</h1>
            <p class="pf-subtitle">Xem và cập nhật hồ sơ, mật khẩu của bạn.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="pf-notice <?= $is_success ? 'pf-notice--ok' : 'pf-notice--error' ?>" role="<?= $is_success ? 'status' : 'alert' ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <div class="pf-layout">
            <!-- ===== Thẻ tóm tắt ===== -->
            <aside class="pf-card pf-summary">
                <div class="pf-summary-top">
                    <div class="pf-seal" aria-hidden="true"><?= e($initials) ?></div>
                    <h2 class="pf-name"><?= e($user_info['full_name'] ?? '') ?></h2>
                    <p class="pf-username">@<?= e($user_info['username'] ?? '') ?></p>
                    <span class="pf-role"><?= e($role_label) ?></span>
                </div>
                <dl class="pf-facts">
                    <div class="pf-fact">
                        <dt>Email</dt>
                        <dd><?= e($user_info['email'] ?? '') ?></dd>
                    </div>
                    <div class="pf-fact">
                        <dt>Lớp</dt>
                        <dd<?= $class_name_value === '' ? ' class="is-empty"' : '' ?>><?= $class_name_value !== '' ? e($class_name_value) : 'Chưa cập nhật' ?></dd>
                    </div>
                    <div class="pf-fact">
                        <dt>Ngày sinh</dt>
                        <dd<?= empty($dob_value) ? ' class="is-empty"' : '' ?>><?= !empty($dob_value) ? e(date('d/m/Y', strtotime($dob_value))) : 'Chưa cập nhật' ?></dd>
                    </div>
                </dl>
            </aside>

            <!-- ===== Form cập nhật ===== -->
            <form class="pf-card pf-form-card" method="POST">
                <input type="hidden" name="action" value="update_profile">

                <section class="pf-section">
                    <h2 class="pf-section-title">Thông tin tài khoản</h2>
                    <p class="pf-section-desc">Tên tài khoản không thể thay đổi.</p>

                    <div class="pf-field">
                        <label for="username">Tên tài khoản</label>
                        <input id="username" type="text" value="<?= e($user_info['username'] ?? '') ?>" disabled>
                    </div>
                    <div class="pf-field">
                        <label for="full_name">Họ và tên</label>
                        <input id="full_name" type="text" name="full_name" value="<?= e($user_info['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="pf-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="<?= e($user_info['email'] ?? '') ?>" required>
                    </div>
                    <div class="pf-row">
                        <div class="pf-field">
                            <label for="class_name">Lớp học</label>
                            <input id="class_name" type="text" name="class_name" placeholder="Ví dụ: 9A1" value="<?= e($user_info['class_name'] ?? '') ?>">
                        </div>
                        <div class="pf-field">
                            <label for="dob">Ngày sinh</label>
                            <input id="dob" type="date" name="dob" value="<?= e($user_info['dob'] ?? '') ?>">
                        </div>
                    </div>
                </section>

                <section class="pf-section">
                    <h2 class="pf-section-title">Đổi mật khẩu</h2>
                    <p class="pf-section-desc">Bỏ trống nếu bạn không muốn đổi mật khẩu.</p>

                    <div class="pf-field">
                        <label for="current_password">Mật khẩu hiện tại</label>
                        <input id="current_password" type="password" name="current_password" autocomplete="current-password">
                    </div>
                    <div class="pf-row">
                        <div class="pf-field">
                            <label for="new_password">Mật khẩu mới</label>
                            <input id="new_password" type="password" name="new_password" minlength="8" autocomplete="new-password">
                            <p class="pf-hint">Ít nhất 8 ký tự.</p>
                        </div>
                        <div class="pf-field">
                            <label for="confirm_password">Xác nhận mật khẩu mới</label>
                            <input id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password">
                        </div>
                    </div>
                </section>

                <div class="pf-actions">
                    <button class="pf-btn pf-btn-ghost" type="reset">Hoàn tác</button>
                    <button class="pf-btn pf-btn-primary" type="submit">Lưu thay đổi</button>
                </div>
            </form>
        </div>
      </div>

<?php if ($is_teacher): ?>
    </main>
  </div>
</div>
<?php else: ?>
</main>
<?php endif; ?>

<script>
function etToggleSidebar() {
  const sidebar = document.getElementById('etSidebar');
  const overlay = document.getElementById('etOverlay');
  if (sidebar) sidebar.classList.toggle('open');
  if (overlay) overlay.classList.toggle('show');
}
</script>
</body>
</html>