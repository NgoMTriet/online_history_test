<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database.php';

$user_id = $_SESSION['user_id'] ?? 1;
$exam_id = (int)($_POST['exam_id'] ?? 0);
$user_answers = $_POST['answers'] ?? [];

$stmt = $conn->prepare("
    SELECT q.id, q.correct_option, q.explanation 
    FROM questions q 
    JOIN exam_questions eq ON q.id = eq.question_id 
    WHERE eq.exam_id = :exam_id
");
$stmt->execute(['exam_id' => $exam_id]);
$answer_keys = $stmt->fetchAll(PDO::FETCH_UNIQUE);

$correct_count = 0;
$total = count($answer_keys);

foreach ($answer_keys as $q_id => $data) {
    if (isset($user_answers[$q_id]) && $user_answers[$q_id] === $data['correct_option']) {
        $correct_count++;
    }
}

$score = round(($correct_count / max($total, 1)) * 10, 2);
$passed = ($score >= 5.0) ? 1 : 0;

$stmt_attempt = $conn->prepare("INSERT INTO exam_attempts (user_id, exam_id, score, passed, submitted_at) VALUES (?, ?, ?, ?, NOW())");
$stmt_attempt->execute([$user_id, $exam_id, $score, $passed]);

echo json_encode([
    'success' => true,
    'score' => $score,
    'correct_count' => $correct_count,
    'total_questions' => $total,
    'passed' => (bool)$passed
]);