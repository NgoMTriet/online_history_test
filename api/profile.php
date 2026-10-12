<?php
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Xử lý nộp form cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (!empty($full_name) && !empty($email)) {
        try {
            // Lấy mật khẩu hiện tại trong DB
            $stmt_check = $conn->prepare("SELECT password_hash FROM users WHERE user_id = :id");
            $stmt_check->execute(['id' => $user_id]);
            $user_db = $stmt_check->fetch();

            $pass_update_query = "";
            $params = ['full_name' => $full_name, 'email' => $email, 'id' => $user_id];

            // Nếu muốn đổi mật khẩu
            if (!empty($new_pass)) {
                if (password_verify($current_pass, $user_db['password_hash'])) {
                    if ($new_pass === $confirm_pass) {
                        $pass_update_query = ", password_hash = :new_pass";
                        $params['new_pass'] = password_hash($new_pass, PASSWORD_DEFAULT);
                    } else {
                        throw new Exception("Mật khẩu xác nhận không khớp!");
                    }
                } else {
                    throw new Exception("Mật khẩu hiện tại không chính xác!");
                }
            }

            $sql = "UPDATE users SET full_name = :full_name, email = :email {$pass_update_query} WHERE user_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $_SESSION['full_name'] = $full_name;
            $message = "<div class='alert alert-success'>✅ Cập nhật thông tin thành công!</div>";

        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>❌ " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Lấy thông tin user hiện tại
$stmt_user = $conn->prepare("SELECT username, full_name, email, role, status FROM users WHERE user_id = :id");
$stmt_user->execute(['id' => $user_id]);
$user_info = $stmt_user->fetch();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cập Nhật Hồ Sơ</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; color: #333; padding: 30px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #34495e; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h2>👤 Thông Tin Cá Nhân</h2>
        <?= $message ?>

        <form method="POST">
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group">
                <label>Tên đăng nhập:</label>
                <input type="text" value="<?= htmlspecialchars($user_info['username']) ?>" disabled>
            </div>

            <div class="form-group">
                <label>Họ và tên:</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user_info['full_name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user_info['email']) ?>" required>
            </div>

            <hr style="margin: 20px 0;">
            <h3>🔑 Đổi mật khẩu (Để trống nếu giữ nguyên)</h3>
            <br>

            <div class="form-group">
                <label>Mật khẩu hiện tại:</label>
                <input type="password" name="current_password">
            </div>

            <div class="form-group">
                <label>Mật khẩu mới:</label>
                <input type="password" name="new_password">
            </div>

            <div class="form-group">
                <label>Nhập lại mật khẩu mới:</label>
                <input type="password" name="confirm_password">
            </div>

            <button type="submit" class="btn">💾 Cập nhật thông tin</button>
        </form>
    </div>
</body>
</html>