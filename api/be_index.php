<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xác định thư mục gốc dự án (lùi 1 cấp từ thư mục api/)
$rootDir = dirname(__DIR__);

$configPath = file_exists($rootDir . '/config/database.php')
    ? $rootDir . '/config/database.php'
    : $rootDir . '/../config/database.php';

$headerPath = file_exists($rootDir . '/includes/header.php')
    ? $rootDir . '/includes/header.php'
    : $rootDir . '/../includes/header.php';

$footerPath = file_exists($rootDir . '/includes/footer.php')
    ? $rootDir . '/includes/footer.php'
    : $rootDir . '/../includes/footer.php';

// Nạp cấu hình Cơ sở dữ liệu
require_once $configPath;

// Nạp header và chèn CSS riêng của trang chủ vào thẻ <head>
ob_start();
include $headerPath;
$headerHtml = ob_get_clean();

$styleTag = '<link rel="stylesheet" href="assets/css/index.css?v=1">' . "\n";
if (stripos($headerHtml, '</head>') !== false) {
    $headerHtml = preg_replace_callback('~</head>~i', function () use ($styleTag) {
        return $styleTag . '</head>';
    }, $headerHtml, 1);
} else {
    $headerHtml .= $styleTag;
}

// Kiểm tra vai trò Giáo viên
$isTeacher = (
    isset($_SESSION['user_id'], $_SESSION['role'])
    && strtoupper($_SESSION['role']) === 'TEACHER'
);

// Đọc tham số tìm kiếm & lọc khối lớp từ URL
$keyword = trim($_GET['keyword'] ?? '');
$grade   = trim($_GET['grade'] ?? '');
$subject = trim($_GET['subject'] ?? '');

// Truy vấn danh sách đề thi kèm tên giáo viên tạo đề
$sql = "SELECT e.exam_id, e.title, e.grade, e.duration, e.total_questions,
               u.full_name AS teacher_name, s.subject_name
        FROM exam e
        LEFT JOIN users u ON e.user_id = u.user_id
        LEFT JOIN subjects s ON e.subject_id = s.subject_id
        WHERE 1=1";
$params = [];

if ($keyword !== '') {
    $sql .= " AND e.title LIKE :keyword";
    $params['keyword'] = '%' . $keyword . '%';
}

if ($grade !== '') {
    $sql .= " AND e.grade = :grade";
    $params['grade'] = (int)$grade;
}

if ($subject !== '') {
    $sql .= " AND s.subject_name = :subject";
    $params['subject'] = $subject;
}

$sql .= " ORDER BY e.exam_id DESC";

$stmt_exams = $conn->prepare($sql);
$stmt_exams->execute($params);

$filtered_exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);
$total_filtered = count($filtered_exams);
$initial_limit  = 6;

// Danh sách tên môn (cho ô lọc) và các thẻ "lớp - môn" kèm số đề thi
$subject_names = $conn->query("SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name ASC")
                      ->fetchAll(PDO::FETCH_COLUMN);

$subject_cards = $conn->query(
    "SELECT s.subject_name, s.grade, COUNT(e.exam_id) AS exam_count
     FROM subjects s
     LEFT JOIN exam e ON e.subject_id = s.subject_id
     GROUP BY s.subject_id, s.subject_name, s.grade
     ORDER BY s.grade ASC, s.subject_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);
