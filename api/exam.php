<?php
require_once __DIR__ . '/database.php';

// Kiểm tra quyền Giáo viên / Admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'teacher' &&$_SESSION['role'] !== 'admin')) {
    // die("Bạn không có quyền truy cập trang này!");
}

$message = "";

// --------------------------------------------------------------------------
// 1. XỬ LÝ LƯU ĐỀ THI KHI SUBMIT FORM
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) &&$_POST['action'] === 'create_exam') {
    $title = trim($_POST['title'] ?? '');
    $grade =$_POST['grade'] ?? '9';
    $duration = (int)($_POST['duration'] ?? 15);
    $mode =$_POST['create_mode'] ?? 'manual';

    if (!empty($title)) {
        try {
            // Bước 1: Lưu thông tin bài thi vào bảng `exams`
            $stmt_exam =$conn->prepare("
                INSERT INTO exams (title, grade, duration_minutes, created_at) 
                VALUES (:title, :grade, :duration, NOW())
            ");
            $stmt_exam->execute([
                'title' => $title,
                'grade' => $grade,
                'duration' => $duration
            ]);
            $exam_id =$conn->lastInsertId(); // Lấy ID đề thi vừa tạo

            $selected_question_ids = [];

            // Bước 2: Xử lý lấy danh sách câu hỏi theo chế độ được chọn
            if ($mode === 'manual') {
                // CHẾ ĐỘ 1: LẤY CÁC CÂU HỎI ĐƯỢC TÍCH CHECKBOX
                $selected_question_ids =$_POST['question_ids'] ?? [];
            } else {
                // CHẾ ĐỘ 2: BỐC NGẪU NHIÊN BẰNG SQL ORDER BY RAND()
                $auto_count = (int)($_POST['auto_count'] ?? 10);

                $stmt_rand =$conn->prepare("
                    SELECT id FROM questions 
                    WHERE grade = :grade 
                    ORDER BY RAND() 
                    LIMIT :limit
                ");
                // Binding tham số integer cho LIMIT trong PDO
                $stmt_rand->bindValue(':grade', $grade, PDO::PARAM_STR);$stmt_rand->bindValue(':limit', $auto_count, PDO::PARAM_INT);$stmt_rand->execute();

                $selected_question_ids =$stmt_rand->fetchAll(PDO::FETCH_COLUMN);
            }

            // Bước 3: Ghi liên kết Đề thi - Câu hỏi vào bảng liên kết `exam_questions`
            if (!empty($selected_question_ids)) {
                $stmt_link =$conn->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (:exam_id, :question_id)");
                foreach ($selected_question_ids as $q_id) {$stmt_link->execute([
                        'exam_id' => $exam_id,
                        'question_id' => $q_id
                    ]);
                }
                $count = count($selected_question_ids);$message = "<div class='alert alert-success'>✅ Tạo đề thi <strong>" . htmlspecialchars($title) . "</strong> thành công với <strong>{$count}</strong> câu hỏi!</div>";
            } else {
                $message = "<div class='alert alert-danger'>❌ Không có câu hỏi nào được chọn hoặc không tìm thấy câu hỏi phù hợp trong ngân hàng.</div>";
            }

        } catch (PDOException $e) {$message = "<div class='alert alert-danger'>❌ Lỗi CSDL: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>❌ Vui lòng nhập tên đề thi.</div>";
    }
}

// --------------------------------------------------------------------------
// 2. LẤY DANH SÁCH CÂU HỎI TRONG CSDL ĐỂ TẠO DANH SÁCH CHECKBOX
// --------------------------------------------------------------------------
$questions_bank = [];
try {
    $stmt_q =$conn->query("SELECT id, grade, question FROM questions ORDER BY grade ASC, id DESC");
    $questions_bank =$stmt_q->fetchAll();
} catch (PDOException $e) {
    // Dữ liệu fallback nếu CSDL chưa có dữ liệu
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Màn Hình Tạo Đề Thi</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; color: #333; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 25px; }
        h2 { color: #2c3e50; margin-bottom: 15px; font-size: 1.3rem; border-bottom: 2px solid #eee; padding-bottom: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #34495e; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.95rem; }
        
        .mode-box { background: #eef7fc; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #3498db; display: flex; gap: 20px; }
        .mode-box label { font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        
        /* Cấu trúc Bảng/Danh sách Checkbox */
        .q-list { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px; background: #fafafa; }
        .q-item { padding: 8px 10px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; background: white; margin-bottom: 5px; border-radius: 4px; }
        .q-item:hover { background-color: #f1f2f6; }
        .badge-grade { background: #e1f5fe; color: #0288d1; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; white-space: nowrap; }

        .btn { padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; color: white; font-size: 1rem; background: #27ae60; transition: background 0.2s; }
        .btn:hover { background: #219150; }
        
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 Màn Hình Tạo Đề Thi</h1>
        <br>
        <?= $message ?>

        <div class="card">
            <form method="POST">
                <input type="hidden" name="action" value="create_exam">

                <h2>1. Thông tin chung về Đề Thi</h2>
                <div class="form-group">
                    <label>Tên đề thi:</label>
                    <input type="text" name="title" placeholder="Ví dụ: Đề thi Giữa kỳ 1 - Lịch sử 9" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Khối lớp:</label>
                        <select name="grade" id="gradeSelect" onchange="filterQuestionsByGrade()">
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
                    <label>
                        <input type="radio" name="create_mode" value="manual" checked onclick="toggleMode('manual')"> 
                        ✍️ Chọn thủ công (Checkbox)
                    </label>
                    <label>
                        <input type="radio" name="create_mode" value="auto" onclick="toggleMode('auto')"> 
                        ⚡ Tự động bốc ngẫu nhiên (RANDOM)
                    </label>
                </div>

                <!-- CẤU TRÚC 1: CHỌN CÂU HỎI THỦ CÔNG (CHECKBOX) -->
                <div id="manual-section" class="form-group">
                    <label>Tích chọn các câu hỏi đưa vào đề thi:</label>
                    <div class="q-list">
                        <?php if (empty($questions_bank)): ?>
                            <p style="padding: 10px; color: #7f8c8d;">Chưa có câu hỏi nào trong ngân hàng.</p>
                        <?php else: ?>
                            <?php foreach ($questions_bank as$q): ?>
                                <div class="q-item question-row" data-grade="<?= $q['grade'] ?>">
                                    <input type="checkbox" name="question_ids[]" value="<?= $q['id'] ?>" id="q_<?= $q['id'] ?>">
                                    <label for="q_<?= $q['id'] ?>" style="font-weight: normal; margin: 0; cursor: pointer; flex-grow: 1;">
                                        <span class="badge-grade">Lớp <?= $q['grade'] ?></span>
                                        <?= htmlspecialchars($q['question']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CẤU TRÚC 2: TỰ ĐỘNG BỐC MA TRẬN (ORDER BY RAND) -->
                <div id="auto-section" class="form-group" style="display: none;">
                    <div style="background: #fff3cd; color: #856404; padding: 12px; border-radius: 4px; margin-bottom: 15px;">
                        💡 Hệ thống sẽ tự động thực hiện câu lệnh SQL <code>ORDER BY RAND()</code> để rút ngẫu nhiên danh sách câu hỏi theo khối lớp đã chọn.
                    </div>
                    <label>Số lượng câu hỏi cần sinh tự động:</label>
                    <input type="number" name="auto_count" value="10" min="1" max="100" style="width: 200px;">
                </div>

                <br>
                <button type="submit" class="btn">💾 Xuất Đề Thi</button>
            </form>
        </div>
    </div>

    <script>
        // Chuyển đổi giữa 2 chế độ Tạo đề thủ công và Tự động
        function toggleMode(mode) {
            document.getElementById('manual-section').style.display = (mode === 'manual') ? 'block' : 'none';
            document.getElementById('auto-section').style.display = (mode === 'auto') ? 'block' : 'none';
        }

        // Tự động lọc danh sách checkbox câu hỏi theo khối lớp đang chọn
        function filterQuestionsByGrade() {
            const selectedGrade = document.getElementById('gradeSelect').value;
            const rows = document.querySelectorAll('.question-row');

            rows.forEach(row => {
                if (row.getAttribute('data-grade') === selectedGrade) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Chạy lọc sẵn khi vừa nạp trang
        document.addEventListener('DOMContentLoaded', filterQuestionsByGrade);
    </script>
</body>
</html>