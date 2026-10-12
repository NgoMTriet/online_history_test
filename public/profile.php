<?php
// Khởi tạo Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------------------------------------------------------
// 1. KẾT NỐI DATABASE VÀ KIỂM TRA ĐĂNG NHẬP
// --------------------------------------------------------------------------
require_once __DIR__ . '/../config/database.php';

// Kiểm tra người dùng đã đăng nhập chưa (Nếu chưa thì chuyển hướng về login.php)
if (!isset($_SESSION['user_id'])) {
    // Để tiện test khi chưa gắn session, bạn có thể gán mặc định user_id = 1
    $_SESSION['user_id'] = 1;
}

$user_id = $_SESSION['user_id'];
$message = "";

// --------------------------------------------------------------------------
// 2. XỬ LÝ LƯU CẬP NHẬT THÔNG TIN (KHI SUBMIT FORM)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Xử lý Upload ảnh đại diện Avatar (nếu có)
    $avatar_filename = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['avatar']['tmp_name'];
        $fileName = $_FILES['avatar']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = 'avatar_user_' . $user_id . '_' . time() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $avatar_filename = 'uploads/avatars/' . $newFileName;
            }
        }
    }

    // Tiến hành cập nhật CSDL
    if (!empty($fullname) && !empty($email)) {
        try {
            // Đọc mật khẩu hiện tại từ CSDL để đối soát
            $stmt_check = $conn->prepare("SELECT password, avatar FROM users WHERE id = :id");
            $stmt_check->execute(['id' => $user_id]);
            $user_db = $stmt_check->fetch();

            $update_password_query = "";
            $params = [
                'fullname' => $fullname,
                'email' => $email,
                'phone' => $phone,
                'id' => $user_id
            ];

            // Nếu người dùng có upload avatar mới
            if ($avatar_filename) {
                $avatar_query = ", avatar = :avatar";
                $params['avatar'] = $avatar_filename;
            } else {
                $avatar_query = "";
            }

            // Nếu người dùng nhập đổi mật khẩu
            if (!empty($new_password)) {
                if (password_verify($current_password, $user_db['password']) || $current_password === $user_db['password']) {
                    if ($new_password === $confirm_password) {
                        $update_password_query = ", password = :new_pass";
                        $params['new_pass'] = password_hash($new_password, PASSWORD_BCRYPT);
                    } else {
                        throw new Exception("Mật khẩu mới và Nhập lại mật khẩu không khớp nhau!");
                    }
                } else {
                    throw new Exception("Mật khẩu hiện tại không chính xác!");
                }
            }

            // Thực hiện câu lệnh UPDATE
            $sql = "UPDATE users SET fullname = :fullname, email = :email, phone = :phone {$avatar_query} {$update_password_query} WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Cập nhật lại thông tin Session
            $_SESSION['fullname'] = $fullname;
            if ($avatar_filename) {
                $_SESSION['avatar'] = $avatar_filename;
            }

            $message = "<div class='alert alert-success'>✅ Cập nhật thông tin cá nhân thành công!</div>";

        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>❌ " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>❌ Họ tên và Email không được để trống!</div>";
    }
}

