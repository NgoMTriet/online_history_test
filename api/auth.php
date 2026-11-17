<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

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
        redirectTo('/forgot_password.php');
    }

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectTo('/forgot_password.php?error=mail');
    }

    $stmt = $conn->prepare(
        'SELECT user_id FROM users WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Không tiết lộ email có đăng ký tài khoản hay không.
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        // Vô hiệu hóa các liên kết cũ chưa sử dụng.
        $stmt = $conn->prepare(
            'UPDATE password_resets
             SET used_at = NOW()
             WHERE user_id = ? AND used_at IS NULL'
        );
        $stmt->execute([$user['user_id']]);

        // Lưu bản băm của token, không lưu token gốc.
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
            . '/reset-password.php?token='
            . urlencode($token);
            
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;

            $mailConfig = require __DIR__ . '/../config/mail.php';

            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];

            $mail->SMTPSecure =
                \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(
                $mailConfig['username'],
                'TestSuViet.com'
            );

            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Dat lai mat khau TestSuViet.com';

            $safeUrl = htmlspecialchars(
                $resetUrl,
                ENT_QUOTES,
                'UTF-8'
            );

            $mail->Body = '
                <h2>Yêu cầu đặt lại mật khẩu</h2>
                <p>Nhấn vào nút bên dưới để tạo mật khẩu mới.</p>
                <p>
                    <a href="' . $safeUrl . '"
                       style="display:inline-block;padding:12px 20px;
                       background:#2563eb;color:white;
                       text-decoration:none;border-radius:6px">
                       Đặt lại mật khẩu
                    </a>
                </p>
                <p>Liên kết có hiệu lực trong 1 giờ và chỉ dùng một lần.</p>
                <p>Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>
            ';

            $mail->AltBody =
                "Dat lai mat khau tai: " . $resetUrl
                . "\nLien ket het han sau 1 gio.";

            $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
    error_log('[MAIL ERROR] ' . $e->getMessage());
    error_log('[MAIL DEBUG] ' . $mail->ErrorInfo);

    redirectTo('/forgot_password.php?error=mail');
            // Không giữ token chưa gửi được email.
            $stmt = $conn->prepare(
                'UPDATE password_resets
                 SET used_at = NOW()
                 WHERE user_id = ? AND token_hash = ?'
            );
            $stmt->execute([$user['user_id'], $tokenHash]);

            redirectTo(
                '/forgot_password.php?error=mail'
            );
        }
    }

    redirectTo(
        '/forgot_password.php?sent=1'
    );
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
    'SELECT id, user_id, used_at, expires_at
     FROM password_resets
     WHERE token_hash = ?
     LIMIT 1'
);

$stmt->execute([$tokenHash]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    error_log('[RESET CHECK] Không tìm thấy token_hash trong password_resets.');
    redirectTo('/forgot_password.php?error=token');
}

if ($reset['used_at'] !== null) {
    error_log('[RESET CHECK] Token đã được sử dụng.');
    redirectTo('/forgot_password.php?error=token');
}

if (strtotime($reset['expires_at']) <= time()) {
    error_log('[RESET CHECK] Token đã hết hạn.');
    redirectTo('/forgot_password.php?error=token');
}


            $newHash = password_hash($password, PASSWORD_DEFAULT);

        if ($newHash === false) {
            redirectTo('/reset-password.php?error=hash');
        }

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare(
                'UPDATE users
                SET password_hash = ?
                WHERE user_id = ?'
            );

    $stmt->execute([$newHash, $reset['user_id']]);
    
    error_log(
    '[RESET CHECK] user_id=' . $reset['user_id']
    . ', hash_length=' . strlen($newHash)
    . ', password_verify='
    . (password_verify($password, $newHash) ? 'OK' : 'FAIL')
    );

    // Xác nhận tài khoản có tồn tại.
    if ($stmt->rowCount() === 0) {
        $check = $conn->prepare(
            'SELECT user_id
             FROM users
             WHERE user_id = ?
             LIMIT 1'
        );
        $check->execute([$reset['user_id']]);

        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException(
                'Không tìm thấy tài khoản cần cập nhật.'
            );
        }
    }

    $stmt = $conn->prepare(
        'UPDATE password_resets
         SET used_at = NOW()
         WHERE id = ?'
    );
    $stmt->execute([$reset['id']]);

    $conn->commit();

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log('[RESET PASSWORD ERROR] ' . $e->getMessage());
    redirectTo('/reset-password.php?error=update');
}

redirectTo(loginUrl('reset=success'));
break;
            break;

        default:
            redirectTo('/login.php?error=action');
    }

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log('[PASSWORD_RESET] ' . $e);

    http_response_code(500);
    echo '<pre>';
    echo htmlspecialchars(
        (string) $e,
        ENT_QUOTES,
        'UTF-8'
    );
    echo '</pre>';
}

