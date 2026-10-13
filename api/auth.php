<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function redirectTo(string $url): never
{
    header("Location: " . $url);
    exit;
}

function loginUrl(string $query = ''): string
{
    return '/login.php' . ($query !== '' ? '?' . $query : '');
}
try {
    switch ($action) {

        // =========================
        // ĐĂNG KÝ
        // =========================
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                redirectTo('/register.php');
            }

            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? 'STUDENT';

            if (
                $username === '' ||
                $email === '' ||
                $fullName === '' ||
                $password === ''
            ) {
                redirectTo('/register.php?error=missing');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                redirectTo('/register.php?error=email');
            }

            if (strlen($password) < 8) {
                redirectTo('/register.php?error=password');
            }

            if ($password !== $confirm) {
                redirectTo('/register.php?error=confirm');
            }

            if (!in_array($role, ['STUDENT', 'TEACHER'], true)) {
                $role = 'STUDENT';
            }

            $stmt = $conn->prepare(
                'SELECT user_id FROM users
                 WHERE username = ? OR email = ? LIMIT 1'
            );
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                redirectTo('/register.php?error=exists');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                'INSERT INTO users
                 (username, password_hash, full_name, email, role, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            // Giáo viên có thể cần Admin duyệt.
            $status = $role === 'TEACHER' ? 'PENDING' : 'ACTIVE';

            $stmt->execute([
                $username,
                $hash,
                $fullName,
                $email,
                $role,
                $status
            ]);

            redirectTo(loginUrl('registered=1'));
            break;

        // =========================
        // ĐĂNG NHẬP
        // =========================
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                redirectTo('/login.php');
            }

            $account = trim($_POST['account'] ?? '');
            $password = $_POST['password'] ?? '';

            $stmt = $conn->prepare(
                'SELECT user_id, username, password_hash,
                        full_name, email, role, status
                 FROM users
                 WHERE username = ? OR email = ?
                 LIMIT 1'
            );
            $stmt->execute([$account, $account]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                redirectTo(loginUrl('error=invalid'));
            }

            if ($user['status'] === 'LOCKED') {
                redirectTo(loginUrl('error=locked'));
            }

            if ($user['status'] === 'PENDING') {
                redirectTo(loginUrl('error=pending'));
            }

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

        // Tất cả vai trò đều về trang chủ sau khi đăng nhập
redirectTo('/index.php');

        // =========================
        // ĐĂNG XUẤT
        // =========================
        case 'logout':
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

        session_destroy();

        redirectTo('/login.php?logout=success');

        // =========================
        // GỬI LIÊN KẾT ĐẶT LẠI MẬT KHẨU
        // =========================
        case 'forgot':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                redirectTo('/forgot-password.php');
            }

            $email = trim($_POST['email'] ?? '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                redirectTo('/forgot-password.php?error=email');
            }

            $stmt = $conn->prepare(
                'SELECT user_id FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Không tiết lộ email có đăng ký hay không.
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);

                $conn->prepare(
                    'UPDATE password_resets
                     SET used_at = NOW()
                     WHERE user_id = ? AND used_at IS NULL'
                )->execute([$user['user_id']]);

                $stmt = $conn->prepare(
                    'INSERT INTO password_resets
                     (user_id, token_hash, expires_at, used_at)
                     VALUES (?, ?, ?, NULL)'
                );
                $stmt->execute([
                    $user['user_id'],
                    $tokenHash,
                    $expiresAt
                ]);

                $resetUrl = 'http://' . $_SERVER['HTTP_HOST']
                    . '/reset-password.php?token=' . urlencode($token);

                $subject = 'Dat lai mat khau TestSuViet';
                $body = "Xin chào,\n\n"
                    . "Mở liên kết sau để đặt lại mật khẩu "
                    . "(liên kết hết hạn sau 1 giờ):\n"
                    . $resetUrl
                    . "\n\nNếu bạn không yêu cầu, hãy bỏ qua email này.";

                $headers = "Content-Type: text/plain; charset=UTF-8\r\n";

                if (!mail($email, $subject, $body, $headers)) {
                    redirectTo('/forgot-password.php?error=mail');
                }
            }

            redirectTo('/forgot-password.php?sent=1');
            break;

        // =========================
        // ĐẶT LẠI MẬT KHẨU BẰNG TOKEN
        // =========================
        case 'reset':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                redirectTo('/reset-password.php');
            }

            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (
                strlen($password) < 8 ||
                $password !== $confirm ||
                !preg_match('/^[a-f0-9]{64}$/', $token)
            ) {
                redirectTo(
                    '/reset-password.php?error=invalid&token='
                    . urlencode($token)
                );
            }

            $tokenHash = hash('sha256', $token);

            $stmt = $conn->prepare(
                'SELECT id, user_id FROM password_resets
                 WHERE token_hash = ?
                   AND used_at IS NULL
                   AND expires_at > NOW()
                 LIMIT 1'
            );
            $stmt->execute([$tokenHash]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                redirectTo('/forgot-password.php?error=token');
            }

            $newHash = password_hash($password, PASSWORD_DEFAULT);

            $conn->beginTransaction();

            $stmt = $conn->prepare(
                'UPDATE users SET password_hash = ? WHERE user_id = ?'
            );
            $stmt->execute([$newHash, $reset['user_id']]);

            $stmt = $conn->prepare(
                'UPDATE password_resets SET used_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$reset['id']]);

            $conn->commit();

            redirectTo(loginUrl('reset=success'));
            break;

        default:
            redirectTo('/login.php?error=action');
    }

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log($e->getMessage());
    http_response_code(500);
    echo 'Đã xảy ra lỗi hệ thống. Vui lòng kiểm tra cấu hình và nhật ký PHP.';
}
