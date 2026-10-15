<?php
require_once __DIR__ . '/../config/database.php';

// Xử lý Khóa / Mở khóa tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $target_user_id = (int)$_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    $stmt_update = $conn->prepare("UPDATE users SET status = :status WHERE user_id = :user_id");
    $stmt_update->execute(['status' => $new_status, 'user_id' => $target_user_id]);
}

// Lấy danh sách người dùng từ CSDL
$stmt_users = $conn->query("SELECT user_id, username, full_name, email, role, status FROM users ORDER BY user_id DESC");
$users = $stmt_users->fetchAll();

// Thống kê số lượng thực tế từ CSDL (Dùng try-catch để phòng trường hợp bảng chưa tạo hoặc đặt tên khác)
try {
    $total_students = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'STUDENT'")->fetchColumn();
    
    // Đã đổi 'exam' -> 'exams' cho khớp CSDL
    $total_exams = $conn->query("SELECT COUNT(*) FROM exams")->fetchColumn(); 
    
    // Thử truy vấn bảng exam_results (hoặc fallback exam_attempts)
    $total_attempts = $conn->query("SELECT COUNT(*) FROM exam_results")->fetchColumn();
} catch (PDOException $e) {
    try {
        // Fallback nếu CSDL dùng tên bảng 'exam_attempts' hoặc 'exam_result'
        $total_attempts = $conn->query("SELECT COUNT(*) FROM exam_attempts")->fetchColumn();
    } catch (PDOException $e2) {
        $total_attempts = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; color: #333; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 25px; }
        h2 { color: #2c3e50; margin-bottom: 20px; font-size: 1.3rem; border-bottom: 2px solid #eee; padding-bottom: 8px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-box { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .stat-number { font-size: 2.2rem; font-weight: bold; color: #3498db; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; color: #2c3e50; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-blocked { background: #f8d7da; color: #721c24; }
        .btn-action { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: bold; color: white; }
        .btn-block { background: #e74c3c; }
        .btn-unblock { background: #27ae60; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚙️ Dashboard Admin Quản Trị Hệ Thống</h1>
        <br>

        <div class="stats-grid">
            <div class="stat-box">
                <div>👨‍🎓 Tổng học viên</div>
                <div class="stat-number"><?= $total_students ?></div>
            </div>
            <div class="stat-box">
                <div>📑 Tổng số đề thi</div>
                <div class="stat-number" style="color: #27ae60;"><?= number_format($total_exams ?? 0) ?></div>
            </div>
            <div class="stat-box">
                <div>✍️ Lượt làm bài</div>
                <div class="stat-number" style="color: #e67e22;"><?= $total_attempts ?></div>
            </div>
        </div>

        <div class="card">
            <h2>👥 Quản lý & Duyệt / Khóa tài khoản</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ & Tên</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['user_id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= $u['role'] ?></td>
                            <td>
                                <?php if ($u['status'] === 'ACTIVE'): ?>
                                    <span class="badge badge-active">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge badge-blocked">Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <?php if ($u['status'] === 'ACTIVE'): ?>
                                        <input type="hidden" name="new_status" value="LOCKED">
                                        <button type="submit" class="btn-action btn-block" onclick="return confirm('Khóa tài khoản này?')">🔒 Khóa</button>
                                    <?php else: ?>
                                        <input type="hidden" name="new_status" value="ACTIVE">
                                        <button type="submit" class="btn-action btn-unblock" onclick="return confirm('Mở khóa tài khoản này?')">🔓 Mở khóa</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>