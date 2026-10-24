<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Không lưu trang cá nhân vào bộ nhớ đệm trình duyệt
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$message = '';
$user_info = [];

// Lấy thông tin người dùng hiện tại (Đã thêm class_name và dob)
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
        // Tài khoản không còn tồn tại trong CSDL
        $_SESSION = [];
        session_destroy();

        header('Location: /login.php?error=account');
        exit;
    }

    return $user;
}

try {
    $user_info = getUserInfo($conn, $user_id);

    // Xử lý cập nhật hồ sơ
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['action'] ?? '') === 'update_profile'
    ) {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $className = trim($_POST['class_name'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $password = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($fullName === '' || $email === '') {
            throw new Exception('Họ tên và email không được để trống.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Địa chỉ email không hợp lệ.');
        }

        // Kiểm tra email có thuộc tài khoản khác không
        $stmt = $conn->prepare(
            'SELECT user_id FROM users
             WHERE email = ? AND user_id <> ?
             LIMIT 1'
        );
        $stmt->execute([$email, $user_id]);

        if ($stmt->fetch()) {
            throw new Exception('Email này đã được tài khoản khác sử dụng.');
        }

        // Chỉ cập nhật mật khẩu khi người dùng nhập mật khẩu mới
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

        // Chuẩn hóa dữ liệu ngày sinh (nếu rỗng thì để NULL)
        $dobValue = $dob !== '' ? $dob : null;

        // Cập nhật thông tin vào CSDL
        if ($changePassword) {
            $stmt = $conn->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, class_name = ?, dob = ?, password_hash = ?
                 WHERE user_id = ?'
            );

            $stmt->execute([
                $fullName,
                $email,
                $className,
                $dobValue,
                password_hash($newPassword, PASSWORD_DEFAULT),
                $user_id
            ]);
        } else {
            $stmt = $conn->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, class_name = ?, dob = ?
                 WHERE user_id = ?'
            );

            $stmt->execute([$fullName, $email, $className, $dobValue, $user_id]);
        }

        // Đồng bộ thông tin với session đăng nhập
        $_SESSION['full_name'] = $fullName;
        $_SESSION['username'] = $user_info['username'];
        $_SESSION['email'] = $email;

        // Lấy lại thông tin mới nhất từ CSDL
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

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        body {
            margin: 0;
            padding: 30px 16px;
            background: #f4f6f9;
            color: #333;
        }

        .container {
            max-width: 850px;
            margin: auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        h1 {
            font-size: 25px;
            color: #2c3e50;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            border: 0;
            border-radius: 6px;
            background: #3498db;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
        }

        .btn:hover {
            background: #2980b9;
        }

        .profile {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 22px;
        }

        .card {
            padding: 24px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px #0000000d;
        }

        .summary {
            text-align: center;
        }

        .avatar {
            width: 110px;
            height: 110px;
            display: grid;
            place-items: center;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #e1f5fe;
            color: #0288d1;
            font-size: 42px;
        }

        .username {
            color: #666;
            margin-top: 8px;
        }

        .role {
            display: inline-block;
            margin-top: 12px;
            padding: 5px 12px;
            border-radius: 20px;
            background: #e1f5fe;
            color: #0277bd;
            font-size: 13px;
            font-weight: 600;
        }

        .extra-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: left;
            font-size: 14px;
            color: #555;
        }

        .extra-info p {
            margin: 6px 0;
        }

        h2 {
            margin-top: 0;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccd2d8;
            border-radius: 6px;
            font-size: 14px;
        }

        input:focus {
            border-color: #3498db;
            outline: none;
        }

        .notice {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 6px;
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .section-title {
            margin: 26px 0 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
        }

        @media (max-width: 650px) {
            .profile {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="topbar">
        <h1>👤 Thông tin cá nhân</h1>
        <a class="btn" href="/index.php">🏠 Trang chủ</a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="notice <?= (str_contains($message, 'thành công')) ? '' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="profile">
        <!-- KHỐI BÊN TRÁI: TỔNG QUAN -->
        <div class="card summary">
            <div class="avatar">👤</div>

            <h2>
                <?= htmlspecialchars($user_info['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </h2>

            <p class="username">
                @<?= htmlspecialchars($user_info['username'], ENT_QUOTES, 'UTF-8') ?>
            </p>

            <span class="role">
                <?= htmlspecialchars($user_info['role'] ?? 'STUDENT', ENT_QUOTES, 'UTF-8') ?>
            </span>

            <div class="extra-info">
                <p><strong>📧 Email:</strong> <?= htmlspecialchars($user_info['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>🏫 Lớp:</strong> <?= htmlspecialchars($user_info['class_name'] ?? 'Chưa cập nhật', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>🎂 Ngày sinh:</strong> <?= !empty($user_info['dob']) ? date('d/m/Y', strtotime($user_info['dob'])) : 'Chưa cập nhật' ?></p>
            </div>
        </div>

        <!-- KHỐI BÊN PHẢI: FORM CẬP NHẬT -->
        <div class="card">
            <h2>Cập nhật hồ sơ</h2>

            <form method="POST">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label for="username">Tên tài khoản</label>
                    <input
                        id="username"
                        type="text"
                        value="<?= htmlspecialchars($user_info['username'], ENT_QUOTES, 'UTF-8') ?>"
                        disabled
                    >
                </div>

                <div class="form-group">
                    <label for="full_name">Họ và tên</label>
                    <input
                        id="full_name"
                        type="text"
                        name="full_name"
                        value="<?= htmlspecialchars($user_info['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($user_info['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <!-- BỔ SUNG: CỘT LỚP HỌC VÀ NGÀY SINH -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="class_name">Lớp học</label>
                        <input
                            id="class_name"
                            type="text"
                            name="class_name"
                            placeholder="Ví dụ: 9A1"
                            value="<?= htmlspecialchars($user_info['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="dob">Ngày sinh</label>
                        <input
                            id="dob"
                            type="date"
                            name="dob"
                            value="<?= htmlspecialchars($user_info['dob'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>
                </div>

                <h3 class="section-title">
                    🔑 Đổi mật khẩu (không bắt buộc)
                </h3>

                <div class="form-group">
                    <label for="current_password">Mật khẩu hiện tại</label>
                    <input
                        id="current_password"
                        type="password"
                        name="current_password"
                        autocomplete="current-password"
                    >
                </div>

                <div class="form-group">
                    <label for="new_password">Mật khẩu mới</label>
                    <input
                        id="new_password"
                        type="password"
                        name="new_password"
                        minlength="8"
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu mới</label>
                    <input
                        id="confirm_password"
                        type="password"
                        name="confirm_password"
                        minlength="8"
                        autocomplete="new-password"
                    >
                </div>

                <button class="btn" type="submit">
                    💾 Lưu cập nhật
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>