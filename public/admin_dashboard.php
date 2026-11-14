<?php
require_once __DIR__ . '/../api/be_admin_dashboard.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản trị hệ thống</title>
    <link rel="stylesheet" href="/../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Đổi màu nền trang */
        :root {
            --bg-page: #e8eef7;
        }
        body {
            background: linear-gradient(135deg, #e8eef7 0%, #dde6f3 100%) !important;
            min-height: 100vh;
        }

        /* Bố cục khu vực sơ đồ / biểu đồ */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .chart-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .chart-card h2 {
            font-size: 1.05rem;
            margin: 0 0 14px 0;
            color: #1e293b;
        }
        .chart-box {
            position: relative;
            height: 260px;
            width: 100%;
        }
    </style>
</head>
<body>
<div class="container">

    <div class="topbar">
        <h1>Quản trị hệ thống</h1>
        <div class="topbar-user">
            <span>Xin chào, <strong><?= h($current_admin_name) ?></strong></span>
            <a class="btn-logout" href="../api/auth.php?action=logout">Đăng xuất</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
            <?= h($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- THỐNG KÊ -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-label">Học viên</div>
            <div class="stat-number"><?= number_format($total_students) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Giáo viên</div>
            <div class="stat-number"><?= number_format($total_teachers) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Đề thi</div>
            <div class="stat-number" style="color: var(--success);"><?= number_format($total_exams) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Lượt làm bài</div>
            <div class="stat-number" style="color: var(--warn);"><?= number_format($total_attempts) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Chờ duyệt</div>
            <div class="stat-number" style="color: var(--primary);"><?= number_format($total_pending) ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Tài khoản bị khóa</div>
            <div class="stat-number" style="color: var(--danger);"><?= number_format($total_locked) ?></div>
        </div>
    </div>

    <!-- SƠ ĐỒ TRỰC QUAN -->
    <div class="charts-grid">
        <div class="chart-card">
            <h2>Sơ đồ trạng thái tài khoản</h2>
            <div class="chart-box">
                <canvas id="userStatusChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h2>Sơ đồ đề thi theo khối lớp</h2>
            <div class="chart-box">
                <canvas id="examGradeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- KHỐI 1: QUẢN LÝ TÀI KHOẢN -->
    <div class="card">
        <h2>Quản lý tài khoản</h2>

        <form class="filters" method="GET" action="admin_dashboard.php">
            <input type="text" name="q" value="<?= h($search) ?>" placeholder="Tìm theo tên, username hoặc email">
            <select name="role">
                <option value="">Tất cả vai trò</option>
                <?php foreach ($role_labels as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= $filter_role === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="ACTIVE" <?= $filter_status === 'ACTIVE' ? 'selected' : '' ?>>Hoạt động</option>
                <option value="PENDING" <?= $filter_status === 'PENDING' ? 'selected' : '' ?>>Chờ duyệt</option>
                <option value="LOCKED" <?= $filter_status === 'LOCKED' ? 'selected' : '' ?>>Đã khóa</option>
            </select>
            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            <?php if ($search !== '' || $filter_role !== '' || $filter_status !== ''): ?>
                <a href="admin_dashboard.php" class="btn btn-ghost">Xóa bộ lọc</a>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ và tên</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users): ?>
                        <tr><td class="empty" colspan="7">Không có tài khoản nào khớp với bộ lọc.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($users as $u): ?>
                        <?php
                            $is_self  = (int)$u['user_id'] === $current_admin_id;
                            $is_admin = $u['role'] === 'ADMIN';
                        ?>
                        <tr>
                            <td><?= (int)$u['user_id'] ?></td>
                            <td><strong><?= h($u['full_name']) ?></strong><?= $is_self ? ' <span class="muted">(bạn)</span>' : '' ?></td>
                            <td><?= h($u['username']) ?></td>
                            <td><?= h($u['email']) ?></td>
                            <td><?= h($role_labels[$u['role']] ?? $u['role']) ?></td>
                            <td>
                                <?php if ($u['status'] === 'ACTIVE'): ?>
                                    <span class="badge badge-active">Hoạt động</span>
                                <?php elseif ($u['status'] === 'PENDING'): ?>
                                    <span class="badge badge-pending">Chờ duyệt</span>
                                <?php else: ?>
                                    <span class="badge badge-locked">Đã khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_self || $is_admin): ?>
                                    <span class="muted">Không khả dụng</span>
                                <?php else: ?>
                                    <form method="POST" action="admin_dashboard.php">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <input type="hidden" name="return_query" value="<?= h($return_query) ?>">
                                        <?php if ($u['status'] === 'PENDING'): ?>
                                            <input type="hidden" name="new_status" value="ACTIVE">
                                            <button type="submit" class="btn btn-unlock"
                                                    onclick="return confirm('Duyệt tài khoản <?= h($u['username']) ?>?')">Duyệt</button>
                                        <?php elseif ($u['status'] === 'ACTIVE'): ?>
                                            <input type="hidden" name="new_status" value="LOCKED">
                                            <button type="submit" class="btn btn-lock"
                                                    onclick="return confirm('Khóa tài khoản <?= h($u['username']) ?>?')">Khóa</button>
                                        <?php else: ?>
                                            <input type="hidden" name="new_status" value="ACTIVE">
                                            <button type="submit" class="btn btn-unlock"
                                                    onclick="return confirm('Mở khóa tài khoản <?= h($u['username']) ?>?')">Mở khóa</button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <div>
                <?php if ($total_rows > 0): ?>
                    Hiển thị <?= $offset + 1 ?>–<?= $offset + count($users) ?> trong tổng số <?= number_format($total_rows) ?> tài khoản
                <?php else: ?>
                    Không có dữ liệu
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pager">
                    <?php if ($page > 1): ?>
                        <a href="<?= h(page_url($page - 1, $search, $filter_role, $filter_status)) ?>">Trước</a>
                    <?php else: ?>
                        <span class="disabled">Trước</span>
                    <?php endif; ?>

                    <?php
                        $start = max(1, $page - 2);
                        $end   = min($total_pages, $page + 2);
                        for ($p = $start; $p <= $end; $p++):
                    ?>
                        <?php if ($p === $page): ?>
                            <span class="current"><?= $p ?></span>
                        <?php else: ?>
                            <a href="<?= h(page_url($p, $search, $filter_role, $filter_status)) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?= h(page_url($page + 1, $search, $filter_role, $filter_status)) ?>">Sau</a>
                    <?php else: ?>
                        <span class="disabled">Sau</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- KHỐI 2: DANH SÁCH ĐỀ THI KÈM NGƯỜI TẠO -->
    <div class="card">
        <h2>Danh sách đề thi gần đây</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mã đề</th>
                        <th>Tên đề thi</th>
                        <th>Khối lớp</th>
                        <th>Thời lượng</th>
                        <th>Số câu</th>
                        <th>Người tạo (Giáo viên)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exams_list)): ?>
                        <tr><td class="empty" colspan="6">Chưa có đề thi nào trong hệ thống.</td></tr>
                    <?php else: ?>
                        <?php foreach ($exams_list as $ex): ?>
                            <tr>
                                <td>#<?= (int)$ex['exam_id'] ?></td>
                                <td><strong><?= h($ex['title']) ?></strong></td>
                                <td>Lớp <?= (int)$ex['grade'] ?></td>
                                <td><?= (int)$ex['duration'] ?> phút</td>
                                <td><?= (int)$ex['total_questions'] ?> câu</td>
                                <td>
                                    <?php if (!empty($ex['teacher_name'])): ?>
                                        <span style="color: var(--primary); font-weight: 600;"> <?= h($ex['teacher_name']) ?></span>
                                        <span class="muted">(<?= h($ex['teacher_username']) ?>)</span>
                                    <?php else: ?>
                                        <span class="muted">Hệ thống / Chưa cập nhật</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Biểu đồ tròn: Trạng thái tài khoản
    const statusCtx = document.getElementById('userStatusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Hoạt động', 'Chờ duyệt', 'Đã khóa'],
                datasets: [{
                    data: [<?= (int)$total_active ?>, <?= (int)$total_pending ?>, <?= (int)$total_locked ?>],
                    backgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 16, usePointStyle: true }
                    }
                }
            }
        });
    }

    // 2. Biểu đồ cột: Số lượng đề thi theo khối lớp
    const gradeLabels = <?= json_encode($grade_labels ?: ['Chưa có dữ liệu'], JSON_UNESCAPED_UNICODE) ?>;
    const gradeCounts = <?= json_encode($grade_counts ?: [0]) ?>;

    const gradeCtx = document.getElementById('examGradeChart');
    if (gradeCtx) {
        new Chart(gradeCtx, {
            type: 'bar',
            data: {
                labels: gradeLabels,
                datasets: [{
                    label: 'Số lượng đề thi',
                    data: gradeCounts,
                    backgroundColor: '#6366f1',
                    borderRadius: 6,
                    maxBarThickness: 44
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>
</body>
</html>