<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database.php';

try {
    $total_students = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $total_exams = $conn->query("SELECT COUNT(*) FROM exams")->fetchColumn();
    $total_attempts = $conn->query("SELECT COUNT(*) FROM exam_attempts")->fetchColumn();

    $stmt_grade = $conn->query("
        SELECT e.grade, COUNT(a.id) as attempts, ROUND(AVG(a.score), 2) as avg_score 
        FROM exams e 
        LEFT JOIN exam_attempts a ON e.id = a.exam_id 
        GROUP BY e.grade
    ");
    $by_grade = $stmt_grade->fetchAll();

    echo json_encode([
        'success' => true,
        'overview' => [
            'total_students' => (int)$total_students,
            'total_exams' => (int)$total_exams,
            'total_attempts' => (int)$total_attempts
        ],
        'by_grade' => $by_grade
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}