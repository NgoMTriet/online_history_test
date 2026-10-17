<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../database.php';

$grade = $_GET['grade'] ?? '';

try {
    if (!empty($grade)) {
        $stmt = $conn->prepare("SELECT DISTINCT grade, topic_name FROM topics WHERE grade = :grade");
        $stmt->execute(['grade' => $grade]);
    } else {
        $stmt = $conn->query("SELECT DISTINCT grade, topic_name FROM topics ORDER BY grade ASC");
    }
    $topics = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $topics], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(['success' => true, 'data' => [
        ['grade' => '6', 'topic_name' => 'Lịch sử Lớp 6 - Khai phá ban đầu'],
        ['grade' => '7', 'topic_name' => 'Lịch sử Lớp 7 - Đại Việt thời Lý - Trần'],
        ['grade' => '8', 'topic_name' => 'Lịch sử Lớp 8 - Khai thác thuộc địa'],
        ['grade' => '9', 'topic_name' => 'Lịch sử Lớp 9 - Phong trào Cách mạng 1930-1945']
    ]], JSON_UNESCAPED_UNICODE);
}
