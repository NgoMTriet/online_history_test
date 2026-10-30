<?php
session_start();
require_once __DIR__ . '/../config/database.php';

/* ------------------------------------------------------------------
 * 1. KIỂM TRA ĐĂNG NHẬP & PHÂN QUYỀN
 *    Đổi tên các key session cho khớp với trang đăng nhập của bạn.
 * ------------------------------------------------------------------ */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'ADMIN') {
    header('Location: /login.php');
    exit;
}
$current_admin_id   = (int)$_SESSION['user_id'];
$current_admin_name = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Admin');

/* ------------------------------------------------------------------
 * 2. HÀM TIỆN ÍCH
 * ------------------------------------------------------------------ */
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Đếm an toàn: lỗi truy vấn (bảng chưa có, sai tên cột...) thì trả về 0
function safe_count(PDO $conn, string $sql, array $params = []): int
{
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/* ------------------------------------------------------------------
 * 3. CSRF TOKEN
 * ------------------------------------------------------------------ */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ------------------------------------------------------------------
 * 4. XỬ LÝ KHÓA / MỞ KHÓA (POST -> Redirect -> GET)
 * ------------------------------------------------------------------ */
$allowed_roles    = ['STUDENT', 'TEACHER', 'ADMIN'];
$allowed_statuses = ['ACTIVE', 'LOCKED', 'PENDING'];   // dùng cho bộ lọc
$toggle_statuses  = ['ACTIVE', 'LOCKED'];              // trạng thái admin được phép đặt

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    $redirect_to = 'admin_dashboard.php' . (!empty($_POST['return_query']) ? '?' . $_POST['return_query'] : '');

    // Chống CSRF
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
        header('Location: ' . $redirect_to);
        exit;
    }

    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $new_status     = $_POST['new_status'] ?? '';

    if (!in_array($new_status, $toggle_statuses, true)) {
        set_flash('error', 'Trạng thái không hợp lệ.');
    } elseif ($target_user_id === $current_admin_id) {
        set_flash('error', 'Bạn không thể tự khóa tài khoản của chính mình.');
    } else {
        $stmt = $conn->prepare("SELECT username, role, status FROM users WHERE user_id = :id");
        $stmt->execute(['id' => $target_user_id]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$target) {
            set_flash('error', 'Không tìm thấy người dùng.');
        } elseif ($target['role'] === 'ADMIN') {
            set_flash('error', 'Không thể thay đổi trạng thái của tài khoản quản trị viên.');
        } else {
            $stmt = $conn->prepare("UPDATE users SET status = :status WHERE user_id = :id");
            $stmt->execute(['status' => $new_status, 'id' => $target_user_id]);

            // Ghi log thao tác (bỏ qua nếu chưa có bảng admin_logs)
            try {
                $log = $conn->prepare(
                    "INSERT INTO admin_logs (admin_id, action, target_user_id, created_at)
                     VALUES (:admin, :action, :target, NOW())"
                );
                $log->execute([
                    'admin'  => $current_admin_id,
                    'action' => $new_status === 'LOCKED' ? 'LOCK_USER' : 'UNLOCK_USER',
                    'target' => $target_user_id,
                ]);
            } catch (PDOException $e) {
                // Không có bảng log thì bỏ qua
            }

            if ($new_status === 'LOCKED') {
                $verb = 'khóa';
            } elseif ($target['status'] === 'PENDING') {
                $verb = 'duyệt';
            } else {
                $verb = 'mở khóa';
            }
            set_flash('success', 'Đã ' . $verb . ' tài khoản ' . $target['username'] . '.');
        }
    }

    header('Location: ' . $redirect_to);
    exit;
}

/* ------------------------------------------------------------------
 * 5. TÌM KIẾM, LỌC, PHÂN TRANG
 * ------------------------------------------------------------------ */
