<?php
// Kết nối database và khởi tạo session nếu cần
require_once __DIR__ . '/../config/database.php';

// Lấy mã lỗi trả về từ api/auth.php
$error = $_GET['error'] ?? '';
$message = '';

switch ($error) {
    case 'missing':
        $message = 'Vui lòng nhập đầy đủ tất cả thông tin.';
        break;

    case 'email':
        $message = 'Địa chỉ email không đúng định dạng. Vui lòng kiểm tra lại.';
        break;

    case 'password':
        $message = 'Mật khẩu phải có ít nhất 8 ký tự.';
        break;

    case 'confirm':
        $message = 'Mật khẩu xác nhận không khớp với mật khẩu đã nhập.';
        break;

    case 'exists':
        $message = 'Tên đăng nhập hoặc email đã tồn tại. Vui lòng sử dụng thông tin khác.';
        break;

    default:
        $message = '';
        break;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - TestSuViet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/css/auth.css?v=1">
    
</head>

<body class="auth-page">

    <svg class="backdrop" viewBox="0 0 1000 1000" aria-hidden="true">
        <g fill="none" stroke="currentColor">
            <circle cx="500" cy="500" r="490" stroke-width="3"/>
            <circle cx="500" cy="500" r="440" stroke-width="2" stroke-dasharray="4 12"/>
            <circle cx="500" cy="500" r="390" stroke-width="3"/>
            <circle cx="500" cy="500" r="330" stroke-width="2" stroke-dasharray="4 12"/>
            <circle cx="500" cy="500" r="270" stroke-width="3"/>
            <circle cx="500" cy="500" r="200" stroke-width="2" stroke-dasharray="4 12"/>
            <circle cx="500" cy="500" r="130" stroke-width="3"/>
        </g>
    </svg>

    <div class="stack">

        <a class="brand" href="index.php">
            <svg width="48" height="48" viewBox="0 0 32 32" aria-hidden="true">
                <circle cx="16" cy="16" r="14.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="16" cy="16" r="11" fill="none" stroke="currentColor" stroke-width="1" stroke-dasharray="1.5 2.5"/>
                <polygon fill="currentColor" points="16,9 17.65,13.73 22.66,13.84 18.66,16.87 20.11,21.66 16,18.8 11.89,21.66 13.34,16.87 9.34,13.84 14.35,13.73"/>
            </svg>
            <span class="brand-name">TestSuViet</span>
        </a>

        <main class="card">
            <h1>Tạo tài khoản</h1>
            <p class="lead">Điền thông tin bên dưới để bắt đầu.</p>

            <?php if ($message !== ''): ?>
                <div class="alert alert-error" role="alert">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="../api/auth.php?action=register">
                <div class="field">
                    <label for="full_name">Họ và tên</label>
                    <input type="text" id="full_name" name="full_name" autocomplete="name" autofocus required>
                </div>

                <div class="field">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" autocomplete="username" required>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" autocomplete="email" required>
                </div>

                <div class="field">
                    <label for="password">Mật khẩu</label>
                    <div class="password-box">
                        <input type="password" id="password" name="password"
                               autocomplete="new-password" minlength="8" required>
                        <button type="button" class="toggle-password" id="togglePassword"
                                aria-controls="password confirm_password" aria-pressed="false">Hiện</button>
                    </div>
                    <p class="hint">Ít nhất 8 ký tự.</p>
                </div>

                <div class="field">
                    <label for="confirm_password">Nhập lại mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           autocomplete="new-password" required>
                </div>

                <fieldset class="role-group">
                    <legend>Bạn đăng ký với vai trò</legend>
                    <div class="role-options">
                        <div class="role-option">
                            <input type="radio" id="role_student" name="role" value="STUDENT" checked>
                            <label for="role_student">
                                <strong>Học viên</strong>
                                <span>Dùng được ngay</span>
                            </label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="role_teacher" name="role" value="TEACHER">
                            <label for="role_teacher">
                                <strong>Giáo viên</strong>
                                <span>Cần Admin duyệt</span>
                            </label>
                        </div>
                    </div>
                </fieldset>

                <button type="submit" class="submit">Đăng ký</button>
            </form>

            <p class="register">
                Đã có tài khoản? <a href="login.php">Đăng nhập</a>
            </p>
        </main>

        <p class="below"><a href="index.php">← Về trang chủ</a></p>

    </div>

<script>
    (function () {
        var password = document.getElementById('password');
        var confirm = document.getElementById('confirm_password');
        var button = document.getElementById('togglePassword');

        // Một nút Hiện/Ẩn điều khiển cả hai ô mật khẩu
        button.addEventListener('click', function () {
            var show = password.type === 'password';
            var type = show ? 'text' : 'password';
            password.type = type;
            confirm.type = type;
            button.textContent = show ? 'Ẩn' : 'Hiện';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    })();
</script>
</body>
</html>