<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Giả sử lấy teacher_id từ session sau khi giáo viên đăng nhập
$teacher_id = $_SESSION['user_id'] ?? 0; 

// Lấy danh sách đề thi của giáo viên để làm bộ lọc (Filter)
$stmt_exams = $conn->prepare("
    SELECT exam_id, title, grade 
    FROM exam 
    ORDER BY exam_id DESC
");
$stmt_exams->execute();
$exam_list = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

// Lấy exam_id được chọn từ bộ lọc (mặc định lấy đề mới nhất hoặc tất cả)
$selected_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

/*
|--------------------------------------------------------------------------
| TRUY VẤN LẤY KẾT QUẢ BÀI LÀM CỦA HỌC SINH
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT 
        er.result_id,
        er.score,
        er.correct_answers,
        er.total_questions,
        er.completion_time,
        er.created_at,
        u.full_name,
        e.title AS exam_title,
        e.grade
    FROM exam_results er
    JOIN users u ON er.user_id = u.user_id
    JOIN exam e ON er.exam_id = e.exam_id
    WHERE 1=1
";

$params = [];

// Lọc theo đề thi
if ($selected_exam_id > 0) {
    $sql .= " AND er.exam_id = :exam_id";
    $params['exam_id'] = $selected_exam_id;
}

// Lọc theo từ khóa tìm kiếm (Tên học sinh hoặc Lớp)
if ($search_keyword !== '') {
    $sql .= " AND (full_name LIKE :search OR class_name LIKE :search)";
    $params['search'] = '%' . $search_keyword . '%';
}

$sql .= " ORDER BY er.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả làm bài thi của học sinh | TestSuViet</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --success: #16a34a;
            --danger: #dc2626;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            padding: 30px 20px;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .page-title h1 {
            font-size: 26px;
            color: #0f172a;
        }

        .page-title p {
            color: var(--muted);
            font-size: 14px;
            margin-top: 4px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            padding: 24px;
            margin-bottom: 25px;
        }

        /* Filter Controls */
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) auto;
            gap: 15px;
            align-items: end;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: #fff;
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }

        /* Table Design */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 700;
            padding: 14px;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        /* Badge Styles */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-class { background: #e0e7ff; color: #4338ca; }
        .score-box {
            font-size: 16px;
            font-weight: 800;
        }

        .score-high { color: var(--success); }
        .score-low { color: var(--danger); }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }

        .empty-state icon {
            font-size: 40px;
            display: block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">

    <header class="page-header">
        <div class="page-title">
            <h1>📊 Kết quả làm bài thi của học sinh</h1>
            <p>Theo dõi điểm số, ngày sinh và thời gian nộp bài chi tiết.</p>
        </div>
        <a href="/index.php" class="btn btn-secondary">⬅ Quay lại Trang Chủ</a>
    </header>

    <!-- THANH LỌC VÀ TÌM KIẾM -->
    <div class="card">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="exam_id">Lọc theo Đề thi</label>
                <select name="exam_id" id="exam_id">
                    <option value="0">-- Tất cả đề thi --</option>
                    <?php foreach ($exam_list as $e): ?>
                        <option value="<?= $e['exam_id'] ?>" <?= $selected_exam_id === (int)$e['exam_id'] ? 'selected' : '' ?>>
                            Lớp <?= $e['grade'] ?> - <?= htmlspecialchars($e['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="search">Tìm kiếm Học sinh / Lớp</label>
                <input type="text" name="search" id="search" placeholder="Nhập tên học sinh hoặc tên lớp..." value="<?= htmlspecialchars($search_keyword) ?>">
            </div>

            <div>
                <button type="submit" class="btn btn-primary">🔍 Lọc danh sách</button>
                <a href="teacher_view_results.php" class="btn btn-secondary">Đặt lại</a>
            </div>
        </form>
    </div>

    <!-- BẢNG DANH SÁCH BÀI LÀM -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Họ và Tên</th>
                        <th>Lớp</th>
                        <th>Ngày sinh</th>
                        <th>Điểm số</th>
                        <th>Ngày nộp bài</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $index => $row): ?>
                            <?php 
                                // Format Ngày sinh
                                $dob = !empty($row['dob']) ? date('d/m/Y', strtotime($row['dob'])) : 'Chưa cập nhật';
                                
                                // Format Thời gian nộp bài
                                $submitted_at = date('H:i - d/m/Y', strtotime($row['created_at']));

                                // Format Thời gian làm bài (Phút:Giây)
                                $seconds = (int)$row['completion_time'];
                                $time_spent = sprintf('%02d phút %02d giây', floor($seconds / 60), $seconds % 60);

                                // Tô màu điểm số
                                $score = (float)$row['score'];
                                $score_class = $score >= 5.0 ? 'score-high' : 'score-low';
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                                <td><span class="badge badge-class"><?= htmlspecialchars($row['class_name'] ?? 'N/A') ?></span></td>
                                <td><?= $dob ?></td>
                                <td>
                                    <span class="score-box <?= $score_class ?>"><?= number_format($score, 1) ?></span>
                                </td>
                                <td><small style="color: var(--muted);"><?= $submitted_at ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <span>📭</span>
                                    <p>Chưa có lượt làm bài nào phù hợp với bộ lọc.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>