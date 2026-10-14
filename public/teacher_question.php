<?php
require_once __DIR__ . '/../config/database.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_manual') {
    $grade = (int)($_POST['grade'] ?? 9);
    $question = trim($_POST['question'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct = $_POST['correct_option'] ?? 'A';
    $explanation = trim($_POST['explanation'] ?? '');

    if (!empty($question) && !empty($option_a) && !empty($option_b)) {
        try {
            // 1. Lấy hoặc tạo topic mặc định theo khối lớp
            $stmt_t = $conn->prepare("SELECT topic_id FROM topics WHERE grade = :grade LIMIT 1");
            $stmt_t->execute(['grade' => $grade]);
            $topic_id = $stmt_t->fetchColumn();

            if (!$topic_id) {
                $stmt_it = $conn->prepare("INSERT INTO topics (topic_name, grade) VALUES (:name, :grade)");
                $stmt_it->execute(['name' => "Chủ đề Lịch sử $grade", 'grade' => $grade]);
                $topic_id = $conn->lastInsertId();
            }

            // 2. Lưu câu hỏi vào questions
            $stmt_q = $conn->prepare("INSERT INTO questions (topic_id, content, difficulty, explanation) VALUES (:t_id, :content, 'EASY', :exp)");
            $stmt_q->execute(['t_id' => $topic_id, 'content' => $question, 'exp' => $explanation]);
            $question_id = $conn->lastInsertId();

            // 3. Lưu 4 đáp án vào answers
            $opts = ['A' => $option_a, 'B' => $option_b, 'C' => $option_c, 'D' => $option_d];
            $stmt_a = $conn->prepare("INSERT INTO answer (question_id, content, is_correct, is_fixed) VALUES (:q_id, :content, :is_corr, 0)");

            foreach ($opts as $key => $val) {
                if (!empty($val)) {
                    $stmt_a->execute([
                        'q_id' => $question_id,
                        'content' => $val,
                        'is_corr' => ($key === $correct) ? 1 : 0
                    ]);
                }
            }

            $message = "<div class='alert alert-success'>✅ Thêm câu hỏi vào Database phpMyAdmin thành công!</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>❌ Lỗi Database: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Lấy danh sách câu hỏi
$stmt_list = $conn->query("
    SELECT q.question_id, q.content, t.grade 
    FROM questions q 
    JOIN topics t ON q.topic_id = t.topic_id 
    ORDER BY q.question_id DESC LIMIT 50
");
$questions_list = $stmt_list->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Ngân Hàng Câu Hỏi</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; color: #333; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 25px; }
        h2 { color: #2c3e50; font-size: 1.3rem; border-bottom: 2px solid #eee; padding-bottom: 8px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #34495e; }
        input[type="text"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; color: white; }
        .btn-primary { background: #3498db; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; color: #2c3e50; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📚 Quản Lý Ngân Hàng Câu Hỏi</h1>
        <br>
        <?= $message ?>

        <div class="card">
            <h2>✍️ Thêm câu hỏi thủ công (Nhập tay)</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_manual">
                
                <div class="form-group">
                    <label>Khối lớp:</label>
                    <select name="grade" style="width: 200px;">
                        <option value="6">Lớp 6</option>
                        <option value="7">Lớp 7</option>
                        <option value="8">Lớp 8</option>
                        <option value="9" selected>Lớp 9</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nội dung câu hỏi:</label>
                    <textarea name="question" rows="3" placeholder="Nhập câu hỏi tại đây..." required></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Đáp án A:</label>
                        <input type="text" name="option_a" required>
                    </div>
                    <div class="form-group">
                        <label>Đáp án B:</label>
                        <input type="text" name="option_b" required>
                    </div>
                    <div class="form-group">
                        <label>Đáp án C:</label>
                        <input type="text" name="option_c">
                    </div>
                    <div class="form-group">
                        <label>Đáp án D:</label>
                        <input type="text" name="option_d">
                    </div>
                </div>

                <div class="form-group">
                    <label>Đáp án đúng:</label>
                    <select name="correct_option" style="width: 150px;">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Lời giải chi tiết:</label>
                    <textarea name="explanation" rows="2" placeholder="Giải thích vì sao đáp án này đúng..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Lưu câu hỏi</button>
            </form>
        </div>

        <?php if (!empty($questions_list)): ?>
        <div class="card">
            <h2>📋 Danh sách câu hỏi trong Ngân hàng</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Lớp</th>
                        <th>Câu hỏi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions_list as $q): ?>
                        <tr>
                            <td><?= $q['question_id'] ?></td>
                            <td><span style="background: #e1f5fe; color: #0288d1; padding: 2px 6px; border-radius: 4px; font-weight: bold;">Lớp <?= $q['grade'] ?></span></td>
                            <td><strong><?= htmlspecialchars($q['content']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
