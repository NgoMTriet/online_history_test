<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $grade = $_POST['grade'] ?? '9';
    $question = trim($_POST['question'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct = $_POST['correct_option'] ?? 'A';
    $explanation = trim($_POST['explanation'] ?? '');
    $is_fixed = isset($_POST['is_fixed']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO questions (grade, question, option_a, option_b, option_c, option_d, correct_option, explanation, is_fixed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$grade, $question, $option_a, $option_b, $option_c, $option_d, $correct, $explanation, $is_fixed]);

    echo json_encode(['success' => true, 'message' => 'Lưu câu hỏi thành công!']);
} elseif ($method === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $id = $_DELETE['id'] ?? 0;

    $stmt = $conn->prepare("DELETE FROM questions WHERE id = :id");
    $stmt->execute(['id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Xóa câu hỏi thành công!']);
}