// --------------------------------------------------------------------------
// 3. LẤY THÔNG TIN NGƯỜI DÙNG HIỆN TẠI TỪ CSDL
// --------------------------------------------------------------------------
try {
    $stmt_user = $conn->prepare("SELECT fullname, email, phone, role, avatar, created_at FROM users WHERE id = :id");
    $stmt_user->execute(['id' => $user_id]);
    $user_info = $stmt_user->fetch();
} catch (PDOException $e) {
    // Dữ liệu Mock Data khi chưa có bảng CSDL
    $user_info = [
        'fullname' => $_SESSION['fullname'] ?? 'Nguyễn Văn Học Viên',
        'email' => 'hocvien@gmail.com',
        'phone' => '0987654321',
        'role' => $_SESSION['role'] ?? 'student',
        'avatar' => 'image/anh1.jpg',
        'created_at' => '2026-01-15 10:00:00'
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập Nhật Thông Tin Cá Nhân</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; color: #333; padding-bottom: 50px; }
        
        header { background: #2c3e50; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .page-title { font-size: 1.3rem; font-weight: bold; }
        .btn-home { background-color: #3498db; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: bold; transition: 0.2s; }
        .btn-home:hover { background-color: #2980b9; }

        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        
        .profile-grid { display: grid; grid-template-columns: 280px 1fr; gap: 25px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        
        /* Cột trái - Xem Avatar & Thông tin chung */
        .avatar-box { text-align: center; }
        .avatar-img { width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid #3498db; margin-bottom: 15px; }
        .role-badge { display: inline-block; background: #e1f5fe; color: #0288d1; padding: 4px 12px; border-radius: 12px; font-weight: bold; font-size: 0.85rem; margin-top: 5px; }
        
        /* Form cài đặt */
        h2 { color: #2c3e50; font-size: 1.3rem; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: bold; margin-bottom: 6px; color: #34495e; font-size: 0.95rem; }
        input[type="text"], input[type="email"], input[type="password"], input[type="file"] { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 0.95rem; }
        
        .section-divider { margin: 25px 0 15px 0; font-size: 1.1rem; color: #e67e22; border-bottom: 1px dashed #ddd; padding-bottom: 8px; }
        
        .btn-submit { background: #27ae60; color: white; border: none; padding: 12px 30px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #219150; }

        .alert { padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <header>
        <div class="page-title">👤 Thông Tin Cá Nhân</div>
        <a href="index.php" class="btn-home">🏠 Trang Chủ</a>
    </header>

    <div class="container">
        
        <?= $message ?>

        <div class="profile-grid">
            
            <!-- CỘT 1: AVATAR & THÔNG TIN CHUNG -->
            <div class="card avatar-box">
                <img src="<?= htmlspecialchars($user_info['avatar'] ?? 'image/anh1.jpg') ?>" alt="Avatar" class="avatar-img" id="preview-avatar">
                <h3 style="color: #2c3e50;"><?= htmlspecialchars($user_info['fullname']) ?></h3>
                <span class="role-badge"><?= strtoupper($user_info['role'] ?? 'STUDENT') ?></span>
                <p style="font-size: 0.85rem; color: #7f8c8d; margin-top: 15px;">
                    📅 Ngày tham gia:<br>
                    <strong><?= date('d/m/Y', strtotime($user_info['created_at'] ?? 'now')) ?></strong>
                </p>
            </div>

            <!-- CỘT 2: FORM CẬP NHẬT THÔNG TIN -->
            <div class="card">
                <h2>✍️ Cập nhật Hồ sơ</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label>Đổi Ảnh đại diện (Avatar):</label>
                        <input type="file" name="avatar" accept="image/*" onchange="previewImage(event)">
                    </div>

                    <div class="form-group">
                        <label>Họ và Tên:</label>
                        <input type="text" name="fullname" value="<?= htmlspecialchars($user_info['fullname']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ Email:</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user_info['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại:</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>" placeholder="Nhập số điện thoại...">
                    </div>

                    <!-- PHẦN ĐỔI MẬT KHẨU (KHÔNG BẮT BUỘC) -->
                    <div class="section-divider">🔑 Đổi mật khẩu (Để trống nếu không đổi)</div>

                    <div class="form-group">
                        <label>Mật khẩu hiện tại:</label>
                        <input type="password" name="current_password" placeholder="Nhập mật khẩu hiện tại...">
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu mới:</label>
                        <input type="password" name="new_password" placeholder="Mật khẩu mới (tối thiểu 6 ký tự)...">
                    </div>

                    <div class="form-group">
                        <label>Xác nhận Mật khẩu mới:</label>
                        <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu mới...">
                    </div>

                    <br>
                    <button type="submit" class="btn-submit">💾 Lưu Cập Nhật</button>
                </form>
            </div>

        </div>

    </div>

    <script>
        // Preview ảnh đại diện ngay khi chọn file
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('preview-avatar');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

</body>
</html>