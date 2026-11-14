<?php
require_once __DIR__ . '/../api/be_teacher_exam.php';
include __DIR__ . '/../includes/teacher_sidebar.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo đề thi | TestSuViet</title>
    <link rel="stylesheet" href="/../assets/css/UI_teacher.css">
</head>
<body>
<div class="et-overlay" id="etOverlay" onclick="etToggleSidebar()"></div>
  <div class="et-workspace">
    <header class="et-topbar">
      <div class="et-topbar-left">
        <button type="button" class="et-mobile-toggle" onclick="etToggleSidebar()" aria-label="Mở menu">☰</button>
        <div><div class="et-crumb">TEACHER / GIÁO VIÊN</div><div class="et-top-title">Tạo đề thi</div></div>
      </div>
      <div class="et-user"><span class="et-avatar">GV</span><span class="et-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Giáo viên', ENT_QUOTES, 'UTF-8') ?></span></div>
    </header>
    <main class="et-content">
<div class="container">
    <header class="page-header">
        <div class="page-title">
            <div class="title-icon">📝</div>
            <div>
                <h2>Tạo đề thi mới</h2>
                <p class="page-description">Thiết lập đề thi, thời gian mở/đóng đề, lượt làm, mật khẩu bảo vệ và cấu trúc câu hỏi.</p>
            </div>
        </div>
    </header>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="examForm">
        <input type="hidden" name="action" value="create_exam">

        <section class="card">
            <div class="section-heading">
                <div class="step-number">01</div>
                <div>
                    <h2>Thông tin chung</h2>
                    <p class="section-description">Tên đề thi, khối lớp, thời gian làm bài, khung giờ mở/đóng đề và mật khẩu truy cập.</p>
                </div>
            </div>

            <div class="form-group">
                <label for="title">Tên đề thi</label>
                <input type="text" id="title" name="title" placeholder="Ví dụ: Đề thi Ôn tập Chương 1 - Lịch sử 9" required>
            </div>

            <div class="form-grid-3">
                <div class="form-group">
                    <label for="gradeSelect">Khối lớp</label>
                    <select name="grade" id="gradeSelect">
                        <?php for ($g = 6; $g <= 9; $g++): ?>
                            <option value="<?= $g ?>" <?= $g === 9 ? 'selected' : '' ?>>Lớp <?= $g ?> — <?= $questionCounts[$g] ?> câu</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="duration">Thời gian làm bài (phút)</label>
                    <input type="number" id="duration" name="duration" value="15" min="1" max="300" required>
                </div>

                <div class="form-group">
                    <label for="maxAttempts">Số lần làm tối đa</label>
                    <select name="max_attempts" id="maxAttempts">
                        <option value="0">Không giới hạn</option>
                        <option value="1">1 lần</option>
                        <option value="2">2 lần</option>
                        <option value="3">3 lần</option>
                        <option value="5">5 lần</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="startTime">Thời gian mở đề thi</label>
                    <input type="datetime-local" id="startTime" name="start_time">
                    <small style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">Để trống nếu muốn mở đề ngay lập tức.</small>
                </div>

                <div class="form-group">
                    <label for="endTime">Thời gian đóng đề thi</label>
                    <input type="datetime-local" id="endTime" name="end_time">
                    <small style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">Quá thời gian này học sinh không thể vào thi.</small>
                </div>

                <div class="form-group">
                    <label for="examPassword">Mật khẩu đề thi</label>
                    <input type="text" id="examPassword" name="exam_password" placeholder="Để trống nếu tự do" maxlength="50">
                </div>
            </div>
        </section>

        <section class="card">
            <div class="section-heading">
                <div class="step-number">02</div>
                <div>
                    <h2>Phương thức chọn câu hỏi</h2>
                    <p class="section-description">Chọn thủ công từng câu hoặc cấu hình bốc ngẫu nhiên theo chủ đề & mức độ.</p>
                </div>
            </div>

            <div class="mode-box">
                <div class="mode-option">
                    <input type="radio" id="modeManual" name="create_mode" value="manual" checked>
                    <label for="modeManual">
                        <strong>Chọn thủ công</strong>
                        <small>Tự chọn từng câu hỏi đưa vào đề thi.</small>
                    </label>
                </div>

                <div class="mode-option">
                    <input type="radio" id="modeAuto" name="create_mode" value="auto">
                    <label for="modeAuto">
                        <strong>Bốc câu hỏi ngẫu nhiên</strong>
                        <small>Bốc theo chủ đề và cấu hình 3 mức độ.</small>
                    </label>
                </div>
            </div>

            <div id="manualSection">
                <div class="question-toolbar">
                    <span class="question-count">Đã chọn <span id="selectedCount">0</span> câu</span>
                    <input type="text" id="questionSearch" style="max-width: 300px;" placeholder="🔎 Tìm nội dung câu hỏi...">
                </div>

                <div class="q-list">
                    <?php foreach ($questionsBank as $q): ?>
                        <?php
                            $diffClass = 'badge-easy';
                            $diffText = 'Nhận biết';
                            $diffUpper = strtoupper($q['difficulty'] ?? 'EASY');
                            if ($diffUpper === 'MEDIUM') { $diffClass = 'badge-medium'; $diffText = 'Thông hiểu'; }
                            if ($diffUpper === 'HARD') { $diffClass = 'badge-hard'; $diffText = 'Vận dụng'; }
                        ?>
                        <div class="q-item question-row" data-grade="<?= (int)$q['grade'] ?>" data-search="<?= htmlspecialchars(mb_strtolower($q['content'] . ' ' . $q['topic_name'], 'UTF-8')) ?>">
                            <input type="checkbox" name="question_ids[]" value="<?= (int)$q['question_id'] ?>" id="q_<?= (int)$q['question_id'] ?>" class="question-checkbox">
                            <label for="q_<?= (int)$q['question_id'] ?>">
                                <span class="q-badge <?= $diffClass ?>"><?= $diffText ?></span>
                                <strong>[<?= htmlspecialchars($q['topic_name']) ?>]</strong>
                                <?= htmlspecialchars($q['content']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="autoSection" hidden>
                <div class="auto-panel">
                    <div class="form-group">
                        <label for="autoTopic">Chọn Chủ đề bài học</label>
                        <select name="auto_topic_id" id="autoTopic" disabled>
                            <option value="0">-- Tất cả các chủ đề (Toàn bộ chương trình lớp) --</option>
                            <?php foreach ($allTopics as $t): ?>
                                <option value="<?= (int)$t['topic_id'] ?>" data-grade="<?= (int)$t['grade'] ?>">
                                    Lớp <?= (int)$t['grade'] ?> - <?= htmlspecialchars($t['topic_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <label style="margin-top: 15px;">Cấu hình số lượng câu hỏi theo 3 mức độ:</label>
                    <div class="three-columns" style="margin-top: 10px;">
                        <div class="form-group">
                            <label style="font-weight: 600; color: #166534;">🟢 Nhận biết (EASY) <span class="avail-badge" id="availEasy">Hiện có: 0</span></label>
                            <input type="number" class="auto-input" id="countEasy" name="count_easy" value="5" min="0" max="50" disabled>
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 600; color: #92400e;">🟡 Thông hiểu (MEDIUM) <span class="avail-badge" id="availMedium">Hiện có: 0</span></label>
                            <input type="number" class="auto-input" id="countMedium" name="count_medium" value="3" min="0" max="50" disabled>
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 600; color: #991b1b;">🔴 Vận dụng (HARD) <span class="avail-badge" id="availHard">Hiện có: 0</span></label>
                            <input type="number" class="auto-input" id="countHard" name="count_hard" value="2" min="0" max="50" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button type="submit" class="btn btn-submit">Lưu đề thi</button>
            </div>
        </section>
    </form>
</div>

<script>
    const questionsData = <?= json_encode($jsQuestionsData, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const gradeSelect = document.getElementById('gradeSelect');
    const modeManual = document.getElementById('modeManual');
    const modeAuto = document.getElementById('modeAuto');
    const manualSection = document.getElementById('manualSection');
    const autoSection = document.getElementById('autoSection');
    const autoTopic = document.getElementById('autoTopic');
    const autoInputs = document.querySelectorAll('.auto-input');
    const questionSearch = document.getElementById('questionSearch');
    const selectedCount = document.getElementById('selectedCount');
    const startTimeInput = document.getElementById('startTime');
    const endTimeInput = document.getElementById('endTime');
    const availEasy = document.getElementById('availEasy');
    const availMedium = document.getElementById('availMedium');
    const availHard = document.getElementById('availHard');
    const countEasyInput = document.getElementById('countEasy');
    const countMediumInput = document.getElementById('countMedium');
    const countHardInput = document.getElementById('countHard');

    function updateAvailableCounts() {
        const selectedGrade = parseInt(gradeSelect.value, 10);
        const selectedTopicId = parseInt(autoTopic.value, 10);
        let easyCount = 0, mediumCount = 0, hardCount = 0;

        questionsData.forEach(q => {
            if (q.g === selectedGrade && (selectedTopicId === 0 || q.t === selectedTopicId)) {
                if (q.d === 'EASY') easyCount++;
                else if (q.d === 'MEDIUM') mediumCount++;
                else if (q.d === 'HARD') hardCount++;
            }
        });

        availEasy.textContent = `Hiện có: ${easyCount}`;
        availMedium.textContent = `Hiện có: ${mediumCount}`;
        availHard.textContent = `Hiện có: ${hardCount}`;

        countEasyInput.max = easyCount;
        countMediumInput.max = mediumCount;
        countHardInput.max = hardCount;
    }

    function toggleMode() {
        const isManual = modeManual.checked;
        manualSection.hidden = !isManual;
        autoSection.hidden = isManual;

        document.querySelectorAll('.question-checkbox').forEach(cb => cb.disabled = !isManual);
        autoTopic.disabled = isManual;
        autoInputs.forEach(input => input.disabled = isManual);

        updateQuestionList();
        updateTopicOptions();
        updateSelectedCount();
        updateAvailableCounts();
    }

    function updateQuestionList() {
        const grade = gradeSelect.value;
        const kw = questionSearch.value.trim().toLowerCase();
        document.querySelectorAll('.question-row').forEach(row => {
            const sameGrade = row.dataset.grade === grade;
            const matchSearch = row.dataset.search.includes(kw);
            row.hidden = !(sameGrade && matchSearch);
        });
    }

    function updateTopicOptions() {
        const grade = gradeSelect.value;
        Array.from(autoTopic.options).forEach(opt => {
            if (opt.value === "0") return;
            opt.hidden = opt.dataset.grade !== grade;
        });
        autoTopic.value = "0";
    }

    function updateSelectedCount() {
        const count = document.querySelectorAll('.question-checkbox:checked:not(:disabled)').length;
        selectedCount.textContent = count;
    }

    gradeSelect.addEventListener('change', () => { updateQuestionList(); updateTopicOptions(); updateAvailableCounts(); });
    autoTopic.addEventListener('change', updateAvailableCounts);
    questionSearch.addEventListener('input', updateQuestionList);
    modeManual.addEventListener('change', toggleMode);
    modeAuto.addEventListener('change', toggleMode);
    document.querySelectorAll('.question-checkbox').forEach(cb => cb.addEventListener('change', updateSelectedCount));

    document.getElementById('examForm').addEventListener('submit', function(e) {
        if (modeManual.checked && selectedCount.textContent === '0') {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất 1 câu hỏi.');
            return;
        }
        if (startTimeInput.value && endTimeInput.value) {
            if (new Date(endTimeInput.value) <= new Date(startTimeInput.value)) {
                e.preventDefault();
                alert('⚠️ Thời gian đóng đề thi phải sau thời gian mở đề thi!');
                endTimeInput.focus();
            }
        }
    });

    toggleMode();
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