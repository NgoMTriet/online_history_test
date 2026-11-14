<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/* ------------------------------------------------------------------
 * 1. KIỂM TRA ĐĂNG NHẬP & PHÂN QUYỀN
 * ------------------------------------------------------------------ */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'ADMIN') { header('Location: /login.php'); exit; }$current_admin_id   = (int)$_SESSION['user_id'];$current_admin_name = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Admin');

/* ------------------------------------------------------------------
 * 2. HÀM TIỆN ÍCH
 * ------------------------------------------------------------------ */
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function safe_count(PDO $conn, string $sql, array$params = []): int
{
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function set_flash(string $type, string$message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' =>$message];
}

function page_url(int $p, string$search, string $role, string$status): string
{
    $query = array_filter(
        ['q' => $search, 'role' => $role, 'status' =>$status, 'page' => $p > 1 ?$p : null],
        fn($v) => $v !== '' &&$v !== null
    );
    return 'admin_dashboard.php' . ($query ? '?' . http_build_query($query) : '');
}

/* ------------------------------------------------------------------
 * 3. CSRF TOKEN
 * ------------------------------------------------------------------ */
if (empty($_SESSION['csrf_token'])) {$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token =$_SESSION['csrf_token'];

/* ------------------------------------------------------------------
 * 4. XỬ LÝ KHÓA / MỞ KHÓA TÀI KHOẢN (POST -> Redirect -> GET)
 * ------------------------------------------------------------------ */
$allowed_roles    = ['STUDENT', 'TEACHER', 'ADMIN'];
$allowed_statuses = ['ACTIVE', 'LOCKED', 'PENDING'];$toggle_statuses  = ['ACTIVE', 'LOCKED'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {$redirect_to = 'admin_dashboard.php' . (!empty($_POST['return_query']) ? '?' .$_POST['return_query'] : '');

    if (!hash_equals($csrf_token,$_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
        header('Location: ' . $redirect_to);
        exit;
    }

    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $new_status     =$_POST['new_status'] ?? '';

    if (!in_array($new_status,$toggle_statuses, true)) {
        set_flash('error', 'Trạng thái không hợp lệ.');
    } elseif ($target_user_id ===$current_admin_id) {
        set_flash('error', 'Bạn không thể tự khóa tài khoản của chính mình.');
    } else {
        $stmt =$conn->prepare("SELECT username, role, status FROM users WHERE user_id = :id");
        $stmt->execute(['id' =>$target_user_id]);
        $target =$stmt->fetch(PDO::FETCH_ASSOC);

        if (!$target) { set_flash('error', 'Không tìm thấy người dùng.'); } elseif ($target['role'] === 'ADMIN') {
            set_flash('error', 'Không thể thay đổi trạng thái của tài khoản quản trị viên.');
        } else {
            $stmt =$conn->prepare("UPDATE users SET status = :status WHERE user_id = :id");
            $stmt->execute(['status' => $new_status, 'id' =>$target_user_id]);

            try {
                $log =$conn->prepare(
                    "INSERT INTO admin_logs (admin_id, action, target_user_id, created_at)
                     VALUES (:admin, :action, :target, NOW())"
                );
                $log->execute([
                    'admin'  => $current_admin_id,
                    'action' => $new_status === 'LOCKED' ? 'LOCK_USER' : 'UNLOCK_USER',
                    'target' => $target_user_id,
                ]);
            } catch (PDOException $e) {}

            $verb = ($new_status === 'LOCKED') ? 'khóa' : (($target['status'] === 'PENDING') ? 'duyệt' : 'mở khóa');
            set_flash('success', 'Đã ' . $verb . ' tài khoản ' . $target['username'] . '.');
        }
    }

    header('Location: ' . $redirect_to);
    exit;
}

/* ------------------------------------------------------------------
 * 5. TÌM KIẾM, LỌC, PHÂN TRANG TÀI KHOẢN
 * ------------------------------------------------------------------ */
$search        = trim($_GET['q'] ?? '');$filter_role   = in_array($_GET['role'] ?? '',$allowed_roles, true) ? $_GET['role'] : '';$filter_status = in_array($_GET['status'] ?? '',$allowed_statuses, true) ? $_GET['status'] : '';$per_page      = 10;
$page          = max(1, (int)($_GET['page'] ?? 1));

$where  = [];$params = [];

if ($search !== '') {$where[] = "(full_name LIKE :q1 OR username LIKE :q2 OR email LIKE :q3)";
    $params['q1'] =$params['q2'] = $params['q3'] = '\%' .$search . '%';
}
if ($filter_role !== '') {$where[] = "role = :role";
    $params['role'] =$filter_role;
}
if ($filter_status !== '') {$where[] = "status = :status";
    $params['status'] =$filter_status;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total_rows  = safe_count($conn, "SELECT COUNT(*) FROM users $where_sql", $params);$total_pages = max(1, (int)ceil($total_rows / $per_page));
$page        = min($page, $total_pages);$offset      = ($page - 1) *$per_page;

$stmt_users =$conn->prepare(
    "SELECT user_id, username, full_name, email, role, status
     FROM users $where_sql
     ORDER BY user_id DESC
     LIMIT " . (int)$per_page . " OFFSET " . (int)$offset
);
$stmt_users->execute($params);
$users =$stmt_users->fetchAll(PDO::FETCH_ASSOC);

/* ------------------------------------------------------------------
 * 6. LẤY DANH SÁCH ĐỀ THI KÈM TÊN NGƯỜI TẠO (GIÁO VIÊN)
 * ------------------------------------------------------------------ */
$stmt_exams =$conn->prepare(
    "SELECT e.exam_id, e.title, e.grade, e.duration, e.total_questions, 
            u.full_name AS teacher_name, u.username AS teacher_username
     FROM exam e
     LEFT JOIN users u ON e.user_id = u.user_id
     ORDER BY e.exam_id DESC
     LIMIT 15"
);
$stmt_exams->execute();
$exams_list =$stmt_exams->fetchAll(PDO::FETCH_ASSOC);

/* ------------------------------------------------------------------
 * 7. THỐNG KÊ HỆ THỐNG & DỮ LIỆU BIỂU ĐỒ
 * ------------------------------------------------------------------ */
$total_students = safe_count($conn, "SELECT COUNT(*) FROM users WHERE role = 'STUDENT'");
$total_teachers = safe_count($conn, "SELECT COUNT(*) FROM users WHERE role = 'TEACHER'");
$total_active   = safe_count($conn, "SELECT COUNT(*) FROM users WHERE status = 'ACTIVE'");
$total_locked   = safe_count($conn, "SELECT COUNT(*) FROM users WHERE status = 'LOCKED'");
$total_pending  = safe_count($conn, "SELECT COUNT(*) FROM users WHERE status = 'PENDING'");
$total_exams    = safe_count($conn, "SELECT COUNT(*) FROM exam");
$total_attempts = safe_count($conn, "SELECT COUNT(*) FROM exam_results");

// Thống kê số lượng đề thi theo khối lớp cho biểu đồ cột
$grade_labels = [];$grade_counts = [];
try {
    $stmt_grade =$conn->query("SELECT grade, COUNT(*) AS total FROM exam GROUP BY grade ORDER BY grade ASC");
    foreach ($stmt_grade->fetchAll(PDO::FETCH_ASSOC) as$row) {
        $grade_labels[] = 'Lớp ' . (int)$row['grade'];
        $grade_counts[] = (int)$row['total'];
    }
} catch (PDOException $e) {
    $grade_labels = [];$grade_counts = [];
}

$return_query =$_SERVER['QUERY_STRING'] ?? '';
$flash =$_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$role_labels = ['STUDENT' => 'Học viên', 'TEACHER' => 'Giáo viên', 'ADMIN' => 'Quản trị viên'];