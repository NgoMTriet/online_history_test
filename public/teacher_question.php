<?php
require_once __DIR__ . '/../api/be_teacher_question.php';
include __DIR__ . '/../includes/teacher_sidebar.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý ngân hàng câu hỏi | TestSuViet</title>
    <link rel="stylesheet" href="/../assets/css/UI_teacher.css">
    <script>
        function onGradeChange(select) {
            window.location.href = '?grade=' + encodeURIComponent(select.value);
        }
        function onTopicSelect(select) {
            const grade = document.getElementById('grade_select').value;
            window.location.href = '?grade=' + encodeURIComponent(grade) + '&topic_id=' + encodeURIComponent(select.value);
        }
    </script>
</head>
<body>
<div class="et-overlay" id="etOverlay" onclick="etToggleSidebar()"></div>
  <div class="et-workspace">
    <header class="et-topbar">
      <div class="et-topbar-left">
        <button type="button" class="et-mobile-toggle" onclick="etToggleSidebar()" aria-label="Mở menu">☰</button>
        <div><div class="et-crumb">TEACHER / GIÁO VIÊN</div><div class="et-top-title">Ngân hàng câu hỏi</div></div>
      </div>
      <div class="et-user"><span class="et-avatar">GV</span><span class="et-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Giáo viên', ENT_QUOTES, 'UTF-8') ?></span></div>
    </header>
    <main class="et-content">
