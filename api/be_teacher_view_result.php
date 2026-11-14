<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$teacher_id = $_SESSION['user_id'] ?? 0;

$stmt_exams = $conn->prepare("SELECT exam_id, title, grade FROM exam ORDER BY exam_id DESC");
$stmt_exams->execute();
$exam_list = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

$selected_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "
    SELECT 
        er.result_id,
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
        e.grade
    FROM exam_results er
    JOIN users u ON er.user_id = u.user_id
    JOIN exam e ON er.exam_id = e.exam_id
    WHERE 1=1
";

$params = [];

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