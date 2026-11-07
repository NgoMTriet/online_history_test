<?php
session_start();
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/teacher_sidebar.php'; 
date_default_timezone_set('Asia/Ho_Chi_Minh');

$user_role = strtoupper($_SESSION['role'] ?? '');
if (!isset($_SESSION['user_id']) || ($user_role !== 'TEACHER' && $user_role !== 'ADMIN')) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'] ?? 0;
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Giáo viên';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['exam_id'])) {
    $delete_id = (int)$_GET['exam_id'];

    try {
        $stmt_del_results = $conn->prepare("DELETE FROM exam_results WHERE exam_id = :exam_id");
        $stmt_del_results->execute(['exam_id' => $delete_id]);

        $stmt_del_exam = $conn->prepare("DELETE FROM exam WHERE exam_id = :exam_id");
        $stmt_del_exam->execute(['exam_id' => $delete_id]);

        $_SESSION['flash_message'] = "Đã xóa thành công đề thi #{$delete_id} và toàn bộ dữ liệu làm bài liên quan!";
        $_SESSION['flash_type'] = "success";
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Lỗi khi xóa đề thi: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_exam') {
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $duration = (int)($_POST['duration'] ?? 15);
    $maxAttempts = (int)($_POST['max_attempts'] ?? 0);
    $examPassword = trim($_POST['exam_password'] ?? '');

    $startTimeRaw = trim($_POST['start_time'] ?? '');
    $startTime = !empty($startTimeRaw) ? date('Y-m-d H:i:s', strtotime($startTimeRaw)) : null;

    $endTimeRaw = trim($_POST['end_time'] ?? '');
    $endTime = !empty($endTimeRaw) ? date('Y-m-d H:i:s', strtotime($endTimeRaw)) : null;

    if ($exam_id <= 0 || $title === '') {
        $_SESSION['flash_message'] = "Dữ liệu không hợp lệ!";
        $_SESSION['flash_type'] = "error";
    } elseif ($startTime !== null && $endTime !== null && strtotime($endTime) <= strtotime($startTime)) {
        $_SESSION['flash_message'] = "⚠️ Thời gian đóng đề thi phải sau thời gian mở đề thi!";
        $_SESSION['flash_type'] = "error";
    } else {
        try {
            $stmt_update = $conn->prepare("
                UPDATE exam
                SET title = :title,
                    duration = :duration,
                    start_time = :start_time,
                    end_time = :end_time,
                    max_attempts = :max_attempts,
                    exam_password = :exam_password
                WHERE exam_id = :exam_id
            ");

            $stmt_update->execute([
                'title' => $title,
                'duration' => $duration,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'max_attempts' => $maxAttempts,
                'exam_password' => $examPassword !== '' ? $examPassword : null,
                'exam_id' => $exam_id
            ]);

            $_SESSION['flash_message'] = "Cập nhật thông tin đề thi #{$exam_id} thành công!";
            $_SESSION['flash_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "Lỗi khi cập nhật đề thi: " . $e->getMessage();
            $_SESSION['flash_type'] = "error";
        }
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$stmt = $conn->query("SELECT COUNT(*) FROM exam");
$total_exams = (int)$stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM questions");
$total_questions = (int)$stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM exam_results");
$total_submissions = (int)$stmt->fetchColumn();

$stmt = $conn->query("SELECT AVG(score) FROM exam_results");
$avg_score = round((float)$stmt->fetchColumn(), 1);

$stmt_exams = $conn->prepare("
    SELECT e.*, COUNT(er.result_id) AS total_attempts
    FROM exam e
    LEFT JOIN exam_results er ON e.exam_id = er.exam_id
    GROUP BY e.exam_id
    ORDER BY e.exam_id DESC
    LIMIT 10
");
$stmt_exams->execute();
$recent_exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển Giáo viên | TestSuViet</title>

    <link rel="stylesheet" href="/../assets/css/UI_teacher.css">
</head>
<body>
<div class="et-overlay" id="etOverlay" onclick="etToggleSidebar()"></div>
  <div class="et-workspace">
    <header class="et-topbar">
      <div class="et-topbar-left">
        <button type="button" class="et-mobile-toggle" onclick="etToggleSidebar()" aria-label="Mở menu">☰</button>
        <div><div class="et-crumb">TEACHER / GIÁO VIÊN</div><div class="et-top-title">Tổng quan</div></div>
      </div>
      <div class="et-user"><span class="et-avatar">GV</span><span class="et-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Giáo viên', ENT_QUOTES, 'UTF-8') ?></span></div>
    </header>
    <main class="et-content">

    <nav class="navbar">
        <a href="index.php" class="brand">🏛️ TestSuViet Teacher</a>
        <div class="user-menu">
            <span class="user-badge">👨‍🏫 <?= htmlspecialchars($teacher_name) ?></span>
            <a href="profile.php" class="btn-link">Hồ sơ</a>
            <a href="login.php" class="back_home">Đăng xuất</a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($flash_message)): ?>
            <div class="alert alert-<?= $flash_type === 'success' ? 'success' : 'error' ?>">
                <?= $flash_type === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($flash_message) ?>
            </div>
        <?php endif; ?>

        <header class="welcome-header">
            <h2>Xin chào, Thầy/Cô <?= htmlspecialchars($teacher_name) ?></h2>
            <p>Bảng điều khiển quản lý đề thi Lịch sử, ngân hàng câu hỏi và xem kết quả học sinh.</p>
        </header>

        <div class="action-grid">
            <a href="teacher_exam.php" class="action-card">
                <div class="action-icon bg-blue">📝</div>
                <div class="action-info">
                    <h3>Tạo Đề Thi Mới</h3>
                    <p>Tạo thủ công hoặc bốc ngẫu nhiên câu hỏi theo cấu trúc</p>
                </div>
            </a>
            <a href="teacher_question.php" class="action-card">
                <div class="action-icon bg-amber">📚</div>
                <div class="action-info">
                    <h3>Ngân Hàng Câu Hỏi</h3>
                    <p>Thêm mới, chỉnh sửa và quản lý câu hỏi Lịch sử các khối</p>
                </div>
            </a>
            <a href="teacher_view_result.php" class="action-card">
                <div class="action-icon bg-green">📊</div>
                <div class="action-info">
                    <h3>Kết Quả Làm Bài</h3>
                    <p>Xem danh sách điểm số, lớp, ngày sinh và thời gian nộp bài</p>
                </div>
            </a>
            <a href="profile.php" class="action-card">
                <div class="action-icon bg-purple">⚙️</div>
                <div class="action-info">
                    <h3>Hồ Sơ Cá Nhân</h3>
                    <p>Cập nhật thông tin tài khoản và đổi mật khẩu</p>
                </div>
            </a>
        </div>

        <div class="card-table chart-card">
            <div class="table-header">
                <div>
                    <h2>📊 Thống kê tổng quan hệ thống</h2>
                    <p style="color:#64748b;font-size:15px;margin-top:5px">Tổng hợp tình hình đề thi, câu hỏi và kết quả học tập</p>
                </div>
            </div>
            <div style="position:relative;width:100%;height:380px">
                <canvas id="overviewChart"></canvas>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Tổng số đề thi</div>
                <div class="stat-value"><?= $total_exams ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Ngân hàng câu hỏi</div>
                <div class="stat-value"><?= $total_questions ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Lượt bài đã nộp</div>
                <div class="stat-value"><?= $total_submissions ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Điểm trung bình</div>
                <div class="stat-value"><?= $avg_score ?> <small style="font-size: 20px;">/10</small></div>
            </div>
        </div>

        <div class="card-table">
            <div class="table-header">
                <h2>📋 Danh sách đề thi đã tạo gần đây</h2>
                <a href="teacher_exam.php" class="btn-link">+ Tạo đề mới</a>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Đề Thi</th>
                            <th>Khối Lớp</th>
                            <th>Thời Gian</th>
                            <th>Khung Giờ Thi</th>
                            <th>Số Lần Tối Đa</th>
                            <th>Bảo Vệ</th>
                            <th>Lượt Làm</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_exams)): ?>
                            <?php foreach ($recent_exams as $exam): ?>
                                <tr>
                                    <td>#<?= $exam['exam_id'] ?></td>
                                    <td><strong><?= htmlspecialchars($exam['title']) ?></strong></td>
                                    <td><span class="badge-grade">Khối <?= $exam['grade'] ?></span></td>
                                    <td>⏱️ <?= $exam['duration'] ?> phút</td>
                                    <td style="font-size: 14px; color: var(--muted);">
                                        📅 Mở: <?= !empty($exam['start_time']) ? date('d/m/Y H:i', strtotime($exam['start_time'])) : 'Ngay lập tức' ?><br>
                                        🛑 Đóng: <?= !empty($exam['end_time']) ? date('d/m/Y H:i', strtotime($exam['end_time'])) : 'Không giới hạn' ?>
                                    </td>
                                    <td><?= $exam['max_attempts'] > 0 ? $exam['max_attempts'] . ' lần' : 'Tự do' ?></td>
                                    <td>
                                        <?php if (!empty($exam['exam_password'])): ?>
                                            <span class="badge-lock">🔒 Mật khẩu</span>
                                        <?php else: ?>
                                            <span style="color: var(--muted);">Công khai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= $exam['total_attempts'] ?></strong> lượt</td>
                                    <td style="white-space: nowrap;">
                                        <a href="teacher_view_result.php?exam_id=<?= $exam['exam_id'] ?>" class="btn-link">📊 Kết quả</a>
                                        <button type="button" class="btn-edit" onclick='openEditModal(<?= json_encode($exam, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>✏️ Sửa</button>
                                        <a href="?action=delete&exam_id=<?= $exam['exam_id'] ?>" class="btn-delete" onclick="return confirm('⚠️ Bạn có chắc chắn muốn xóa đề thi: «<?= htmlspecialchars(addslashes($exam['title'])) ?>»?');">🗑️ Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; color: var(--muted); padding: 30px;">
                                    Chưa có đề thi nào được tạo. Hãy nhấn "Tạo đề thi mới" để bắt đầu!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>✏️ Chỉnh sửa Đề thi <span id="modalExamId"></span></h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit_exam">
                <input type="hidden" name="exam_id" id="edit_exam_id">

                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_title">Tên đề thi</label>
                        <input type="text" id="edit_title" name="title" required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="edit_duration">Thời gian làm bài (phút)</label>
                            <input type="number" id="edit_duration" name="duration" min="1" max="300" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_max_attempts">Số lần làm tối đa</label>
                            <select name="max_attempts" id="edit_max_attempts">
                                <option value="0">Không giới hạn</option>
                                <option value="1">1 lần</option>
                                <option value="2">2 lần</option>
                                <option value="3">3 lần</option>
                                <option value="5">5 lần</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="edit_start_time">📅 Thời gian mở đề</label>
                            <input type="datetime-local" id="edit_start_time" name="start_time">
                        </div>
                        <div class="form-group">
                            <label for="edit_end_time">🛑 Thời gian đóng đề</label>
                            <input type="datetime-local" id="edit_end_time" name="end_time">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_password">🔑 Mật khẩu bài thi</label>
                        <input type="text" id="edit_password" name="exam_password" placeholder="Để trống nếu không đặt mật khẩu">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Hủy</button>
                    <button type="submit" class="btn-primary">💾 Cập nhật thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('overviewChart');
    new Chart(ctx, {
        data: {
            labels: ['Tổng số đề thi', 'Ngân hàng câu hỏi', 'Lượt bài đã nộp', 'Điểm trung bình'],
            datasets: [
                {
                    type: 'bar',
                    label: 'Số lượng',
                    data: [<?= $total_exams ?>, <?= $total_questions ?>, <?= $total_submissions ?>, null],
                    backgroundColor: ['#4f46e5', '#0d9488', '#f59e0b', 'transparent'],
                    borderRadius: 9,
                    yAxisID: 'y',
                    barThickness: 48
                },
                {
                    type: 'line',
                    label: 'Điểm trung bình (/10)',
                    data: [null, null, null, <?= $avg_score ?>],
                    borderColor: '#e11d48',
                    backgroundColor: '#e11d48',
                    pointRadius: 7,
                    pointHoverRadius: 10,
                    borderWidth: 3,
                    showLine: false,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 22 } } },
            scales: {
                y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Số lượng' }, ticks: { precision: 0 } },
                y1: { beginAtZero: true, min: 0, max: 10, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Điểm trung bình (/10)' } }
            }
        }
    });

    const editModal = document.getElementById('editModal');
    function openEditModal(exam) {
        document.getElementById('modalExamId').textContent = '#' + exam.exam_id;
        document.getElementById('edit_exam_id').value = exam.exam_id;
        document.getElementById('edit_title').value = exam.title;
        document.getElementById('edit_duration').value = exam.duration;
        document.getElementById('edit_max_attempts').value = exam.max_attempts;
        document.getElementById('edit_password').value = exam.exam_password || '';
        document.getElementById('edit_start_time').value = exam.start_time ? formatDateTimeLocal(exam.start_time) : '';
        document.getElementById('edit_end_time').value = exam.end_time ? formatDateTimeLocal(exam.end_time) : '';
        editModal.classList.add('active');
    }

    function closeEditModal() { editModal.classList.remove('active'); }
    function formatDateTimeLocal(dateTimeStr) {
        if (!dateTimeStr) return '';
        const dt = new Date(dateTimeStr);
        const year = dt.getFullYear();
        const month = String(dt.getMonth() + 1).padStart(2, '0');
        const day = String(dt.getDate()).padStart(2, '0');
        const hours = String(dt.getHours()).padStart(2, '0');
        const minutes = String(dt.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    editModal.addEventListener('click', function(e) { if (e.target === editModal) closeEditModal(); });
    document.getElementById('editForm').addEventListener('submit', function(e) {
        const startVal = document.getElementById('edit_start_time').value;
        const endVal = document.getElementById('edit_end_time').value;
        if (startVal && endVal && new Date(endVal) <= new Date(startVal)) {
            e.preventDefault();
            alert('⚠️ Thời gian đóng đề thi phải sau thời gian mở đề thi!');
        }
    });
    </script>
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