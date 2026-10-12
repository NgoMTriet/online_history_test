<?php
require_once __DIR__ . '/database.php';

$message = "";

// Xử lý tạo đề thi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_exam') {
    $title = trim($_POST['title'] ?? '');
    $grade = (int)($_POST['grade'] ?? 9);
    $duration = (int)($_POST['duration'] ?? 15);
    $mode = $_POST['create_mode'] ?? 'manual';

    if (!empty($title)) {
        try {
            $selected_question_ids = [];

            if ($mode === 'manual') {
                $selected_question_ids = $_POST['question_ids'] ?? [];
            } else {
                $auto_count = (int)($_POST['auto_count'] ?? 10);
                
                // Bốc ngẫu nhiên câu hỏi theo khối lớp
                $stmt_rand = $conn->prepare("
                    SELECT q.question_id 
                    FROM questions q
                    JOIN topics t ON q.topic_id = t.topic_id
                    WHERE t.grade = :grade 
                    ORDER BY RAND() 
                    LIMIT :limit
                ");
                $stmt_rand->bindValue(':grade', $grade, PDO::PARAM_INT);
                $stmt_rand->bindValue(':limit', $auto_count, PDO::PARAM_INT);
                $stmt_rand->execute();
                $selected_question_ids = $stmt_rand->fetchAll(PDO::FETCH_COLUMN);
            }

            $total_q = count($selected_question_ids);

            if ($total_q > 0) {
                // 1. Lưu vào bảng exam
                $stmt_exam = $conn->prepare("
                    INSERT INTO exam (title, grade, duration, total_questions, is_randomized) 
                    VALUES (:title, :grade, :duration, :total_q, :is_rand)
                ");
                $stmt_exam->execute([
                    'title' => $title,
                    'grade' => $grade,
                    'duration' => $duration,
                    'total_q' => $total_q,
                    'is_rand' => ($mode === 'auto') ? 1 : 0
                ]);
                $exam_id = $conn->lastInsertId();

                // 2. Lưu liên kết vào bảng exam_questions
                $stmt_link = $conn->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (:exam_id, :question_id)");
                foreach ($selected_question_ids as $q_id) {
                    $stmt_link->execute(['exam_id' => $exam_id, 'question_id' => $q_id]);
                }

                $message = "<div class='alert alert-success'>✅ Tạo đề thi <strong>" . htmlspecialchars($title) . "</strong> thành công với <strong>{$total_q}</strong> câu hỏi!</div>";
            } else {
                $message = "<div class='alert alert-danger'>❌ Không tìm thấy câu hỏi phù hợp để tạo đề.</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>❌ Lỗi CSDL: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Lấy danh sách câu hỏi trong CSDL
$stmt_q = $conn->query("
    SELECT q.question_id, q.content, t.grade 
    FROM questions q 
    JOIN topics t ON q.topic_id = t.topic_id 
    ORDER BY t.grade ASC, q.question_id DESC
");
$questions_bank = $stmt_q->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo Đề Thi</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; color: #333; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 25px; }
        h2 { color: #2c3e50; margin-bottom: 15px; font-size: 1.3rem; border-bottom: 2px solid #eee; padding-bottom: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #34495e; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .mode-box { background: #eef7fc; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #3498db; display: flex; gap: 20px; }
        .q-list { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fafafa; }
        .q-item { padding: 8px 10px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; background: white; margin-bottom: 5px; }
        .btn { padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; color: white; background: #27ae60; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 Tạo Đề Thi Mới</h1>
        <br>
        <?= $message ?>

        <div class="card">
            <form method="POST">
                <input type="hidden" name="action" value="create_exam">

                <h2>1. Thông tin chung</h2>
                <div class="form-group">
                    <label>Tên đề thi:</label>
                    <input type="text" name="title" placeholder="Ví dụ: Đề thi Giữa kỳ 1 - Lịch sử 9" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Khối lớp:</label>
                        <select name="grade" id="gradeSelect" onchange="filterQuestions()">
                            <option value="6">Lớp 6</option>
                            <option value="7">Lớp 7</option>
                            <option value="8">Lớp 8</option>
                            <option value="9" selected>Lớp 9</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Thời gian làm bài (Phút):</label>
                        <input type="number" name="duration" value="15" min="1" required>
                    </div>
                </div>

                <h2>2. Phương thức chọn câu hỏi</h2>
                <div class="mode-box">
                    <label><input type="radio" name="create_mode" value="manual" checked onclick="toggleMode('manual')"> ✍️ Chọn thủ công</label>
                    <label><input type="radio" name="create_mode" value="auto" onclick="toggleMode('auto')"> ⚡ Tự động bốc ngẫu nhiên</label>
                </div>

                <div id="manual-section" class="form-group">
                    <label>Tích chọn câu hỏi:</label>
                    <div class="q-list">
                        <?php foreach ($questions_bank as $q): ?>
                            <div class="q-item question-row" data-grade="<?= $q['grade'] ?>">
                                <input type="checkbox" name="question_ids[]" value="<?= $q['question_id'] ?>" id="q_<?= $q['question_id'] ?>">
                                <label for="q_<?= $q['question_id'] ?>" style="font-weight: normal; margin: 0; cursor: pointer;">
                                    [Lớp <?= $q['grade'] ?>] <?= htmlspecialchars($q['content']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="auto-section" class="form-group" style="display: none;">
                    <label>Số lượng câu hỏi muốn sinh tự động:</label>
                    <input type="number" name="auto_count" value="10" min="1" max="100" style="width: 200px;">
                </div>

                <br>
                <button type="submit" class="btn">💾 Lưu Đề Thi</button>
            </form>
        </div>
    </div>

    <script>
        function toggleMode(mode) {
            document.getElementById('manual-section').style.display = (mode === 'manual') ? 'block' : 'none';
            document.getElementById('auto-section').style.display = (mode === 'auto') ? 'block' : 'none';
        }

        function filterQuestions() {
            const selectedGrade = document.getElementById('gradeSelect').value;
            document.querySelectorAll('.question-row').forEach(row => {
                row.style.display = (row.getAttribute('data-grade') === selectedGrade) ? 'flex' : 'none';
            });
        }
        document.addEventListener('DOMContentLoaded', filterQuestions);
    </script>
</body>
</html>