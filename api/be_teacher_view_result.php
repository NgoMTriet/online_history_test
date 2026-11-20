<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$teacher_id = (int)$_SESSION['user_id'];

// 1. Chỉ lấy danh sách đề thi do chính giáo viên đang đăng nhập tạo
$stmt_exams = $conn->prepare("
    SELECT exam_id, title, grade
    FROM exam
    WHERE user_id = :teacher_id
    ORDER BY exam_id DESC
");
$stmt_exams->execute(['teacher_id' => $teacher_id]);
$exam_list = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

$selected_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$search_keyword   = isset($_GET['search']) ? trim($_GET['search']) : '';

// Ngăn giáo viên xem kết quả đề của người khác bằng cách sửa tham số ?exam_id= trên URL
$owned_exam_ids = array_map(fn($e) => (int)$e['exam_id'], $exam_list);
if ($selected_exam_id > 0 && !in_array($selected_exam_id, $owned_exam_ids, true)) {
    $selected_exam_id = 0;
}

// 2. Chỉ lấy kết quả làm bài thuộc các đề thi do giáo viên này tạo
$sql = "
    SELECT 
        er.result_id,
        er.user_id,
        er.exam_id,
        er.score,
        er.correct_answers_count AS correct_answers,
        er.tab_switches,
        er.total_questions,
        er.completion_time,
        er.created_at,
        u.full_name,
        u.class_name,
        u.dob,
        e.title AS exam_title,
        e.grade,
        (SELECT COUNT(*) FROM exam_results r2
         WHERE r2.user_id = er.user_id AND r2.exam_id = er.exam_id
           AND r2.result_id <= er.result_id) AS attempt_no
    FROM exam_results er
    JOIN users u ON er.user_id = u.user_id
    JOIN exam e ON er.exam_id = e.exam_id
    WHERE e.user_id = :teacher_id
      AND er.status <> 'IN_PROGRESS'
";

$params = [
    'teacher_id' => $teacher_id,
];

if ($selected_exam_id > 0) {
    $sql .= " AND er.exam_id = :exam_id";
    $params['exam_id'] = $selected_exam_id;
}

if ($search_keyword !== '') {
    $sql .= " AND (u.full_name LIKE :search OR u.class_name LIKE :search)";
    $params['search'] = '%' . $search_keyword . '%';
}

$sql .= " ORDER BY er.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
