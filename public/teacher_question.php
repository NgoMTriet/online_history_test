<?php
require_once __DIR__ . '/../config/database.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = "";

// 1. LẤY GIÁ TRỊ KHỐI LỚP VÀ CHỦ ĐỀ HIỆN TẠI
$selected_grade = isset($_REQUEST['grade']) ? (int)$_REQUEST['grade'] : 9;
$selected_topic_id = isset($_REQUEST['topic_id']) ? (int)$_REQUEST['topic_id'] : 0;

// 2. XỬ LÝ KHI GIÁO VIÊN TẠO CHỦ ĐỀ MỚI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_topic') {
    $new_topic_name = trim($_POST['new_topic_name'] ?? '');
    
    if (!empty($new_topic_name)) {
        try {
            $stmt = $conn->prepare("SELECT topic_id FROM topics WHERE topic_name = :name AND grade = :grade LIMIT 1");
            $stmt->execute(['name' => $new_topic_name, 'grade' => $selected_grade]);
            $existing_id = $stmt->fetchColumn();

            if ($existing_id) {
                $selected_topic_id = $existing_id;
                $message = "<div class='alert alert-warning'>⚠️ Chủ đề này đã tồn tại. Đã tự động chọn chủ đề!</div>";
            } else {
                $stmt_ins = $conn->prepare("INSERT INTO topics (topic_name, grade) VALUES (:name, :grade)");
                $stmt_ins->execute(['name' => $new_topic_name, 'grade' => $selected_grade]);
                $selected_topic_id = $conn->lastInsertId();
                $message = "<div class='alert alert-success'>✅ Đã tạo thành công chủ đề mới: <strong>" . htmlspecialchars($new_topic_name) . "</strong></div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>❌ Lỗi tạo chủ đề: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>❌ Vui lòng nhập tên chủ đề!</div>";
    }
}

// 3. XỬ LÝ THÊM CÂU HỎI THỦ CÔNG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_manual') {
    $topic_id = (int)($_POST['topic_id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct  = strtoupper(trim($_POST['correct_option'] ?? 'A'));
    $explanation = trim($_POST['explanation'] ?? '');

    if ($topic_id > 0 && !empty($question) && !empty($option_a)) {
        try {
            $stmt_q = $conn->prepare("INSERT INTO questions (topic_id, content, difficulty, explanation) VALUES (:t_id, :content, 'EASY', :exp)");
            $stmt_q->execute(['t_id' => $topic_id, 'content' => $question, 'exp' => $explanation]);
            $question_id = $conn->lastInsertId();

            $opts = ['A' => $option_a, 'B' => $option_b, 'C' => $option_c, 'D' => $option_d];
            $stmt_a = $conn->prepare("INSERT INTO answer (question_id, option_code, content, is_correct, is_fixed) VALUES (:q_id, :code, :content, :is_corr, 0)");

            foreach ($opts as $key => $val) {
                $val = trim($val);
                if ($val !== '') {
                    $stmt_a->execute([
                        'q_id'    => $question_id,
                        'code'    => $key,
                        'content' => $val,
                        'is_corr' => ($key === $correct) ? 1 : 0
                    ]);
                }
            }

            $message = "<div class='alert alert-success'>✅ Thêm câu hỏi thủ công thành công!</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>❌ Lỗi Database: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// 4. XỬ LÝ IMPORT FILE EXCEL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_excel') {
    $topic_id = (int)($_POST['topic_id'] ?? 0);

    if ($topic_id > 0 && isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['excel_file']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($fileTmpPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $count = 0;
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Bỏ qua hàng tiêu đề

                $question = trim($row[0] ?? '');
                $option_a = trim($row[1] ?? '');
                $option_b = trim($row[2] ?? '');
                $option_c = trim($row[3] ?? '');
                $option_d = trim($row[4] ?? '');
                
                $raw_correct = strtoupper(trim($row[5] ?? 'A'));
                $correct = in_array($raw_correct, ['A', 'B', 'C', 'D']) ? $raw_correct : 'A';
                
                // 🎯 ĐỌC LỜI GIẢI TỪ CỘT I (index 8)
                $explanation = trim($row[8] ?? '');

                if (!empty($question) && !empty($option_a)) {
                    $stmt_q = $conn->prepare("INSERT INTO questions (topic_id, content, difficulty, explanation) VALUES (:t_id, :content, 'EASY', :exp)");
                    $stmt_q->execute(['t_id' => $topic_id, 'content' => $question, 'exp' => $explanation]);
                    $question_id = $conn->lastInsertId();

                    $opts = ['A' => $option_a, 'B' => $option_b, 'C' => $option_c, 'D' => $option_d];
                    $stmt_a = $conn->prepare("INSERT INTO answer (question_id, option_code, content, is_correct, is_fixed) VALUES (:q_id, :code, :content, :is_corr, 0)");

                    foreach ($opts as $key => $val) {
                        $val = trim($val);
                        if ($val !== '') {
                            $stmt_a->execute([
                                'q_id'    => $question_id,
                                'code'    => $key,
                                'content' => $val,
                                'is_corr' => ($key === $correct) ? 1 : 0
                            ]);
                        }
                    }
                    $count++;
                }
            }

            $message = "<div class='alert alert-success'>✅ Đã import thành công $count câu hỏi (Lời giải chi tiết lấy chuẩn từ Cột I)!</div>";
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>❌ Lỗi xử lý file Excel: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>❌ Vui lòng chọn chủ đề và file Excel hợp lệ!</div>";
    }
}

// 5. LẤY DANH SÁCH CÁC CHỦ ĐỀ THEO LỚP
$stmt_topics = $conn->prepare("SELECT topic_id, topic_name FROM topics WHERE grade = :grade ORDER BY topic_id DESC");
$stmt_topics->execute(['grade' => $selected_grade]);
$topics_list = $stmt_topics->fetchAll();

// Lấy thông tin chủ đề đang chọn (nếu có)
$current_topic = null;
if ($selected_topic_id > 0) {
    $stmt_ct = $conn->prepare("SELECT topic_id, topic_name, grade FROM topics WHERE topic_id = :t_id LIMIT 1");
    $stmt_ct->execute(['t_id' => $selected_topic_id]);
    $current_topic = $stmt_ct->fetch();
}

// Lấy danh sách câu hỏi mới
$stmt_list = $conn->query("
    SELECT q.question_id, q.content, t.grade, t.topic_name 
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
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 25px; }
        h2 { color: #2c3e50; font-size: 1.3rem; border-bottom: 2px solid #eee; padding-bottom: 8px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #34495e; }
        input[type="text"], input[type="file"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; color: white; text-decoration: none; display: inline-block; }
        .btn-primary { background: #3498db; }
        .btn-success { background: #2ecc71; }
        .btn-warning { background: #f39c12; }
        .btn-secondary { background: #7f8c8d; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-warning { background: #fff3cd; color: #856404; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-info { background: #d1ecf1; color: #0c5460; font-size: 1.05rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; color: #2c3e50; }
        .file-hint { font-size: 0.85rem; color: #666; margin-top: 5px; }
        .badge { background: #e1f5fe; color: #0288d1; padding: 3px 8px; border-radius: 4px; font-weight: bold; }
        .badge-topic { background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 4px; font-weight: bold; }
    </style>
    <script>
        function onGradeChange(gradeSelect) {
            window.location.href = "?grade=" + gradeSelect.value;
        }
        function onTopicSelect(topicSelect) {
            const grade = document.getElementById('grade_select').value;
            window.location.href = "?grade=" + grade + "&topic_id=" + topicSelect.value;
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <h1>📚 Quản Lý Ngân Hàng Câu Hỏi</h1>
            <a href="../index.php" class="btn btn-secondary">🏠 Trang chủ</a>
        </div>

        <?= $message ?>

        <!-- BƯỚC 1: CHỌN HOẶC TẠO CHỦ ĐỀ -->
        <div class="card">
            <h2>📌 BƯỚC 1: Chọn hoặc Tạo Chủ Đề (bảng <code>topics</code>)</h2>
            
            <div class="grid-2">
                <div>
                    <div class="form-group">
                        <label>Chọn Khối Lớp:</label>
                        <select id="grade_select" name="grade" onchange="onGradeChange(this)">
                            <option value="6" <?= $selected_grade == 6 ? 'selected' : '' ?>>Lớp 6</option>
                            <option value="7" <?= $selected_grade == 7 ? 'selected' : '' ?>>Lớp 7</option>
                            <option value="8" <?= $selected_grade == 8 ? 'selected' : '' ?>>Lớp 8</option>
                            <option value="9" <?= $selected_grade == 9 ? 'selected' : '' ?>>Lớp 9</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Chọn Chủ Đề đã có trong CSDL:</label>
                        <select name="topic_id" onchange="onTopicSelect(this)">
                            <option value="0">-- Bấm để chọn Chủ Đề --</option>
                            <?php foreach ($topics_list as $t): ?>
                                <option value="<?= $t['topic_id'] ?>" <?= $selected_topic_id == $t['topic_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['topic_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="border-left: 2px solid #eee; padding-left: 20px;">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_topic">
                        <input type="hidden" name="grade" value="<?= $selected_grade ?>">

                        <div class="form-group">
                            <label>Tạo chủ đề mới cho Lớp <?= $selected_grade ?>:</label>
                            <input type="text" name="new_topic_name" placeholder="Ví dụ: Chiến tranh thế giới thứ hai..." required>
                        </div>
                        <button type="submit" class="btn btn-warning">➕ Tạo Chủ Đề Mới</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- BƯỚC 2: CHỈ HIỂN THỊ KHI ĐÃ CHỌN CHỦ ĐỀ -->
        <?php if ($current_topic): ?>
            <div class="alert alert-info">
                🎯 Bạn đang làm việc với Chủ Đề: <strong><?= htmlspecialchars($current_topic['topic_name']) ?></strong> (Khối Lớp <?= $current_topic['grade'] ?>)
            </div>

            <!-- CARD IMPORT EXCEL -->
            <div class="card">
                <h2>📊 BƯỚC 2A: Import câu hỏi từ Excel vào chủ đề: "<?= htmlspecialchars($current_topic['topic_name']) ?>"</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_excel">
                    <input type="hidden" name="grade" value="<?= $selected_grade ?>">
                    <input type="hidden" name="topic_id" value="<?= $selected_topic_id ?>">

                    <div class="form-group">
                        <label>Chọn File Excel (.xlsx / .csv):</label>
                        <input type="file" name="excel_file" accept=".xlsx, .xls, .csv" required>
                    </div>

                    <div class="file-hint">
                        📌 <strong>Cấu trúc cột Excel chuẩn:</strong><br>
                        • <strong>Cột A:</strong> Câu hỏi | <strong>Cột B - E:</strong> Đáp án A, B, C, D | <strong>Cột F:</strong> Đáp án đúng | <strong>Cột I:</strong> Lời giải chi tiết
                    </div>
                    <br>
                    <button type="submit" class="btn btn-success">📥 Import từ Excel</button>
                </form>
            </div>

            <!-- CARD NHẬP THỦ CÔNG -->
            <div class="card">
                <h2>✍️ BƯỚC 2B: Thêm câu hỏi thủ công vào chủ đề: "<?= htmlspecialchars($current_topic['topic_name']) ?>"</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_manual">
                    <input type="hidden" name="grade" value="<?= $selected_grade ?>">
                    <input type="hidden" name="topic_id" value="<?= $selected_topic_id ?>">

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

                    <button type="submit" class="btn btn-primary">💾 Lưu câu hỏi</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="text-align: center; padding: 20px;">
                ⚠️ <strong>VUI LÒNG CHỌN HOẶC TẠO CHỦ ĐỀ Ở BƯỚC 1 TRƯỚC KHI THÊM CÂU HỎI</strong>
            </div>
        <?php endif; ?>

        <!-- DANH SÁCH CÂU HỎI -->
        <?php if (!empty($questions_list)): ?>
        <div class="card">
            <h2>📋 Danh sách câu hỏi mới thêm gần đây</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Lớp</th>
                        <th>Chủ đề (topics)</th>
                        <th>Câu hỏi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions_list as $q): ?>
                        <tr>
                            <td><?= $q['question_id'] ?></td>
                            <td><span class="badge">Lớp <?= $q['grade'] ?></span></td>
                            <td><span class="badge-topic"><?= htmlspecialchars($q['topic_name']) ?></span></td>
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
