<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$message = '';
$is_success = false;
$user_info = [];

function getUserInfo(PDO $conn, int $userId): array
{
    $stmt = $conn->prepare(
        'SELECT user_id, username, password_hash, full_name,
                email, class_name, dob, role, status
         FROM users
         WHERE user_id = ?
         LIMIT 1'
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION = [];
        session_destroy();
        header('Location: /login.php?error=account');
        exit;
    }
    return $user;
}

try {
    $user_info = getUserInfo($conn, $user_id);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $className = trim($_POST['class_name'] ?? '');
        $dobInput = trim($_POST['dob'] ?? '');
        $password = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($fullName === '' || $email === '') {
            throw new Exception('Họ tên và email không được để trống.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Địa chỉ email không hợp lệ.');
        }

        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1');
        $stmt->execute([$email, $user_id]);

        if ($stmt->fetch()) {
            throw new Exception('Email này đã được tài khoản khác sử dụng.');
        }

        $changePassword = $newPassword !== '';

        if ($changePassword) {
            if (!password_verify($password, $user_info['password_hash'])) {
                throw new Exception('Mật khẩu hiện tại không chính xác.');
            }
            if (strlen($newPassword) < 8) {
                throw new Exception('Mật khẩu mới phải có ít nhất 8 ký tự.');
            }
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Mật khẩu xác nhận không khớp.');
            }
        }

        $dob = !empty($dobInput) ? $dobInput : null;

        if ($changePassword) {
            $stmt = $conn->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, class_name = ?, dob = ?, password_hash = ?
                 WHERE user_id = ?'
            );
            $stmt->execute([$fullName, $email, $className, $dob, password_hash($newPassword, PASSWORD_DEFAULT), $user_id]);
        } else {
            $stmt = $conn->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, class_name = ?, dob = ?
                 WHERE user_id = ?'
            );
            $stmt->execute([$fullName, $email, $className, $dob, $user_id]);
        }

        $_SESSION['full_name'] = $fullName;
        $_SESSION['username'] = $user_info['username'];
        $_SESSION['email'] = $email;

        $user_info = getUserInfo($conn, $user_id);
        $message = 'Cập nhật thông tin cá nhân thành công.';
        $is_success = true;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $message = 'Không thể cập nhật thông tin. Vui lòng kiểm tra cấu trúc CSDL.';
} catch (Exception $e) {
    $message = $e->getMessage();
}

/* ------------------------------------------------------------------
 * Chọn khung giao diện theo vai trò
 * ------------------------------------------------------------------ */
$role = $user_info['role'] ?? ($_SESSION['role'] ?? 'STUDENT');
$is_teacher = $role === 'TEACHER';
$is_admin = $role === 'ADMIN';

$role_labels = ['STUDENT' => 'Học viên', 'TEACHER' => 'Giáo viên', 'ADMIN' => 'Quản trị viên'];
$role_label = $role_labels[$role] ?? $role;

$display_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? $role_label;
$initials = mb_strtoupper(mb_substr(trim($display_name) !== '' ? trim($display_name) : '?', 0, 1, 'UTF-8'), 'UTF-8');

/* Hàm rút gọn việc escape HTML */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$class_name_value = trim((string) ($user_info['class_name'] ?? ''));
$dob_value = $user_info['dob'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - TestSuViet</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600&family=Noto+Serif:wght@700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/../assets/css/style.css">
    <link rel="stylesheet" href="/../assets/css/profile.css">
</head>

<body<?= $is_teacher ? '' : ' class="pf-body"' ?>>

<?php if ($is_teacher): ?>
<!-- ================= KHUNG GIÁO VIÊN ================= -->
<div class="et-overlay" id="etOverlay" onclick="etToggleSidebar()"></div>
<div class="et-shell">
  <aside class="et-sidebar" id="etSidebar">
    <div>
      <a class="et-brand" href="teacher_dashboard.php">
        <span class="et-brand-icon">VN</span>
        <span><strong>SuVietTeacher</strong><small>Hệ thống của giáo viên</small></span>
      </a>
      <nav class="et-nav">
        <div class="et-nav-label">Menu chính</div>
        <a href="teacher_dashboard.php"><span>Tổng quan</span></a>
        <a href="teacher_exam.php"><span>Tạo đề thi</span></a>
        <a href="teacher_question.php"><span>Ngân hàng câu hỏi</span></a>
        <a href="teacher_view_result.php"><span>Kết quả làm bài</span></a>
        <div class="et-nav-label" style="margin-top:25px">Tài khoản</div>
        <a href="profile.php" class="active"><span>Hồ sơ cá nhân</span></a>
      </nav>
    </div>
    <div class="et-sidebar-foot"><a href="login.php"><span>⇥</span> Đăng xuất</a></div>
  </aside>
  <div class="et-workspace">
    <header class="et-topbar">
      <div class="et-topbar-left">
        <button type="button" class="et-mobile-toggle" onclick="etToggleSidebar()" aria-label="Mở menu">☰</button>
        <div><div class="et-crumb">TEACHER / GIÁO VIÊN</div><div class="et-top-title">Hồ sơ cá nhân</div></div>
      </div>
      <div class="et-user"><span class="et-avatar"><?= e($initials) ?></span><span class="et-user-name"><?= e($display_name) ?></span></div>
    </header>
    <main class="et-content">
<?php else: ?>
<!-- ================= KHUNG HỌC VIÊN / ADMIN ================= -->
<header class="st-topbar">
  <div class="st-topbar-inner">
    <div class="st-left">
      <a class="st-brand" href="index.php">TestSuViet</a>
      <nav class="st-nav" aria-label="Menu chính">
        <a href="index.php">Trang chủ</a>
        <?php if ($is_admin): ?>
          <a href="admin_dashboard.php">Trang quản trị</a>
        <?php else: ?>
          <a href="history.php">Lịch sử làm bài</a>
        <?php endif; ?>
        <a href="profile.php" class="active" aria-current="page">Hồ sơ cá nhân</a>
      </nav>
    </div>
    <div class="st-user">
      <span><strong><?= e($display_name) ?></strong></span>
      <a href="../api/auth.php?action=login">Đăng xuất</a>
    </div>
  </div>
</header>
<main class="st-content">
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