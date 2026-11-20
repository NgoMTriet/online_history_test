<?php
require_once __DIR__ . '/../api/be_teacher_exam.php';

$currentSubjectName = '';
foreach ($allSubjects as $subj) {
    if ((int)$subj['subject_id'] === $defaultSubjectId) {
        $currentSubjectName = (string)$subj['subject_name'];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo đề thi | EXAM365</title>

    <link rel="stylesheet" href="/../assets/css/UI_teacher.css">
    <link rel="stylesheet" href="/../assets/css/teacher_exam_attempts.css">
</head>

<body>
<?php include __DIR__ . '/../includes/teacher_sidebar.php'; ?>
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
        <input type="hidden" name="max_attempts" value="0">

        <!-- PHẦN 1: THÔNG TIN CHUNG -->
        <section class="card">
            <div class="section-heading">
                <div class="step-number">01</div>
                <div>
                    <h2>Thông tin chung</h2>
                    <p class="section-description">Tên đề thi, khối lớp, phạm vi áp dụng, thời gian và mật khẩu truy cập.</p>
                </div>
            </div>

            <div class="form-group">
                <label for="title">Tên đề thi</label>
                <input type="text" id="title" name="title" placeholder="Ví dụ: Đề ôn tập Giữa kì 1 - Lịch sử <?= (int)$defaultGrade ?>" required>
            </div>

            <div class="form-grid-3">
                <!-- Hàng 1: Khối lớp | Môn học | Kỳ thi -->
                <div class="form-group">
                    <label for="gradeDisplay">Khối lớp</label>
                    <input type="hidden" name="grade" id="gradeSelect" value="<?= (int)$defaultGrade ?>">
                    <input
                        type="text"
                        id="gradeDisplay"
                        value="Khối <?= (int)$defaultGrade ?> — <?= $questionCounts[(int)$defaultGrade] ?? 0 ?> câu"
                        readonly
                    >
                </div>

                <div class="form-group">
                    <label for="subjectDisplay">Môn học</label>
                    <input
                        type="hidden"
                        name="subject_id"
                        id="subjectSelect"
                        value="<?= (int)$defaultSubjectId ?>"
                        data-name="<?= htmlspecialchars(mb_strtolower(trim($currentSubjectName), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <input
                        type="text"
                        id="subjectDisplay"
                        value="<?= htmlspecialchars($currentSubjectName, ENT_QUOTES, 'UTF-8') ?>"
                        readonly
                    >
                    <small style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">
                        <?php if ($hasProfileSubjects): ?>
                            Gán sẵn theo hồ sơ giảng dạy (<a href="profile.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">Cập nhật hồ sơ</a>).
                        <?php else: ?>
                            Chưa cấu hình môn giảng dạy (<a href="profile.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">Thiết lập trong hồ sơ</a>).
                        <?php endif; ?>
                    </small>
                </div>

                <div class="form-group">
                    <label for="examTypeSelect">Kỳ thi / Loại đề</label>
                    <select name="exam_type" id="examTypeSelect">
                        <option value="REGULAR">Luyện tập / Thường xuyên</option>
                        <option value="MIDTERM_1">Giữa kì 1</option>
                        <option value="FINAL_1">Cuối kì 1</option>
                        <option value="MIDTERM_2">Giữa kì 2</option>
                        <option value="FINAL_2">Cuối kì 2</option>
                    </select>
                </div>

                <!-- Hàng 2: Thời gian mở | Thời gian đóng | Thời gian làm bài -->
                <div class="form-group">
                    <label for="startTime">📅 Thời gian mở đề thi</label>
                    <input type="datetime-local" id="startTime" name="start_time">
                    <small style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">Để trống nếu muốn mở đề ngay lập tức.</small>
                </div>

                <div class="form-group">
                    <label for="endTime">🔒 Thời gian đóng đề thi</label>
                    <input type="datetime-local" id="endTime" name="end_time">
                    <small id="endTimeHint" style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">Để trống nếu không giới hạn thời điểm đóng đề.</small>
                </div>

                <div class="form-group">
                    <label for="duration">Thời gian làm bài (phút)</label>
                    <input type="number" id="duration" name="duration" value="15" min="1" max="300" required>
                </div>

                <!-- Hàng 3: Mật khẩu đề thi | Phạm vi hiển thị -->
                <div class="form-group">
                    <label for="examPassword">🔑 Mật khẩu đề thi</label>
                    <input type="text" id="examPassword" name="exam_password" placeholder="Để trống nếu tự do" maxlength="50">
                </div>

                <div class="form-group">
                    <label for="visibilitySelect">🌐 Phạm vi đề thi</label>
                    <select name="visibility" id="visibilitySelect">
                        <option value="PUBLIC">Công khai (Tất cả học sinh)</option>
                        <option value="SCHOOL">Theo trường</option>
                        <option value="GRADE">Theo khối lớp</option>
                        <option value="CLASS">Theo lớp (Lớp ra đề)</option>
                    </select>
                    <small id="visibilityHint" style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">
                        Mọi học sinh trên hệ thống đều có thể nhìn thấy và làm đề này.
                    </small>
                </div>
            </div>

            <!-- KHUNG CẤU HÌNH CHI TIẾT PHẠM VI (CHỈ HIỆN KHI CHỌN THEO TRƯỜNG / KHỐI / LỚP) -->
            <div class="scope-panel" id="scopeDetailsPanel" hidden>
                <div class="scope-grid">
                    <div class="form-group" id="schoolFieldWrap" style="margin-bottom: 0;" hidden>
                        <label for="schoolNameInput">🏫 Trường áp dụng</label>
                        <input
                            type="text"
                            id="schoolNameInput"
                            name="school_name"
                            list="schoolSuggestions"
                            value="<?= htmlspecialchars($teacherSchoolName, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Nhập tên trường (VD: THCS Nguyễn Trãi)"
                        >
                        <datalist id="schoolSuggestions">
                            <?php foreach ($availableSchools as $sch): ?>
                                <option value="<?= htmlspecialchars($sch, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <!-- CHỌN LỚP RA ĐỀ (HIỆN KHI CHỌN PHẠM VI THEO LỚP) -->
                <div id="classTargetWrap" style="margin-top: 14px;" hidden>
                    <label>👥 Lớp ra đề (thuộc <span id="selectedGradeLabel">Khối <?= (int)$defaultGrade ?></span>)</label>

                    <?php if (!empty($availableClassItems)): ?>
                        <div class="class-chips-wrap" id="existingClassesBox">
                            <?php foreach ($availableClassItems as $item): ?>
                                <label
                                    class="class-chip"
                                    data-grade="<?= (int)$item['grade'] ?>"
                                    data-school="<?= htmlspecialchars(mb_strtolower($item['school_name'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <input type="checkbox" name="target_class_list[]" value="<?= htmlspecialchars($item['class_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <span>Lớp <?= htmlspecialchars($item['class_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <input
                        type="text"
                        id="customTargetClasses"
                        name="custom_target_classes"
                        style="margin-top: 10px;"
                        placeholder="Hoặc nhập tên các lớp ra đề, cách nhau bằng dấu phẩy (Ví dụ: <?= (int)$defaultGrade ?>A1, <?= (int)$defaultGrade ?>A2)"
                    >
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
                <div class="question-toolbar" style="flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <span class="question-count">
                            Đã chọn <span id="selectedCount">0</span> câu (Hiển thị <span id="visibleQuestionCount">0</span> câu phù hợp)
                        </span>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px; font-size: 12px; font-weight: 600;">
                            <span style="background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 999px;">
                                🟢 Dễ: <span id="manualSelEasy">0</span>/<span id="manualAvailEasy">0</span>
                            </span>
                            <span style="background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 999px;">
                                🟡 Trung bình: <span id="manualSelMedium">0</span>/<span id="manualAvailMedium">0</span>
                            </span>
                            <span style="background: #fee2e2; color: #991b1b; padding: 3px 10px; border-radius: 999px;">
                                🔴 Khó: <span id="manualSelHard">0</span>/<span id="manualAvailHard">0</span>
                            </span>
                        </div>
                    </div>
                    <input type="text" id="questionSearch" style="max-width: 300px;" placeholder="🔎 Tìm nội dung câu hỏi...">
                </div>

                <div class="q-list">
                    <?php foreach ($questionsBank as $q): ?>
                        <?php
                            $diffClass = 'badge-easy';
                            $diffText = 'Dễ (Nhận biết)';
                            $diffUpper = strtoupper($q['difficulty'] ?? 'EASY');
                            if ($diffUpper === 'MEDIUM') { $diffClass = 'badge-medium'; $diffText = 'Trung bình (Thông hiểu)'; }
                            if ($diffUpper === 'HARD') { $diffClass = 'badge-hard'; $diffText = 'Khó (Vận dụng)'; }
                        ?>
                        <div
                            class="q-item question-row"
                            data-grade="<?= (int)$q['grade'] ?>"
                            data-subject="<?= (int)$q['subject_id'] ?>"
                            data-subject-name="<?= htmlspecialchars((string)$q['subject_norm'], ENT_QUOTES, 'UTF-8') ?>"
                            data-difficulty="<?= htmlspecialchars($diffUpper, ENT_QUOTES, 'UTF-8') ?>"
                            data-search="<?= htmlspecialchars(mb_strtolower($q['content'] . ' ' . $q['topic_name'], 'UTF-8')) ?>"
                        >
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
                                <option
                                    value="<?= (int)$t['topic_id'] ?>"
                                    data-grade="<?= (int)$t['grade'] ?>"
                                    data-subject="<?= (int)$t['subject_id'] ?>"
                                    data-subject-name="<?= htmlspecialchars((string)$t['subject_norm'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    Lớp <?= (int)$t['grade'] ?> - <?= htmlspecialchars($t['topic_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <label style="margin-top: 15px;">Cấu hình số lượng câu hỏi theo 3 mức độ:</label>
                    <div class="three-columns" style="margin-top: 10px;">
                        <div class="form-group">
                            <label style="font-weight: 500; color: #166534;">
                                🟢 Dễ / Nhận biết (<span id="autoAvailEasy">0</span> câu có sẵn)
                            </label>
                            <input type="number" class="auto-input" id="inputCountEasy" name="count_easy" value="5" min="0" max="50" disabled>
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 500; color: #92400e;">
                                🟡 Trung bình / Thông hiểu (<span id="autoAvailMedium">0</span> câu có sẵn)
                            </label>
                            <input type="number" class="auto-input" id="inputCountMedium" name="count_medium" value="3" min="0" max="50" disabled>
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 500; color: #991b1b;">
                                🔴 Khó / Vận dụng (<span id="autoAvailHard">0</span> câu có sẵn)
                            </label>
                            <input type="number" class="auto-input" id="inputCountHard" name="count_hard" value="2" min="0" max="50" disabled>
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
    const gradeSelect          = document.getElementById('gradeSelect');
    const subjectSelect        = document.getElementById('subjectSelect');
    const examTypeSelect       = document.getElementById('examTypeSelect');
    const startTimeInput       = document.getElementById('startTime');
    const endTimeInput         = document.getElementById('endTime');
    const endTimeHint          = document.getElementById('endTimeHint');
    const durationInput        = document.getElementById('duration');
    const visibilitySelect     = document.getElementById('visibilitySelect');
    const visibilityHint       = document.getElementById('visibilityHint');
    const scopeDetailsPanel    = document.getElementById('scopeDetailsPanel');
    const schoolFieldWrap      = document.getElementById('schoolFieldWrap');
    const schoolNameInput      = document.getElementById('schoolNameInput');
    const classTargetWrap      = document.getElementById('classTargetWrap');
    const selectedGradeLabel   = document.getElementById('selectedGradeLabel');
    const customTargetClasses  = document.getElementById('customTargetClasses');
    const modeManual           = document.getElementById('modeManual');
    const modeAuto             = document.getElementById('modeAuto');
    const manualSection        = document.getElementById('manualSection');
    const autoSection          = document.getElementById('autoSection');
    const autoTopic            = document.getElementById('autoTopic');
    const autoInputs           = document.querySelectorAll('.auto-input');
    const questionSearch       = document.getElementById('questionSearch');
    const selectedCount        = document.getElementById('selectedCount');
    const visibleQuestionCount = document.getElementById('visibleQuestionCount');

    const questionsBankData = <?= json_encode(array_map(fn($q) => [
        'grade'       => (int)$q['grade'],
        'topic_id'    => (int)$q['topic_id'],
        'subject_id'  => (int)$q['subject_id'],
        'subjectName' => (string)$q['subject_norm'],
        'difficulty'  => strtoupper($q['difficulty'] ?? 'EASY')
    ], $questionsBank), JSON_UNESCAPED_UNICODE) ?>;

    const visibilityDescriptions = {
        PUBLIC: 'Mọi học sinh trên hệ thống đều có thể nhìn thấy và làm đề này.',
        SCHOOL: 'Tất cả học sinh thuộc trường được chọn (mọi khối/lớp) đều thấy và làm được đề này.',
        GRADE:  'Tất cả học sinh thuộc các lớp của khối đã chọn trong trường (VD: 7A1, 7A2...) đều làm được đề này.',
        CLASS:  'Chỉ học sinh thuộc các lớp ra đề được chọn bên dưới (VD: 7A1, 7A2) mới làm được đề này.'
    };

    function syncExamTimeBounds() {
        if (!startTimeInput || !endTimeInput) return;

        if (startTimeInput.value) {
            endTimeInput.min = startTimeInput.value;
            if (endTimeInput.value && new Date(endTimeInput.value) <= new Date(startTimeInput.value)) {
                endTimeInput.value = '';
                if (endTimeHint) {
                    endTimeHint.textContent = 'Thời gian đóng đề đã được đặt lại vì phải sau thời gian mở đề.';
                    endTimeHint.style.color = '#d97706';
                }
            } else if (endTimeHint) {
                endTimeHint.textContent = 'Đề thi sẽ tự động đóng sau thời điểm này.';
                endTimeHint.style.color = '#64748b';
            }
        } else {
            endTimeInput.removeAttribute('min');
            if (endTimeHint) {
                endTimeHint.textContent = endTimeInput.value
                    ? 'Đề thi sẽ tự động đóng sau thời điểm này.'
                    : 'Để trống nếu không giới hạn thời điểm đóng đề.';
                endTimeHint.style.color = '#64748b';
            }
        }
    }

    if (startTimeInput && endTimeInput) {
        startTimeInput.addEventListener('change', syncExamTimeBounds);
        endTimeInput.addEventListener('change', syncExamTimeBounds);
    }

    function getSelectedSubjectInfo() {
        return {
            id: subjectSelect ? subjectSelect.value : '',
            name: subjectSelect ? (subjectSelect.dataset.name || '').trim().toLowerCase() : ''
        };
    }

    function isSameSubject(elSubjectId, elSubjectName, selectedSubj) {
        if (selectedSubj.id !== '' && elSubjectId === selectedSubj.id) {
            return true;
        }
        if (selectedSubj.name !== '' && elSubjectName !== '' && elSubjectName === selectedSubj.name) {
            return true;
        }
        return false;
    }

    function updateVisibilityUI() {
        const vis    = visibilitySelect.value;
        const grade  = gradeSelect.value;
        const school = schoolNameInput.value.trim().toLowerCase();

        visibilityHint.textContent = visibilityDescriptions[vis] || '';
        selectedGradeLabel.textContent = 'Khối ' + grade;

        const needSchool = (vis !== 'PUBLIC');
        const needClass  = (vis === 'CLASS');

        if (scopeDetailsPanel) {
            scopeDetailsPanel.hidden = !needSchool && !needClass;
        }
        schoolFieldWrap.hidden = !needSchool;
        schoolNameInput.required = needSchool;
        classTargetWrap.hidden = !needClass;

        const allChips = Array.from(document.querySelectorAll('#existingClassesBox .class-chip'));
        const seenDisplayed = new Set();

        allChips.forEach(chip => {
            const chipGrade  = chip.dataset.grade || '';
            const chipSchool = chip.dataset.school || '';
            const cb         = chip.querySelector('input[type="checkbox"]');
            const clsVal     = cb ? cb.value : '';

            const matchGrade  = (chipGrade === '0' || chipGrade === '' || chipGrade === grade);
            const matchSchool = (school === '' || chipSchool === '' || chipSchool === school);
            const isDuplicate = seenDisplayed.has(clsVal);

            const show = matchGrade && matchSchool && !isDuplicate;
            if (show && clsVal !== '') {
                seenDisplayed.add(clsVal);
            }

            chip.style.display = show ? 'inline-flex' : 'none';
            if (!show && cb) {
                cb.checked = false;
            }
            if (cb) {
                cb.disabled = !needClass || !show;
            }
        });

        customTargetClasses.disabled = !needClass;
        customTargetClasses.placeholder = `Hoặc nhập tên các lớp ra đề, cách nhau bằng dấu phẩy (Ví dụ: ${grade}A1, ${grade}A2)`;
    }

    examTypeSelect.addEventListener('change', function() {
        if (this.value === 'REGULAR') {
            if (durationInput.value === '45') durationInput.value = '15';
        } else {
            if (durationInput.value === '15') durationInput.value = '45';
        }
    });

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
        const selectedSubj = getSelectedSubjectInfo();
        const kw = questionSearch.value.trim().toLowerCase();
        let visibleCount = 0;
        const availCounts = { EASY: 0, MEDIUM: 0, HARD: 0 };

        document.querySelectorAll('.question-row').forEach(row => {
            const sameGrade = row.dataset.grade === grade
                && isSameSubject(row.dataset.subject, row.dataset.subjectName || '', selectedSubj);
            const matchSearch = row.dataset.search.includes(kw);
            const show = sameGrade && matchSearch;
            row.hidden = !show;
            if (show) {
                visibleCount++;
                const diff = (row.dataset.difficulty || 'EASY').toUpperCase();
                if (availCounts[diff] !== undefined) {
                    availCounts[diff]++;
                }
            }
        });

        if (visibleQuestionCount) {
            visibleQuestionCount.textContent = visibleCount;
        }
        document.getElementById('manualAvailEasy').textContent   = availCounts.EASY;
        document.getElementById('manualAvailMedium').textContent = availCounts.MEDIUM;
        document.getElementById('manualAvailHard').textContent   = availCounts.HARD;
    }

    function updateAutoAvailableCounts() {
        const grade = parseInt(gradeSelect.value, 10) || 0;
        const selectedSubj = getSelectedSubjectInfo();
        const topicId = parseInt(autoTopic.value, 10) || 0;
        const counts = { EASY: 0, MEDIUM: 0, HARD: 0 };

        questionsBankData.forEach(item => {
            if (item.grade !== grade) return;
            if (!isSameSubject(String(item.subject_id), item.subjectName || '', selectedSubj)) return;
            if (topicId > 0 && item.topic_id !== topicId) return;

            if (counts[item.difficulty] !== undefined) {
                counts[item.difficulty]++;
            }
        });

        document.getElementById('autoAvailEasy').textContent   = counts.EASY;
        document.getElementById('autoAvailMedium').textContent = counts.MEDIUM;
        document.getElementById('autoAvailHard').textContent   = counts.HARD;

        document.getElementById('inputCountEasy').max   = counts.EASY;
        document.getElementById('inputCountMedium').max = counts.MEDIUM;
        document.getElementById('inputCountHard').max   = counts.HARD;
    }

    function updateTopicOptions() {
        const grade = gradeSelect.value;
        const selectedSubj = getSelectedSubjectInfo();

        Array.from(autoTopic.options).forEach(opt => {
            if (opt.value === "0") return;
            const match = opt.dataset.grade === grade
                && isSameSubject(opt.dataset.subject, opt.dataset.subjectName || '', selectedSubj);
            opt.hidden = !match;
        });
        autoTopic.value = "0";
        updateAutoAvailableCounts();
    }

    function updateSelectedCount() {
        let total = 0;
        const selCounts = { EASY: 0, MEDIUM: 0, HARD: 0 };

        document.querySelectorAll('.question-checkbox:checked:not(:disabled)').forEach(cb => {
            total++;
            const row = cb.closest('.question-row');
            if (row) {
                const diff = (row.dataset.difficulty || 'EASY').toUpperCase();
                if (selCounts[diff] !== undefined) {
                    selCounts[diff]++;
                }
            }
        });

        selectedCount.textContent = total;
        document.getElementById('manualSelEasy').textContent   = selCounts.EASY;
        document.getElementById('manualSelMedium').textContent = selCounts.MEDIUM;
        document.getElementById('manualSelHard').textContent   = selCounts.HARD;
    }

    visibilitySelect.addEventListener('change', updateVisibilityUI);
    schoolNameInput.addEventListener('input', updateVisibilityUI);
    questionSearch.addEventListener('input', updateQuestionList);
    autoTopic.addEventListener('change', updateAutoAvailableCounts);
    modeManual.addEventListener('change', toggleMode);
    modeAuto.addEventListener('change', toggleMode);

    document.querySelectorAll('.question-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    document.getElementById('examForm').addEventListener('submit', function(e) {
        if (startTimeInput && endTimeInput && startTimeInput.value && endTimeInput.value) {
            if (new Date(endTimeInput.value) <= new Date(startTimeInput.value)) {
                e.preventDefault();
                alert('Thời gian đóng đề thi phải sau thời gian mở đề thi.');
                endTimeInput.focus();
                return;
            }
        }

        if (visibilitySelect.value === 'CLASS') {
            const checkedCount = document.querySelectorAll('input[name="target_class_list[]"]:checked:not(:disabled)').length;
            const hasCustom = customTargetClasses.value.trim() !== '';
            if (checkedCount === 0 && !hasCustom) {
                e.preventDefault();
                alert('Vui lòng chọn hoặc nhập ít nhất một lớp ra đề.');
                return;
            }
        }

        if (modeManual.checked && selectedCount.textContent === '0') {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất 1 câu hỏi.');
        }
    });

    updateVisibilityUI();
    toggleMode();
</script>
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