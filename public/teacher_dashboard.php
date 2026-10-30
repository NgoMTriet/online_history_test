<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Thiết lập múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kiểm tra quyền Giáo viên (Nếu không phải GV thì chuyển hướng)
$user_role = strtoupper($_SESSION['role'] ?? '');
if (!isset($_SESSION['user_id']) || ($user_role !== 'TEACHER' && $user_role !== 'ADMIN')) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'] ?? 0;
$teacher_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Giáo viên';

/*
|--------------------------------------------------------------------------
| XỬ LÝ 1. XÓA ĐỀ THI
|--------------------------------------------------------------------------
*/
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['exam_id'])) {
    $delete_id = (int)$_GET['exam_id'];

    try {
        // 1. Xóa các lượt làm bài liên quan trong bảng exam_results trước để tránh lỗi khóa ngoại
        $stmt_del_results = $conn->prepare("DELETE FROM exam_results WHERE exam_id = :exam_id");
        $stmt_del_results->execute(['exam_id' => $delete_id]);

        // 2. Tiến hành xóa đề thi khỏi bảng exam
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

/*
|--------------------------------------------------------------------------
| XỬ LÝ 2. CHỈNH SỬA ĐỀ THI (CẬP NHẬT THỜI GIAN MỞ/ĐÓNG ĐỀ...)
|--------------------------------------------------------------------------
*/
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

// Lấy thông báo flash (nếu có)
$flash_message = $_SESSION['flash_message'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

/*
|--------------------------------------------------------------------------
| THỐNG KÊ TỔNG QUAN HỆ THỐNG
|--------------------------------------------------------------------------
*/
$stmt = $conn->query("SELECT COUNT(*) FROM exam");
$total_exams = (int)$stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM questions");
$total_questions = (int)$stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM exam_results");
$total_submissions = (int)$stmt->fetchColumn();

$stmt = $conn->query("SELECT AVG(score) FROM exam_results");
$avg_score = round((float)$stmt->fetchColumn(), 1);

/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH ĐỀ THI ĐÃ TẠO GẦN ĐÂY
|--------------------------------------------------------------------------
*/
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
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --success: #16a34a;
            --warning: #d97706;
            --danger: #dc2626;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.5;
            padding-bottom: 50px;
        }

        /* Header Navigation */
        .navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-badge {
            background: #eff6ff;
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        /* Container Layout */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-header { margin-bottom: 30px; }
        .welcome-header h1 { font-size: 28px; color: var(--text); }
        .welcome-header p { color: var(--muted); font-size: 15px; }

        /* Alert Notification */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* Quick Action Cards */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .action-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-color: var(--primary);
        }

        .action-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 26px;
            flex-shrink: 0;
        }

        .bg-blue { background: #dbeafe; color: #1d4ed8; }
        .bg-green { background: #dcfce7; color: #15803d; }
        .bg-purple { background: #f3e8ff; color: #7e22ce; }
        .bg-amber { background: #fef3c7; color: #b45309; }

        .action-info h3 { font-size: 17px; color: var(--text); margin-bottom: 4px; }
        .action-info p { font-size: 13px; color: var(--muted); }

        /* Stat Counter Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 30px;
            font-weight: 800;
            color: var(--text);
            margin-top: 6px;
        }

        /* Data Table Section */
        .card-table {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h2 { font-size: 18px; color: var(--text); }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f8fafc;
            color: var(--muted);
            font-weight: 700;
            padding: 12px 16px;
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:hover { background-color: #f8fafc; }

        .badge-grade { background: #f1f5f9; color: #334155; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 12px; }
        .badge-lock { background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 12px; }

        .btn-link { color: var(--primary); text-decoration: none; font-weight: 600; }
        .btn-link:hover { text-decoration: underline; }

        .btn-edit {
            color: var(--warning);
            text-decoration: none;
            font-weight: 600;
            margin-left: 10px;
            padding: 4px 8px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            background: transparent;
            font-size: 14px;
        }
        .btn-edit:hover { background-color: #fef3c7; }

        .btn-delete {
            color: var(--danger);
            text-decoration: none;
            font-weight: 600;
            margin-left: 10px;
            padding: 4px 8px;
            border-radius: 6px;
            transition: background-color 0.2s;
        }
        .btn-delete:hover { background-color: #fee2e2; }

        /* =========================================================
           CSS CHO MODAL POPUP CHỈNH SỬA
           ========================================================= */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-card {
            background: #ffffff;
            width: 90%;
            max-width: 600px;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            transform: translateY(20px);
            transition: transform 0.25s ease;
        }

        .modal-overlay.active .modal-card {
            transform: translateY(0);
        }

        .modal-header {
            padding: 20px 24px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 { font-size: 18px; color: var(--text); }

        .modal-close {
            background: none;
            border: none;
            font-size: 22px;
            color: var(--muted);
            cursor: pointer;
        }

        .modal-body {
            padding: 24px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 700; font-size: 13px; color: #334155; margin-bottom: 6px; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .modal-footer {
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-primary:hover { background: var(--primary-dark); }
    </style>
</head>
<body>

    <!-- THANH ĐIỀU HƯỚNG (NAVBAR) -->
    <nav class="navbar">
        <a href="index.php" class="brand">🏛️ TestSuViet Teacher</a>
        <div class="user-menu">
            <span class="user-badge">👨‍🏫 <?= htmlspecialchars($teacher_name) ?></span>
            <a href="profile.php" class="btn-link">Hồ sơ</a>
            <a href="login.php" class="back_home">Đăng xuất</a>
        </div>
    </nav>

    <div class="container">

        <!-- THÔNG BÁO TẠO/SỬA/XÓA (FLASH MESSAGE) -->
        <?php if (!empty($flash_message)): ?>
            <div class="alert alert-<?= $flash_type === 'success' ? 'success' : 'error' ?>">
                <?= $flash_type === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($flash_message) ?>
            </div>
        <?php endif; ?>

        <!-- LỜI CHÀO -->
        <header class="welcome-header">
            <h1>Xin chào, Thầy/Cô <?= htmlspecialchars($teacher_name) ?>! 👋</h1>
            <p>Bảng điều khiển quản lý đề thi Lịch sử, ngân hàng câu hỏi và xem kết quả học sinh.</p>
        </header>

        <!-- LỐI TẮC CHỨC NĂNG CHÍNH -->
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

        <!-- THỐNG KÊ NHANH -->
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
                <div class="stat-value"><?= $avg_score ?> <small style="font-size: 16px;">/10</small></div>
            </div>
        </div>

        <!-- DANH SÁCH ĐỀ THI GẦN ĐÂY -->
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
                                    <td style="font-size: 12px; color: var(--muted);">
                                        📅 Mở: <?= !empty($exam['start_time']) ? date('d/m/Y H:i', strtotime($exam['start_time'])) : 'Ngay lập tức' ?><br>
                                        🛑 Đóng: <?= !empty($exam['end_time']) ? date('d/m/Y H:i', strtotime($exam['end_time'])) : 'Không giới hạn' ?>
                                    </td>
                                    <td>
                                        <?= $exam['max_attempts'] > 0 ? $exam['max_attempts'] . ' lần' : 'Tự do' ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($exam['exam_password'])): ?>
                                            <span class="badge-lock">🔒 Mật khẩu</span>
                                        <?php else: ?>
                                            <span style="color: var(--muted);">Công khai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= $exam['total_attempts'] ?></strong> lượt</td>
                                    <td style="white-space: nowrap;">
                                        <a href="teacher_view_result.php?exam_id=<?= $exam['exam_id'] ?>" class="btn-link" title="Xem kết quả">
                                            📊 Kết quả
                                        </a>

                                        <!-- Nút Mở Modal Sửa -->
                                        <button type="button" 
                                                class="btn-edit"
                                                onclick='openEditModal(<?= json_encode($exam, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            ✏️ Sửa
                                        </button>

                                        <!-- Nút Xóa Đề Thi -->
                                        <a href="?action=delete&exam_id=<?= $exam['exam_id'] ?>" 
                                           class="btn-delete"
                                           onclick="return confirm('⚠️ Bạn có chắc chắn muốn xóa đề thi: «<?= htmlspecialchars(addslashes($exam['title'])) ?>»?\n\nToàn bộ lượt làm bài của học sinh liên quan cũng sẽ bị xóa!');">
                                            🗑️ Xóa
                                        </a>
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

    <!-- =========================================================
         MODAL POPUP CHỈNH SỬA ĐỀ THI
         ========================================================= -->
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

    <!-- SCRIPT ĐIỀU KHIỂN MODAL POPUP -->
    <script>
        const editModal = document.getElementById('editModal');

        function openEditModal(exam) {
            document.getElementById('modalExamId').textContent = '#' + exam.exam_id;
            document.getElementById('edit_exam_id').value = exam.exam_id;
            document.getElementById('edit_title').value = exam.title;
            document.getElementById('edit_duration').value = exam.duration;
            document.getElementById('edit_max_attempts').value = exam.max_attempts;
            document.getElementById('edit_password').value = exam.exam_password || '';

            // Định dạng thời gian YYYY-MM-DDTHH:MM cho input datetime-local
            document.getElementById('edit_start_time').value = exam.start_time ? formatDateTimeLocal(exam.start_time) : '';
            document.getElementById('edit_end_time').value = exam.end_time ? formatDateTimeLocal(exam.end_time) : '';

            editModal.classList.add('active');
        }

        function closeEditModal() {
            editModal.classList.remove('active');
        }

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

        // Đóng modal khi click ra bên ngoài
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) closeEditModal();
        });

        // Validation kiểm tra thời gian đóng > mở
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const startVal = document.getElementById('edit_start_time').value;
            const endVal = document.getElementById('edit_end_time').value;

            if (startVal && endVal) {
                if (new Date(endVal) <= new Date(startVal)) {
                    e.preventDefault();
                    alert('⚠️ Thời gian đóng đề thi phải sau thời gian mở đề thi!');
                }
            }
        });
    </script>

</body>
</html>