$search        = trim($_GET['q'] ?? '');
$filter_role   = in_array($_GET['role'] ?? '', $allowed_roles, true) ? $_GET['role'] : '';
$filter_status = in_array($_GET['status'] ?? '', $allowed_statuses, true) ? $_GET['status'] : '';
$per_page      = 15;
$page          = max(1, (int)($_GET['page'] ?? 1));

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = "(full_name LIKE :q1 OR username LIKE :q2 OR email LIKE :q3)";
    $params['q1'] = $params['q2'] = $params['q3'] = '%' . $search . '%';
}
if ($filter_role !== '') {
    $where[] = "role = :role";
    $params['role'] = $filter_role;
}
if ($filter_status !== '') {
    $where[] = "status = :status";
    $params['status'] = $filter_status;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total_rows  = safe_count($conn, "SELECT COUNT(*) FROM users $where_sql", $params);
$total_pages = max(1, (int)ceil($total_rows / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$stmt_users = $conn->prepare(
    "SELECT user_id, username, full_name, email, role, status
     FROM users $where_sql
     ORDER BY user_id DESC
     LIMIT " . (int)$per_page . " OFFSET " . (int)$offset
);
$stmt_users->execute($params);
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

/* ------------------------------------------------------------------
 * 6. THỐNG KÊ (mỗi truy vấn độc lập, lỗi một cái không ảnh hưởng cái khác)
 * ------------------------------------------------------------------ */
$total_students = safe_count($conn, "SELECT COUNT(*) FROM users WHERE role = 'STUDENT'");
$total_teachers = safe_count($conn, "SELECT COUNT(*) FROM users WHERE role = 'TEACHER'");
$total_locked   = safe_count($conn, "SELECT COUNT(*) FROM users WHERE status = 'LOCKED'");
$total_pending  = safe_count($conn, "SELECT COUNT(*) FROM users WHERE status = 'PENDING'");
$total_exams    = safe_count($conn, "SELECT COUNT(*) FROM exam");
$total_attempts = safe_count($conn, "SELECT COUNT(*) FROM exam_results");

/* ------------------------------------------------------------------
 * 7. HÀM TẠO LINK PHÂN TRANG (giữ nguyên bộ lọc)
 * ------------------------------------------------------------------ */
function page_url(int $p, string $search, string $role, string $status): string
{
    $query = array_filter(
        ['q' => $search, 'role' => $role, 'status' => $status, 'page' => $p > 1 ? $p : null],
        fn($v) => $v !== '' && $v !== null
    );
    return 'admin_dashboard.php' . ($query ? '?' . http_build_query($query) : '');
}

$return_query = $_SERVER['QUERY_STRING'] ?? '';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$role_labels = ['STUDENT' => 'Học viên', 'TEACHER' => 'Giáo viên', 'ADMIN' => 'Quản trị viên'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản trị hệ thống</title>
    <style>
        :root {
            --bg: #f3f5f8;
            --surface: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
            --primary: #2563eb;
            --success: #15803d;
            --success-bg: #dcfce7;
            --danger: #b91c1c;
            --danger-bg: #fee2e2;
            --warn: #b45309;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: var(--bg); color: var(--text); padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }

        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
        .topbar h1 { font-size: 1.5rem; }
        .topbar-user { display: flex; align-items: center; gap: 14px; color: var(--muted); font-size: 0.95rem; }
        .btn-logout { color: var(--danger); text-decoration: none; font-weight: 600; }
        .btn-logout:hover { text-decoration: underline; }

        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.95rem; }
        .alert-success { background: var(--success-bg); color: var(--success); }
        .alert-error { background: var(--danger-bg); color: var(--danger); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-box { background: var(--surface); padding: 18px; border-radius: 8px; border: 1px solid var(--line); }
        .stat-label { color: var(--muted); font-size: 0.9rem; }
        .stat-number { font-size: 2rem; font-weight: 700; margin-top: 4px; }

        .card { background: var(--surface); padding: 24px; border-radius: 8px; border: 1px solid var(--line); }
        .card h2 { font-size: 1.2rem; margin-bottom: 16px; }

        .filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .filters input, .filters select { padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; background: #fff; }
        .filters input[type="text"] { flex: 1; min-width: 200px; }
        .btn { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: #fff; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--primary); }
        .btn-ghost { background: #e5e7eb; color: var(--text); }
        .btn-lock { background: #dc2626; padding: 6px 12px; }
        .btn-unlock { background: #16a34a; padding: 6px 12px; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 720px; }
        th, td { padding: 11px 12px; text-align: left; border-bottom: 1px solid var(--line); font-size: 0.95rem; }
        th { background: #f9fafb; color: var(--muted); font-weight: 600; }
        td.empty { text-align: center; color: var(--muted); padding: 32px; }

        .badge { padding: 3px 9px; border-radius: 999px; font-size: 0.82rem; font-weight: 600; }
        .badge-active { background: var(--success-bg); color: var(--success); }
        .badge-pending { background: #dbeafe; color: #1d4ed8; }
        .badge-locked { background: var(--danger-bg); color: var(--danger); }
        .muted { color: var(--muted); font-size: 0.85rem; }

        .pagination { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 18px; color: var(--muted); font-size: 0.9rem; }
        .pager { display: flex; gap: 6px; }
        .pager a, .pager span { padding: 6px 11px; border: 1px solid var(--line); border-radius: 6px; text-decoration: none; color: var(--text); background: #fff; }
        .pager .current { background: var(--primary); border-color: var(--primary); color: #fff; }
        .pager .disabled { color: #9ca3af; background: #f9fafb; }

        @media (max-width: 600px) {
            body { padding: 12px; }
            .card { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="topbar">
        <h1>Quản trị hệ thống</h1>
        <div class="topbar-user">
            <span>Xin chào, <strong><?= h($current_admin_name) ?></strong></span>
            <a class="btn-logout" href="../api/auth.php?action=logout">Đăng xuất</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
            <?= h($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-label">Học viên</div>
            <div class="stat-number"><?= number_format($total_students) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Giáo viên</div>
            <div class="stat-number"><?= number_format($total_teachers) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Đề thi</div>
            <div class="stat-number" style="color: var(--success);"><?= number_format($total_exams) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Lượt làm bài</div>
            <div class="stat-number" style="color: var(--warn);"><?= number_format($total_attempts) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Chờ duyệt</div>
            <div class="stat-number" style="color: var(--primary);"><?= number_format($total_pending) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Tài khoản bị khóa</div>
            <div class="stat-number" style="color: var(--danger);"><?= number_format($total_locked) ?></div>
        </div>
    </div>

    <div class="card">
        <h2>Quản lý tài khoản</h2>

        <form class="filters" method="GET" action="admin_dashboard.php">
            <input type="text" name="q" value="<?= h($search) ?>" placeholder="Tìm theo tên, username hoặc email">
            <select name="role">
                <option value="">Tất cả vai trò</option>
                <?php foreach ($role_labels as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= $filter_role === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="ACTIVE" <?= $filter_status === 'ACTIVE' ? 'selected' : '' ?>>Hoạt động</option>
                <option value="PENDING" <?= $filter_status === 'PENDING' ? 'selected' : '' ?>>Chờ duyệt</option>
                <option value="LOCKED" <?= $filter_status === 'LOCKED' ? 'selected' : '' ?>>Đã khóa</option>
            </select>
            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            <?php if ($search !== '' || $filter_role !== '' || $filter_status !== ''): ?>
                <a href="admin_dashboard.php" class="btn btn-ghost">Xóa bộ lọc</a>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ và tên</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users): ?>
                        <tr><td class="empty" colspan="7">Không có tài khoản nào khớp với bộ lọc.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($users as $u): ?>
                        <?php
                            $is_self  = (int)$u['user_id'] === $current_admin_id;
                            $is_admin = $u['role'] === 'ADMIN';
                        ?>
                        <tr>
                            <td><?= (int)$u['user_id'] ?></td>
                            <td><strong><?= h($u['full_name']) ?></strong><?= $is_self ? ' <span class="muted">(bạn)</span>' : '' ?></td>
                            <td><?= h($u['username']) ?></td>
                            <td><?= h($u['email']) ?></td>
                            <td><?= h($role_labels[$u['role']] ?? $u['role']) ?></td>
                            <td>
                                <?php if ($u['status'] === 'ACTIVE'): ?>
                                    <span class="badge badge-active">Hoạt động</span>
                                <?php elseif ($u['status'] === 'PENDING'): ?>
                                    <span class="badge badge-pending">Chờ duyệt</span>
                                <?php else: ?>
                                    <span class="badge badge-locked">Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_self || $is_admin): ?>
                                    <span class="muted">Không khả dụng</span>
                                <?php else: ?>
                                    <form method="POST" action="admin_dashboard.php">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <input type="hidden" name="return_query" value="<?= h($return_query) ?>">
                                        <?php if ($u['status'] === 'PENDING'): ?>
                                            <input type="hidden" name="new_status" value="ACTIVE">
                                            <button type="submit" class="btn btn-unlock"
                                                    onclick="return confirm('Duyệt tài khoản <?= h($u['username']) ?>?')">Duyệt</button>
                                        <?php elseif ($u['status'] === 'ACTIVE'): ?>
                                            <input type="hidden" name="new_status" value="LOCKED">
                                            <button type="submit" class="btn btn-lock"
                                                    onclick="return confirm('Khóa tài khoản <?= h($u['username']) ?>?')">Khóa</button>
                                        <?php else: ?>
                                            <input type="hidden" name="new_status" value="ACTIVE">
                                            <button type="submit" class="btn btn-unlock"
                                                    onclick="return confirm('Mở khóa tài khoản <?= h($u['username']) ?>?')">Mở khóa</button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <div>
                <?php if ($total_rows > 0): ?>
                    Hiển thị <?= $offset + 1 ?>–<?= $offset + count($users) ?> trong tổng số <?= number_format($total_rows) ?> tài khoản
                <?php else: ?>
                    Không có dữ liệu
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pager">
                    <?php if ($page > 1): ?>
                        <a href="<?= h(page_url($page - 1, $search, $filter_role, $filter_status)) ?>">Trước</a>
                    <?php else: ?>
                        <span class="disabled">Trước</span>
                    <?php endif; ?>

                    <?php
                        $start = max(1, $page - 2);
                        $end   = min($total_pages, $page + 2);
                        for ($p = $start; $p <= $end; $p++):
                    ?>
                        <?php if ($p === $page): ?>
                            <span class="current"><?= $p ?></span>
                        <?php else: ?>
                            <a href="<?= h(page_url($p, $search, $filter_role, $filter_status)) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?= h(page_url($page + 1, $search, $filter_role, $filter_status)) ?>">Sau</a>
                    <?php else: ?>
                        <span class="disabled">Sau</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</body>
</html>