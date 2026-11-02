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
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $message = 'Không thể cập nhật thông tin. Vui lòng kiểm tra cấu trúc CSDL.';
} catch (Exception $e) {
    $message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - TestSuViet</title>
    <link rel="stylesheet" href="/../assets/css/style.css">
</head>

<body>
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
    <div class="et-sidebar-foot"><a href="/logout.php"><span>⇥</span> Đăng xuất</a></div>
  </aside>
  <div class="et-workspace">
    <header class="et-topbar">
      <div class="et-topbar-left">
        <button type="button" class="et-mobile-toggle" onclick="etToggleSidebar()" aria-label="Mở menu">☰</button>
        <div><div class="et-crumb">TEACHER / GIÁO VIÊN</div><div class="et-top-title">Hồ sơ cá nhân</div></div>
      </div>
      <div class="et-user"><span class="et-avatar">GV</span><span class="et-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Giáo viên', ENT_QUOTES, 'UTF-8') ?></span></div>
    </header>
    <main class="et-content">
      <div class="container">
        <div class="topbar">
            <h1>👤 Thông tin cá nhân</h1>
        </div>

        <?php if ($message !== ''): ?>
            <div class="notice <?= (str_contains($message, 'thành công')) ? '' : 'error' ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="profile">
            <div class="card summary">
                <div class="avatar">👤</div>
                <h2><?= htmlspecialchars($user_info['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="username">@<?= htmlspecialchars($user_info['username'], ENT_QUOTES, 'UTF-8') ?></p>
                <span class="role"><?= htmlspecialchars($user_info['role'] ?? 'STUDENT', ENT_QUOTES, 'UTF-8') ?></span>
                <div class="extra-info">
                    <p><strong> Email:</strong> <?= htmlspecialchars($user_info['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong> Lớp:</strong> <?= htmlspecialchars($user_info['class_name'] ?? 'Chưa cập nhật', ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong> Ngày sinh:</strong> <?= !empty($user_info['dob']) ? date('d/m/Y', strtotime($user_info['dob'])) : 'Chưa cập nhật' ?></p>
                </div>
            </div>

            <div class="card">
                <h2>Cập nhật hồ sơ</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label for="username">Tên tài khoản</label>
                        <input id="username" type="text" value="<?= htmlspecialchars($user_info['username'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Họ và tên</label>
                        <input id="full_name" type="text" name="full_name" value="<?= htmlspecialchars($user_info['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="<?= htmlspecialchars($user_info['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="class_name">Lớp học</label>
                            <input id="class_name" type="text" name="class_name" placeholder="Ví dụ: 9A1" value="<?= htmlspecialchars($user_info['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-group">
                            <label for="dob">Ngày sinh</label>
                            <input id="dob" type="date" name="dob" value="<?= htmlspecialchars($user_info['dob'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <h3 class="section-title">Đổi mật khẩu (không bắt buộc)</h3>
                    <div class="form-group">
                        <label for="current_password">Mật khẩu hiện tại</label>
                        <input id="current_password" type="password" name="current_password" autocomplete="current-password">
                    </div>
                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới</label>
                        <input id="new_password" type="password" name="new_password" minlength="8" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Xác nhận mật khẩu mới</label>
                        <input id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password">
                    </div>
                    <button class="btn btn-warning" type="submit">Lưu cập nhật</button>
                </form>
            </div>
        </div>
      </div>
    </main>
  </div>
</div>
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