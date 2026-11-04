<?php
session_start();
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/teacher_sidebar.php'; 

$teacher_id = $_SESSION['user_id'] ?? 0; 

$stmt_exams = $conn->prepare("SELECT exam_id, title, grade FROM exam ORDER BY exam_id DESC");
$stmt_exams->execute();
$exam_list = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

$selected_exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "
    SELECT 
        er.result_id,
        er.score,
        er.correct_answers_count AS correct_answers,
        er.tab_switches,
        er.total_questions,
        er.completion_time,
        er.created_at,
        u.full_name,
        u.class_name,
        u.dob,
        e.title AS exam_title,
        e.grade
    FROM exam_results er
    JOIN users u ON er.user_id = u.user_id
    JOIN exam e ON er.exam_id = e.exam_id
    WHERE 1=1
";

$params = [];

if ($selected_exam_id > 0) {
    $sql .= " AND er.exam_id = :exam_id";
    $params['exam_id'] = $selected_exam_id;
}

if ($search_keyword !== '') {
    $sql .= " AND (u.full_name LIKE :search OR u.class_name LIKE :search)";
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
    <link rel="stylesheet" href="/../assets/css/style.css">
</head>
<body>
<div class="et-overlay" id="etOverlay" onclick="etToggleSidebar()"></div>
  <div class="et-workspace">
    <header class="et-topbar">
      <div class="et-topbar-left">
        <button type="button" class="et-mobile-toggle" onclick="etToggleSidebar()" aria-label="Mở menu">☰</button>
        <div><div class="et-crumb">EDUTEACHER / GIÁO VIÊN</div><div class="et-top-title">Kết quả làm bài</div></div>
      </div>
      <div class="et-user"><span class="et-avatar">GV</span><span class="et-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Giáo viên', ENT_QUOTES, 'UTF-8') ?></span></div>
    </header>
    <main class="et-content">
<div class="container">
    <header class="page-header">
        <div class="page-title">
            <h2>📊 Kết quả làm bài thi của học sinh</h2>
            <p>Theo dõi điểm số, ngày sinh và thời gian nộp bài chi tiết.</p>
        </div>
    </header>

    <div class="card">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="exam_id">Lọc theo Đề thi</label>
                <select name="exam_id" id="exam_id">
                    <option value="0">-- Tất cả đề thi --</option>
                    <?php foreach ($exam_list as $e): ?>
                        <option value="<?= $e['exam_id'] ?>" <?= $selected_exam_id === (int)$e['exam_id'] ? 'selected' : '' ?>>
                            Lớp <?= htmlspecialchars($e['grade']) ?> - <?= htmlspecialchars($e['title']) ?>
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
                <a href="teacher_view_result.php" class="btn btn-warning">Đặt lại</a>
            </div>
        </form>
    </div>

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
                        <th>Rời bài</th>
                        <th>Ngày nộp bài</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $index => $row): ?>
                            <?php 
                                $dob = !empty($row['dob']) ? date('d/m/Y', strtotime($row['dob'])) : 'Chưa cập nhật';
                                $submitted_at = date('H:i - d/m/Y', strtotime($row['created_at']));
                                $score = (float)$row['score'];
                                $score_class = $score >= 5.0 ? 'score-high' : 'score-low';
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                                <td><span class="badge badge-class"><?= htmlspecialchars($row['class_name'] ?? 'N/A') ?></span></td>
                                <td><?= htmlspecialchars($dob) ?></td>
                                <td><span class="score-box <?= $score_class ?>"><?= number_format($score, 1) ?></span></td>
                                <td>
                                    <?php if ((int)$row['tab_switches'] > 0): ?>
                                        <span style="color:#b3261e;font-weight:600;">⚠️ <?= (int)$row['tab_switches'] ?> lần</span>
                                    <?php else: ?>
                                        <span style="color: var(--muted);">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><small style="color: var(--muted);"><?= htmlspecialchars($submitted_at) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
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
    </main>
  </div>
</div>
<script>
function etToggleSidebar() {
  const sidebar = document.getElementById('etSidebar');
  const overlay = document.getElementById('etOverlay');
  if (sidebar) sidebar.classList.toggle('open');
  if (overlay) overlay.classList.toggle('show');
}
</script>
</body>
</html>