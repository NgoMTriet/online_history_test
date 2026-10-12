<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database.php';

$exam_id = (int)($_GET['exam_id'] ?? 0);

$stmt_exam = $conn->prepare("SELECT id, title, duration_minutes FROM exams WHERE id = :id");
$stmt_exam->execute(['id' => $exam_id]);
$exam = $stmt_exam->fetch();

$stmt_q = $conn->prepare("
    SELECT q.id, q.question, q.option_a, q.option_b, q.option_c, q.option_d 
    FROM questions q 
    JOIN exam_questions eq ON q.id = eq.question_id 
    WHERE eq.exam_id = :exam_id
");
$stmt_q->execute(['exam_id' => $exam_id]);
$questions = $stmt_q->fetchAll();

$formatted = array_map(function($q) {
    return [
        'id' => (int)$q['id'],
        'question' => $q['question'],
        'options' => ['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']]
    ];
}, $questions);

echo json_encode(['success' => true, 'exam' => $exam, 'questions' => $formatted], JSON_UNESCAPED_UNICODE);