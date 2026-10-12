<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database.php';

$user_id = $_SESSION['user_id'] ?? 1;

$stmt = $conn->prepare("
    SELECT a.id, e.title, a.score, a.passed, a.submitted_at 
    FROM exam_attempts a 
    JOIN exams e ON a.exam_id = e.id 
    WHERE a.user_id = :user_id 
    ORDER BY a.submitted_at ASC
");
$stmt->execute(['user_id' => $user_id]);
$attempts = $stmt->fetchAll();

$chart_labels = [];
$chart_scores = [];

foreach ($attempts as $row) {
    $chart_labels[] = date('d/m H:i', strtotime($row['submitted_at']));
    $chart_scores[] = (float)$row['score'];
}

echo json_encode([
    'success' => true,
    'history' => $attempts,
    'chart_data' => [
        'labels' => $chart_labels,
        'scores' => $chart_scores
    ]
], JSON_UNESCAPED_UNICODE);