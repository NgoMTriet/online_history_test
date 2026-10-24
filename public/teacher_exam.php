<?php
require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| XỬ LÝ TẠO ĐỀ THI
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'create_exam'
) {
    $title = trim($_POST['title'] ?? '');
    $grade = (int)($_POST['grade'] ?? 9);
    $duration = (int)($_POST['duration'] ?? 15);
    $maxAttempts = (int)($_POST['max_attempts'] ?? 0); // 0 = Không giới hạn
    $examPassword = trim($_POST['exam_password'] ?? ''); // Mật khẩu bài thi
    $mode = $_POST['create_mode'] ?? 'manual';

    if ($title === '') {
        $message = 'Vui lòng nhập tên đề thi.';
        $messageType = 'danger';
    } elseif (!in_array($grade, [6, 7, 8, 9], true)) {
        $message = 'Khối lớp không hợp lệ.';
        $messageType = 'danger';
    } elseif ($duration < 1 || $duration > 300) {
        $message = 'Thời gian làm bài phải từ 1 đến 300 phút.';
        $messageType = 'danger';
    } elseif ($maxAttempts < 0) {
        $message = 'Số lần làm bài không hợp lệ.';
        $messageType = 'danger';
    } elseif (!in_array($mode, ['manual', 'auto'], true)) {
        $message = 'Phương thức chọn câu hỏi không hợp lệ.';
        $messageType = 'danger';
    } else {
        try {
            $selectedQuestionIds = [];

            if ($mode === 'manual') {
                $rawIds = $_POST['question_ids'] ?? [];

                if (!is_array($rawIds)) {
                    $rawIds = [];
                }

                $selectedQuestionIds = array_values(
                    array_unique(
                        array_filter(
                            array_map('intval', $rawIds),
                            fn($id) => $id > 0
                        )
                    )
                );

                if (count($selectedQuestionIds) > 0) {
                    $placeholders = implode(
                        ',',
                        array_fill(0, count($selectedQuestionIds), '?')
                    );

                    $stmtCheck = $conn->prepare(
                        "SELECT q.question_id
                         FROM questions q
                         JOIN topics t ON q.topic_id = t.topic_id
                         WHERE t.grade = ?
                         AND q.question_id IN ($placeholders)"
                    );

                    $stmtCheck->execute(
                        array_merge([$grade], $selectedQuestionIds)
                    );

                    $validIds = array_map(
                        'intval',
                        $stmtCheck->fetchAll(PDO::FETCH_COLUMN)
                    );

                    $selectedQuestionIds = array_values(
                        array_intersect($selectedQuestionIds, $validIds)
                    );
                }
            } else {
                // Chế độ Bốc ngẫu nhiên theo Chủ đề & 3 Mức độ
                $topicId = (int)($_POST['auto_topic_id'] ?? 0);
                $countEasy = max(0, (int)($_POST['count_easy'] ?? 0));
                $countMedium = max(0, (int)($_POST['count_medium'] ?? 0));
                $countHard = max(0, (int)($_POST['count_hard'] ?? 0));

                if ($countEasy + $countMedium + $countHard === 0) {
                    throw new Exception('Vui lòng nhập số lượng cho ít nhất 1 mức độ câu hỏi.');
                }

                $levelsMap = [
                    'EASY' => $countEasy,
                    'MEDIUM' => $countMedium,
                    'HARD' => $countHard
                ];

                foreach ($levelsMap as $level => $count) {
                    if ($count <= 0) continue;

                    $sql = "SELECT q.question_id
                            FROM questions q
                            JOIN topics t ON q.topic_id = t.topic_id
                            WHERE t.grade = :grade AND q.difficulty = :level";

                    if ($topicId > 0) {
                        $sql .= " AND q.topic_id = :topic_id";
                    }

                    $sql .= " ORDER BY RAND() LIMIT :limit";

                    $stmtRandom = $conn->prepare($sql);
                    $stmtRandom->bindValue(':grade', $grade, PDO::PARAM_INT);
                    $stmtRandom->bindValue(':level', $level, PDO::PARAM_STR);
                    $stmtRandom->bindValue(':limit', $count, PDO::PARAM_INT);

                    if ($topicId > 0) {
                        $stmtRandom->bindValue(':topic_id', $topicId, PDO::PARAM_INT);
                    }

                    $stmtRandom->execute();
                    $fetchedIds = array_map('intval', $stmtRandom->fetchAll(PDO::FETCH_COLUMN));
                    
                    $selectedQuestionIds = array_merge($selectedQuestionIds, $fetchedIds);
                }
            }

            $totalQuestions = count($selectedQuestionIds);

            if ($totalQuestions === 0) {
                throw new Exception(
                    'Không tìm thấy câu hỏi phù hợp với cấu hình. Vui lòng kiểm tra lại Ngân hàng câu hỏi.'
                );
            }

            $conn->beginTransaction();

            // Lưu thông tin đề thi (có lưu exam_password)
            $stmtExam = $conn->prepare(
                "INSERT INTO exam
                 (title, grade, duration, max_attempts, exam_password, total_questions, is_randomized)
                 VALUES (:title, :grade, :duration, :max_attempts, :exam_password, :total_questions, :is_randomized)"
            );

            $stmtExam->execute([
                'title' => $title,
                'grade' => $grade,
                'duration' => $duration,
                'max_attempts' => $maxAttempts,
                'exam_password' => $examPassword !== '' ? $examPassword : null,
                'total_questions' => $totalQuestions,
                'is_randomized' => ($mode === 'auto') ? 1 : 0
            ]);

            $examId = (int)$conn->lastInsertId();

            // Lưu danh sách câu hỏi
            $stmtLink = $conn->prepare(
                "INSERT INTO exam_questions (exam_id, question_id)
                 VALUES (:exam_id, :question_id)"
            );

            foreach ($selectedQuestionIds as $questionId) {
                $stmtLink->execute([
                    'exam_id' => $examId,
                    'question_id' => $questionId
                ]);
            }

            $conn->commit();

            $message = "Tạo đề thi thành công! Đề thi gồm {$totalQuestions} câu hỏi.";
            $messageType = 'success';

        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

/*
|--------------------------------------------------------------------------
| LẤY NGÂN HÀNG CÂU HỎI VÀ TẤT CẢ CHỦ ĐỀ
|--------------------------------------------------------------------------
*/
$stmtTopics = $conn->query("SELECT topic_id, topic_name, grade FROM topics ORDER BY grade ASC, topic_name ASC");
$allTopics = $stmtTopics->fetchAll(PDO::FETCH_ASSOC);

$stmtQuestions = $conn->query(
    "SELECT q.question_id, q.content, q.difficulty, t.grade, t.topic_name, t.topic_id
     FROM questions q
     JOIN topics t ON q.topic_id = t.topic_id
     ORDER BY t.grade ASC, q.question_id DESC"
);

$questionsBank = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);
$questionCounts = [6 => 0, 7 => 0, 8 => 0, 9 => 0];

foreach ($questionsBank as $question) {
    $g = (int)$question['grade'];
    if (isset($questionCounts[$g])) {
        $questionCounts[$g]++;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo đề thi | TestSuViet</title>

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #16a34a;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --background: #f1f5f9;
            --white: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 28px 18px;
            background: var(--background);
            color: var(--text);
            font-family: "Segoe UI", Arial, sans-serif;
            line-height: 1.6;
        }

        input, select, button { font: inherit; }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 25px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .title-icon {
            display: grid;
            place-items: center;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: #dbeafe;
            font-size: 28px;
        }

        h1 {
            margin: 0;
            color: #0f172a;
            font-size: clamp(23px, 3vw, 30px);
        }

        .page-description {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .card {
            background: var(--white);
            border: 1px solid #e8edf4;
            border-radius: 18px;
            padding: 25px;
            margin-bottom: 23px;
            box-shadow: 0 6px 22px rgba(15, 23, 42, .045);
        }

        .section-heading {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            margin-bottom: 23px;
        }

        .step-number {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            background: #dbeafe;
            color: var(--primary-dark);
            font-weight: 800;
            border-radius: 12px;
        }

        h2 { margin: 0; font-size: 19px; color: #0f172a; }
        .section-description { color: var(--muted); font-size: 13px; margin-top: 4px; }

        .form-group { margin-bottom: 19px; }

        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: 700;
        }

        input[type="text"], input[type="password"], input[type="number"], select {
            width: 100%;
            min-height: 45px;
            padding: 11px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            outline: none;
            background: #fff;
            color: var(--text);
            transition: border-color .2s;
        }

        input:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        }

        .form-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .three-columns {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .mode-box {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 23px;
        }

        .mode-option {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 17px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 13px;
            cursor: pointer;
        }

        .mode-option:has(input:checked) {
            background: #eff6ff;
            border-color: var(--primary);
        }

        .question-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .question-count {
            padding: 6px 12px;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
        }

        .q-list {
            max-height: 380px;
            overflow-y: auto;
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: #f8fafc;
        }

        .q-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px;
            margin-bottom: 7px;
            border: 1px solid #edf0f5;
            border-radius: 10px;
            background: #fff;
        }

        .q-badge {
            padding: 3px 7px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
        }

        .badge-easy { background: #dcfce7; color: #166534; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-hard { background: #fee2e2; color: #991b1b; }

        .auto-panel {
            padding: 22px;
            border: 1px solid #bfdbfe;
            border-radius: 13px;
            background: #eff6ff;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 11px 18px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary { background: var(--primary); }
        .btn-secondary { background: #475569; }
        .btn-submit { background: var(--success); min-width: 180px; padding: 13px 22px; }

        .alert {
            padding: 15px 18px;
            margin-bottom: 22px;
            border-radius: 12px;
            font-size: 14px;
        }
        .alert-success { color: #166534; background: #dcfce7; }
        .alert-danger { color: #991b1b; background: #fee2e2; }

        [hidden] { display: none !important; }

        @media (max-width: 900px) {
            .form-grid-4 { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .form-grid-4, .three-columns, .mode-box { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
<div class="container">

    <header class="page-header">
        <div class="page-title">
            <div class="title-icon">📝</div>
            <div>
                <h1>Tạo đề thi mới</h1>
                <p class="page-description">
                    Thiết lập đề thi, giới hạn số lần làm, mật khẩu bảo vệ và cấu trúc câu hỏi.
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
                    <p class="section-description">Tên đề thi, khối lớp, thời gian, lượt làm bài và mật khẩu truy cập.</p>
                </div>
            </div>

            <div class="form-group">
                <label for="title">Tên đề thi</label>
                <input type="text" id="title" name="title" placeholder="Ví dụ: Đề thi Ôn tập Chương 1 - Lịch sử 9" required>
            </div>

            <div class="form-grid-4">
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
                    <label for="duration">Thời gian (phút)</label>
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

            <!-- 2. BỐC NGẪU NHIÊN CHỦ ĐỀ & 3 MỨC ĐỘ -->
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
    const modeManual = document.getElementById('modeManual');
    const modeAuto = document.getElementById('modeAuto');
    const manualSection = document.getElementById('manualSection');
    const autoSection = document.getElementById('autoSection');
    const autoTopic = document.getElementById('autoTopic');
    const autoInputs = document.querySelectorAll('.auto-input');
    const questionSearch = document.getElementById('questionSearch');
    const selectedCount = document.getElementById('selectedCount');

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

    gradeSelect.addEventListener('change', () => {
        updateQuestionList();
        updateTopicOptions();
    });

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

    toggleMode();
</script>
</body>
</html>