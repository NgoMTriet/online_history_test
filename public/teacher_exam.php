<?php
require_once __DIR__ . '/../api/be_teacher_exam.php';
include __DIR__ . '/../includes/teacher_sidebar.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo đề thi | EXAM365</title>

    <link rel="stylesheet" href="/../assets/css/UI_teacher.css">
    <style>
        .scope-panel {
            margin-top: 16px;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }
        .scope-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }
        .class-chips-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .class-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            cursor: pointer;
            user-select: none;
        }
        .class-chip input[type="checkbox"] {
            margin: 0;
            accent-color: #2563eb;
        }
    </style>
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

        <!-- PHẦN 1: THÔNG TIN CHUNG -->
        <section class="card">
            <div class="section-heading">
                <div class="step-number">01</div>
                <div>
                    <h2>Thông tin chung</h2>
                    <p class="section-description">Tên đề thi, khối lớp, phạm vi áp dụng, thời gian làm bài và mật khẩu truy cập.</p>
                </div>
            </div>

            <div class="form-group">
                <label for="title">Tên đề thi</label>
                <input type="text" id="title" name="title" placeholder="Ví dụ: Đề ôn tập Giữa kì 1 - Lịch sử <?= (int)$defaultGrade ?>" required>
            </div>

            <div class="form-grid-3">
                <div class="form-group">
                    <label for="gradeSelect">Khối lớp</label>
                    <select name="grade" id="gradeSelect">
                        <?php foreach ($gradeOptions as $g): ?>
                            <option value="<?= (int)$g ?>" <?= (int)$g === (int)$defaultGrade ? 'selected' : '' ?>>
                                Khối <?= (int)$g ?> — <?= $questionCounts[(int)$g] ?? 0 ?> câu
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subjectSelect">Môn học</label>
                    <select name="subject_id" id="subjectSelect" required>
                        <?php foreach ($allSubjects as $subj): ?>
                            <?php
                                $sid    = (int)$subj['subject_id'];
                                $sGrade = (int)$subj['grade'];
                                $sName  = (string)$subj['subject_name'];
                            ?>
                            <option
                                value="<?= $sid ?>"
                                data-grade="<?= $sGrade ?>"
                                data-name="<?= htmlspecialchars(mb_strtolower(trim($sName), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                                <?= $sid === $defaultSubjectId ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($sName, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">
                        <?php if ($hasProfileSubjects): ?>
                            Gán sẵn theo hồ sơ giảng dạy (<a href="profile.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">Cập nhật hồ sơ</a>).
                        <?php else: ?>
                            Chưa cấu hình môn giảng dạy (<a href="profile.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">Thiết lập trong hồ sơ</a>).
                        <?php endif; ?>
                    </small>
                </div>

                <!-- Ô CHỌN KỲ THI / LOẠI ĐỀ -->
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

            <!-- PHẠM VI HIỂN THỊ & ĐỐI TƯỢNG RA ĐỀ -->
            <div class="scope-panel">
                <div class="scope-grid">
                    <div class="form-group" style="margin-bottom: 0;">
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
                <div class="question-toolbar">
                    <span class="question-count">Đã chọn <span id="selectedCount">0</span> câu (Hiển thị <span id="visibleQuestionCount">0</span> câu phù hợp)</span>
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
                        <div
                            class="q-item question-row"
                            data-grade="<?= (int)$q['grade'] ?>"
                            data-subject="<?= (int)$q['subject_id'] ?>"
                            data-subject-name="<?= htmlspecialchars((string)$q['subject_norm'], ENT_QUOTES, 'UTF-8') ?>"
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
    const gradeSelect          = document.getElementById('gradeSelect');
    const subjectSelect        = document.getElementById('subjectSelect');
    const examTypeSelect       = document.getElementById('examTypeSelect');
    const durationInput        = document.getElementById('duration');
    const visibilitySelect     = document.getElementById('visibilitySelect');
    const visibilityHint       = document.getElementById('visibilityHint');
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

    const visibilityDescriptions = {
        PUBLIC: 'Mọi học sinh trên hệ thống đều có thể nhìn thấy và làm đề này.',
        SCHOOL: 'Tất cả học sinh thuộc trường được chọn (mọi khối/lớp) đều thấy và làm được đề này.',
        GRADE:  'Tất cả học sinh thuộc các lớp của khối đã chọn trong trường (VD: 7A1, 7A2...) đều làm được đề này.',
        CLASS:  'Chỉ học sinh thuộc các lớp ra đề được chọn bên dưới (VD: 7A1, 7A2) mới làm được đề này.'
    };

    function getSelectedSubjectInfo() {
        const opt = subjectSelect.selectedOptions[0];
        return {
            id: opt ? opt.value : '',
            name: opt ? (opt.dataset.name || '').trim().toLowerCase() : ''
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
        schoolFieldWrap.hidden = !needSchool;
        schoolNameInput.required = needSchool;

        const needClass = (vis === 'CLASS');
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

    function updateSubjectOptions() {
        const grade = gradeSelect.value;
        const prevSelected = subjectSelect.selectedOptions[0];
        const prevSubjectName = prevSelected ? (prevSelected.dataset.name || '') : '';
        const options = Array.from(subjectSelect.options);

        // Kiểm tra xem có môn nào khớp đúng khối đang chọn (hoặc grade = 0 dùng chung) không
        const hasExactGradeMatch = options.some(opt => opt.dataset.grade === grade || opt.dataset.grade === '0');

        let firstMatch = null;
        let sameNameMatch = null;

        options.forEach(opt => {
            const match = hasExactGradeMatch
                ? (opt.dataset.grade === grade || opt.dataset.grade === '0')
                : true;
            opt.hidden = !match;
            opt.disabled = !match;
            if (match) {
                if (firstMatch === null) firstMatch = opt;
                if (prevSubjectName !== '' && opt.dataset.name === prevSubjectName) {
                    sameNameMatch = opt;
                }
            }
        });

        const current = subjectSelect.selectedOptions[0];
        if (!current || current.disabled) {
            const chosen = sameNameMatch || firstMatch;
            subjectSelect.value = chosen ? chosen.value : '';
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
        const selectedSubj = getSelectedSubjectInfo();
        const kw = questionSearch.value.trim().toLowerCase();
        let visibleCount = 0;

        document.querySelectorAll('.question-row').forEach(row => {
            const sameGrade = row.dataset.grade === grade
                && isSameSubject(row.dataset.subject, row.dataset.subjectName || '', selectedSubj);
            const matchSearch = row.dataset.search.includes(kw);
            const show = sameGrade && matchSearch;
            row.hidden = !show;
            if (show) visibleCount++;
        });

        if (visibleQuestionCount) {
            visibleQuestionCount.textContent = visibleCount;
        }
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
    }

    function updateSelectedCount() {
        const count = document.querySelectorAll('.question-checkbox:checked:not(:disabled)').length;
        selectedCount.textContent = count;
    }

    visibilitySelect.addEventListener('change', updateVisibilityUI);
    schoolNameInput.addEventListener('input', updateVisibilityUI);
    gradeSelect.addEventListener('change', () => {
        updateSubjectOptions();
        updateVisibilityUI();
        updateQuestionList();
        updateTopicOptions();
    });
    subjectSelect.addEventListener('change', () => { updateQuestionList(); updateTopicOptions(); });

    questionSearch.addEventListener('input', updateQuestionList);
    modeManual.addEventListener('change', toggleMode);
    modeAuto.addEventListener('change', toggleMode);

    document.querySelectorAll('.question-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    document.getElementById('examForm').addEventListener('submit', function(e) {
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

    updateSubjectOptions();
    updateVisibilityUI();
    toggleMode();
</script>
</body>
</html>