<div class="container">
    <header class="header-bar">
        <div class="header-title">
            <div class="header-icon">📚</div>
            <div>
                <h1>Ngân hàng câu hỏi</h1>
                <p class="subtitle">Quản lý chủ đề, nhập câu hỏi và đáp án môn Lịch sử THCS</p>
            </div>
        </div>
    </header>

    <?= $message ?>

    <section class="card">
        <div class="card-heading">
            <div class="step-number">01</div>
            <div>
                <h2>Chọn hoặc tạo chủ đề</h2>
                <p class="section-description">Chọn khối lớp trước khi nhập câu hỏi vào ngân hàng.</p>
            </div>
        </div>

        <div class="grid-2">
            <div>
                <div class="form-group">
                    <label for="grade_select">Khối lớp</label>
                    <select id="grade_select" onchange="onGradeChange(this)">
                        <?php for ($grade = 6; $grade <= 9; $grade++): ?>
                            <option value="<?= $grade ?>" <?= $selected_grade === $grade ? 'selected' : '' ?>>Lớp <?= $grade ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="topic_select">Chủ đề hiện có</label>
                    <select id="topic_select" onchange="onTopicSelect(this)">
                        <option value="0">-- Chọn chủ đề --</option>
                        <?php foreach ($topics_list as $topic): ?>
                            <option value="<?= (int)$topic['topic_id'] ?>" <?= $selected_topic_id === (int)$topic['topic_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($topic['topic_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="topic-create">
                <form method="POST">
                    <input type="hidden" name="action" value="create_topic">
                    <input type="hidden" name="grade" value="<?= $selected_grade ?>">
                    <?php if ($selected_topic_id > 0): ?>
                        <input type="hidden" name="topic_id" value="<?= $selected_topic_id ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="new_topic_name">Tạo chủ đề cho lớp <?= $selected_grade ?></label>
                        <input type="text" id="new_topic_name" name="new_topic_name" placeholder="Ví dụ: Việt Nam từ 1930 đến 1945" maxlength="255" required>
                    </div>
                    <button type="submit" class="btn btn-warning">＋ Tạo chủ đề mới</button>
                </form>
            </div>
        </div>
    </section>

    <?php if ($current_topic): ?>
        <div class="alert alert-info">
            <div>Đang làm việc với chủ đề <strong><?= htmlspecialchars($current_topic['topic_name'], ENT_QUOTES, 'UTF-8') ?></strong> — Lớp <?= (int)$current_topic['grade'] ?></div>
        </div>

        <section class="card">
            <div class="card-heading">
                <div class="step-number">02A</div>
                <div>
                    <h2>Import câu hỏi từ Excel</h2>
                    <p class="section-description">Thêm nhiều câu hỏi cùng lúc vào chủ đề đang chọn.</p>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_excel">
                <input type="hidden" name="grade" value="<?= $selected_grade ?>">
                <input type="hidden" name="topic_id" value="<?= $selected_topic_id ?>">

                <div class="form-group">
                    <label for="excel_file">Chọn file Excel</label>
                    <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                </div>

                <div class="file-hint">
                    <strong>Cấu trúc file Excel:</strong><br><br>
                    <span class="option-label">Cột A</span> Nội dung câu hỏi<br>
                    <span class="option-label">Cột B–E</span> Đáp án A, B, C, D<br>
                    <span class="option-label">Cột F</span> Đáp án đúng: A, B, C hoặc D<br>
                    <span class="option-label">Cột G</span> Độ khó: EASY, MEDIUM hoặc HARD<br>
                    <span class="option-label">Cột H</span> Lời giải chi tiết
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-success">📥 Import câu hỏi</button>
                </div>
            </form>
        </section>

        <section class="card">
            <div class="card-heading">
                <div class="step-number">02B</div>
                <div>
                    <h2>Thêm câu hỏi thủ công</h2>
                    <p class="section-description">Nhập từng câu hỏi, lựa chọn đáp án đúng và lời giải.</p>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="add_manual">
                <input type="hidden" name="grade" value="<?= $selected_grade ?>">
                <input type="hidden" name="topic_id" value="<?= $selected_topic_id ?>">

                <div class="form-group">
                    <label for="question">Nội dung câu hỏi</label>
                    <textarea id="question" name="question" rows="3" placeholder="Nhập nội dung câu hỏi..." required></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="option_a">Đáp án A</label>
                        <input type="text" id="option_a" name="option_a" placeholder="Nhập đáp án A" required>
                    </div>
                    <div class="form-group">
                        <label for="option_b">Đáp án B</label>
                        <input type="text" id="option_b" name="option_b" placeholder="Nhập đáp án B" required>
                    </div>
                    <div class="form-group">
                        <label for="option_c">Đáp án C</label>
                        <input type="text" id="option_c" name="option_c" placeholder="Nhập đáp án C">
                    </div>
                    <div class="form-group">
                        <label for="option_d">Đáp án D</label>
                        <input type="text" id="option_d" name="option_d" placeholder="Nhập đáp án D">
                    </div>
                </div>

                <div class="form-group">
                    <label for="correct_option">Đáp án đúng</label>
                    <select id="correct_option" name="correct_option" style="max-width: 240px;" required>
                        <option value="A">Đáp án A</option>
                        <option value="B">Đáp án B</option>
                        <option value="C">Đáp án C</option>
                        <option value="D">Đáp án D</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="difficulty">Độ khó câu hỏi</label>
                    <select id="difficulty" name="difficulty" style="max-width: 240px;" required>
                        <option value="EASY">EASY - Dễ</option>
                        <option value="MEDIUM">MEDIUM - Trung bình</option>
                        <option value="HARD">HARD - Khó</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="explanation">Lời giải chi tiết</label>
                    <textarea id="explanation" name="explanation" rows="3" placeholder="Giải thích vì sao đáp án được chọn là đúng..."></textarea>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">💾 Lưu câu hỏi</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <div class="alert alert-warning">⚠️ Hãy chọn hoặc tạo chủ đề ở Bước 1 trước khi thêm câu hỏi.</div>
    <?php endif; ?>

    <section class="card">
        <div class="card-heading">
            <div class="step-number">03</div>
            <div>
                <h2>Câu hỏi mới thêm gần đây</h2>
                <p class="section-description">Hiển thị tối đa 50 câu hỏi mới nhất trong hệ thống.</p>
            </div>
        </div>

        <?php if (!empty($questions_list)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khối lớp</th>
                            <th>Chủ đề</th>
                            <th>Nội dung câu hỏi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions_list as $q): ?>
                            <tr>
                                <td><?= (int)$q['question_id'] ?></td>
                                <td><span class="badge">Lớp <?= (int)$q['grade'] ?></span></td>
                                <td><span class="badge-topic"><?= htmlspecialchars($q['topic_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="question-content"><?= htmlspecialchars($q['content'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">Chưa có câu hỏi nào trong ngân hàng câu hỏi.</div>
        <?php endif; ?>
    </section>
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