<?php
session_start();
require_once __DIR__ . '/../config/database.php';

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
| THỐNG KÊ TỔNG QUAN HỆ THỐNG
|--------------------------------------------------------------------------
*/
// 1. Tổng số đề thi đã tạo
$stmt = $conn->query("SELECT COUNT(*) FROM exam");
$total_exams = (int)$stmt->fetchColumn();

// 2. Tổng số câu hỏi trong Ngân hàng
$stmt = $conn->query("SELECT COUNT(*) FROM questions");
$total_questions = (int)$stmt->fetchColumn();

// 3. Tổng số lượt học sinh đã làm bài
$stmt = $conn->query("SELECT COUNT(*) FROM exam_results");
$total_submissions = (int)$stmt->fetchColumn();

// 4. Điểm trung bình của tất cả lượt thi
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

        .welcome-header {
            margin-bottom: 30px;
        }

        .welcome-header h1 {
            font-size: 28px;
            color: var(--text);
        }

        .welcome-header p {
            color: var(--muted);
            font-size: 15px;
        }

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

        .action-info h3 {
            font-size: 17px;
            color: var(--text);
            margin-bottom: 4px;
        }

        .action-info p {
            font-size: 13px;
            color: var(--muted);
        }

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

        .table-header h2 {
            font-size: 18px;
            color: var(--text);
        }

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

        tr:hover {
            background-color: #f8fafc;
        }

        .badge-grade {
            background: #f1f5f9;
            color: #334155;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
        }

        .badge-lock {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
        }

        .btn-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .btn-link:hover {
            text-decoration: underline;
        }
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
                            <th>Số Lần Tối Đa</th>
                            <th>Bảo Vệ</th>
                            <th>Lượt Làm Bài</th>
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
                                    <td>
                                        <a href="teacher_view_result.php?exam_id=<?= $exam['exam_id'] ?>" class="btn-link">
                                            Xem kết quả ➔
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--muted); padding: 30px;">
                                    Chưa có đề thi nào được tạo. Hãy nhấn "Tạo đề thi mới" để bắt đầu!
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