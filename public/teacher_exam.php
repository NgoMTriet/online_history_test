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
                <h1>Tạo đề thi mới</h1>
                <p class="page-description">
                    Thiết lập đề thi, thời gian mở đề, lượt làm, mật khẩu bảo vệ và cấu trúc câu hỏi.
                </p>
            </div>
        </div>

        <a href="../index.php" class="btn btn-secondary">🏠 Trang chủ</a>
    </header>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="examForm">
        <input type="hidden" name="action" value="create_exam">

        <!-- PHẦN 1: THÔNG TIN CHUNG -->
        <section class="card">
            <div class="section-heading">
                <div class="step-number">01</div>
                <div>
                    <h2>Thông tin chung</h2>
                    <p class="section-description">Tên đề thi, khối lớp, kỳ thi, thời gian làm bài, thời gian mở đề và mật khẩu truy cập.</p>
                </div>
            </div>

            <div class="form-group">
                <label for="title">Tên đề thi</label>
                <input type="text" id="title" name="title" placeholder="Ví dụ: Đề ôn tập Giữa kì 1 - Lịch sử 6" required>
            </div>

            <div class="form-grid-3">
                <div class="form-group">
                    <label for="gradeSelect">Khối lớp</label>
                    <select name="grade" id="gradeSelect">
                        <?php for ($g = 6; $g <= 9; $g++): ?>
                            <option value="<?= $g ?>" <?= $g === 9 ? 'selected' : '' ?>>
                                Lớp <?= $g ?> — <?= $questionCounts[$g] ?> câu
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subjectSelect">Môn học</label>
                    <select name="subject_id" id="subjectSelect" required>
                        <?php foreach ($allSubjects as $subj): ?>
                            <option value="<?= (int)$subj['subject_id'] ?>" data-grade="<?= (int)$subj['grade'] ?>">
                                <?= htmlspecialchars($subj['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ô CHỌN LOẠI ĐỀ / KỲ THI -->
                <div class="form-group">
                    <label for="examTypeSelect">Loại đề / Kỳ thi</label>
                    <select name="exam_type" id="examTypeSelect" required>
                        <?php foreach ($examTypesMap as $typeKey => $typeLabel): ?>
                            <option value="<?= htmlspecialchars($typeKey) ?>">
                                <?= htmlspecialchars($typeLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ⏱️ Ô CHỌN THỜI GIAN MỞ ĐỀ THI -->
                <div class="form-group">
                    <label for="startTime">📅 Thời gian mở đề thi</label>
                    <input type="datetime-local" id="startTime" name="start_time">
                    <small style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">Để trống nếu muốn mở đề ngay lập tức.</small>
                </div>

                <div class="form-group">
                    <label for="duration">Thời gian làm bài (phút)</label>
                    <input type="number" id="duration" name="duration" value="15" min="1" max="300" required>
                </div>

                <!-- Ô NHẬP MẬT KHẨU ĐỀ THI -->
                <div class="form-group">
                    <label for="examPassword">🔑 Mật khẩu đề thi</label>
                    <input type="text" id="examPassword" name="exam_password" placeholder="Để trống nếu tự do" maxlength="50">
                </div>
            </div>
        </section>

        <!-- PHẦN 2: PHƯƠNG THỨC CHỌN CÂU HỎI -->
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
                        <strong>✍️ Chọn thủ công</strong>
                        <small>Tự chọn từng câu hỏi đưa vào đề thi.</small>
                    </label>
                </div>

                <div class="mode-option">
                    <input type="radio" id="modeAuto" name="create_mode" value="auto">
                    <label for="modeAuto">
                        <strong>⚡ Bốc câu hỏi ngẫu nhiên</strong>
                        <small>Bốc theo chủ đề và cấu hình 3 mức độ.</small>
                    </label>
                </div>
            </div>

            <!-- 1. CHỌN THỦ CÔNG -->
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
                        <div class="q-item question-row" data-grade="<?= (int)$q['grade'] ?>" data-subject="<?= (int)$q['subject_id'] ?>" data-search="<?= htmlspecialchars(mb_strtolower($q['content'] . ' ' . $q['topic_name'], 'UTF-8')) ?>">
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

            <!-- 2. BỐC NGẪU NHIÊN CHỦ ĐỀ & 3 MỨC ĐỘ -->
            <div id="autoSection" hidden>
                <div class="auto-panel">
                    <div class="form-group">
                        <label for="autoTopic">Chọn Chủ đề bài học</label>
                        <select name="auto_topic_id" id="autoTopic" disabled>
                            <option value="0">-- Tất cả các chủ đề (Toàn bộ chương trình lớp) --</option>
                            <?php foreach ($allTopics as $t): ?>
                                <option value="<?= (int)$t['topic_id'] ?>" data-grade="<?= (int)$t['grade'] ?>" data-subject="<?= (int)$t['subject_id'] ?>">
                                    Lớp <?= (int)$t['grade'] ?> - <?= htmlspecialchars($t['topic_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <label style="margin-top: 15px;">Cấu hình số lượng câu hỏi theo 3 mức độ:</label>
                    <div class="three-columns" style="margin-top: 10px;">
                        <div class="form-group">
                            <label style="font-weight: 500; color: #166534;">🟢 Nhận biết (EASY)</label>
                            <input type="number" class="auto-input" name="count_easy" value="5" min="0" max="50" disabled>
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 500; color: #92400e;">🟡 Thông hiểu (MEDIUM)</label>
                            <input type="number" class="auto-input" name="count_medium" value="3" min="0" max="50" disabled>
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 500; color: #991b1b;">🔴 Vận dụng (HARD)</label>
                            <input type="number" class="auto-input" name="count_hard" value="2" min="0" max="50" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button type="submit" class="btn btn-submit">💾 Lưu đề thi</button>
            </div>
        </section>
    </form>
</div>

<script>
    const gradeSelect = document.getElementById('gradeSelect');
    const subjectSelect = document.getElementById('subjectSelect');
    const examTypeSelect = document.getElementById('examTypeSelect');
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
        if (typeof questionsData === 'undefined' || !availEasy) return;
        const selectedGrade = parseInt(gradeSelect.value, 10);
        const selectedSubject = parseInt(subjectSelect.value, 10);
        const selectedTopicId = parseInt(autoTopic.value, 10);
        let easyCount = 0, mediumCount = 0, hardCount = 0;

        questionsData.forEach(q => {
            if (q.g === selectedGrade && q.s === selectedSubject && (selectedTopicId === 0 || q.t === selectedTopicId)) {
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

    // Chỉ hiện các môn thuộc khối lớp đang chọn
    function updateSubjectOptions() {
        const grade = gradeSelect.value;
        let firstMatch = null;
        Array.from(subjectSelect.options).forEach(opt => {
            const match = opt.dataset.grade === grade;
            opt.hidden = !match;
            opt.disabled = !match;
            if (match && firstMatch === null) firstMatch = opt;
        });
        const current = subjectSelect.selectedOptions[0];
        if (!current || current.disabled) {
            subjectSelect.value = firstMatch ? firstMatch.value : '';
        }
    }

    // Ẩn lựa chọn "Ôn thi vào lớp 10" nếu không phải Khối lớp 9
    function updateExamTypeOptions() {
        const isGrade9 = gradeSelect.value === '9';
        const grade10Opt = examTypeSelect.querySelector('option[value="GRADE_10"]');
        if (grade10Opt) {
            grade10Opt.hidden = !isGrade9;
            grade10Opt.disabled = !isGrade9;
            if (!isGrade9 && examTypeSelect.value === 'GRADE_10') {
                examTypeSelect.value = 'PRACTICE';
            }
        }
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
    }

    function updateQuestionList() {
        const grade = gradeSelect.value;
        const kw = questionSearch.value.trim().toLowerCase();

        document.querySelectorAll('.question-row').forEach(row => {
            const sameGrade = row.dataset.grade === grade && row.dataset.subject === subjectSelect.value;
            const matchSearch = row.dataset.search.includes(kw);
            row.hidden = !(sameGrade && matchSearch);
        });
    }

    function updateTopicOptions() {
        const grade = gradeSelect.value;
        Array.from(autoTopic.options).forEach(opt => {
            if (opt.value === "0") return;
            opt.hidden = opt.dataset.grade !== grade || opt.dataset.subject !== subjectSelect.value;
        });
        autoTopic.value = "0";
    }

    function updateSelectedCount() {
        const count = document.querySelectorAll('.question-checkbox:checked:not(:disabled)').length;
        selectedCount.textContent = count;
    }

    gradeSelect.addEventListener('change', () => {
        updateSubjectOptions();
        updateExamTypeOptions();
        updateQuestionList();
        updateTopicOptions();
        updateAvailableCounts();
    });
    subjectSelect.addEventListener('change', () => { updateQuestionList(); updateTopicOptions(); updateAvailableCounts(); });
    autoTopic.addEventListener('change', updateAvailableCounts);

    questionSearch.addEventListener('input', updateQuestionList);
    modeManual.addEventListener('change', toggleMode);
    modeAuto.addEventListener('change', toggleMode);

    document.querySelectorAll('.question-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    document.getElementById('examForm').addEventListener('submit', function(e) {
        if (modeManual.checked && selectedCount.textContent === '0') {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất 1 câu hỏi.');
        }
    });

    updateSubjectOptions();
    updateExamTypeOptions();
    toggleMode();
</script>
</body>
</html>
