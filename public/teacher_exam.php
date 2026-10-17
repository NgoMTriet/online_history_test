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
                    // Chỉ chấp nhận câu hỏi thuộc khối lớp đang chọn.
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
                $autoCount = (int)($_POST['auto_count'] ?? 10);

                if ($autoCount < 1 || $autoCount > 100) {
                    throw new Exception(
                        'Số lượng câu hỏi tự động phải từ 1 đến 100.'
                    );
                }

                $stmtRandom = $conn->prepare(
                    "SELECT q.question_id
                     FROM questions q
                     JOIN topics t ON q.topic_id = t.topic_id
                     WHERE t.grade = :grade
                     ORDER BY RAND()
                     LIMIT :limit"
                );

                $stmtRandom->bindValue(
                    ':grade',
                    $grade,
                    PDO::PARAM_INT
                );

                $stmtRandom->bindValue(
                    ':limit',
                    $autoCount,
                    PDO::PARAM_INT
                );

                $stmtRandom->execute();

                $selectedQuestionIds = array_map(
                    'intval',
                    $stmtRandom->fetchAll(PDO::FETCH_COLUMN)
                );
            }

            $totalQuestions = count($selectedQuestionIds);

            if ($totalQuestions === 0) {
                throw new Exception(
                    'Không có câu hỏi phù hợp. Hãy chọn câu hỏi hoặc kiểm tra ngân hàng câu hỏi của khối lớp này.'
                );
            }

            $conn->beginTransaction();

            // Lưu thông tin đề thi.
            $stmtExam = $conn->prepare(
                "INSERT INTO exam
                 (title, grade, duration, total_questions, is_randomized)
                 VALUES (:title, :grade, :duration, :total_questions, :is_randomized)"
            );

            $stmtExam->execute([
                'title' => $title,
                'grade' => $grade,
                'duration' => $duration,
                'total_questions' => $totalQuestions,
                'is_randomized' => ($mode === 'auto') ? 1 : 0
            ]);

            $examId = (int)$conn->lastInsertId();

            // Lưu danh sách câu hỏi của đề thi.
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

            $message = "Tạo đề thi thành công! Đề có {$totalQuestions} câu hỏi.";
            $messageType = 'success';

        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $message = $e instanceof Exception
                ? $e->getMessage()
                : 'Không thể tạo đề thi. Vui lòng kiểm tra lại cấu trúc cơ sở dữ liệu.';

            $messageType = 'danger';
        }
    }
}

/*
|--------------------------------------------------------------------------
| LẤY NGÂN HÀNG CÂU HỎI
|--------------------------------------------------------------------------
*/
$stmtQuestions = $conn->query(
    "SELECT q.question_id, q.content, t.grade, t.topic_name
     FROM questions q
     JOIN topics t ON q.topic_id = t.topic_id
     ORDER BY t.grade ASC, q.question_id DESC"
);

$questionsBank = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);

$questionCounts = [6 => 0, 7 => 0, 8 => 0, 9 => 0];

foreach ($questionsBank as $question) {
    $questionGrade = (int)$question['grade'];

    if (isset($questionCounts[$questionGrade])) {
        $questionCounts[$questionGrade]++;
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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 28px 18px;
            background: var(--background);
            color: var(--text);
            font-family: "Segoe UI", Arial, sans-serif;
            line-height: 1.6;
        }

        input, select, button {
            font: inherit;
        }

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
            line-height: 1.3;
        }

        .page-description {
            margin: 6px 0 0;
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

        h2 {
            margin: 0;
            color: #0f172a;
            font-size: 19px;
        }

        .section-description {
            color: var(--muted);
            font-size: 13px;
            margin: 4px 0 0;
        }

        .form-group {
            margin-bottom: 19px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: 700;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            min-height: 45px;
            padding: 11px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            outline: none;
            background: #fff;
            color: var(--text);
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        }

        .two-columns {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
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
            transition: background .2s, border-color .2s;
        }

        .mode-option:has(input:checked) {
            background: #eff6ff;
            border-color: var(--primary);
        }

        .mode-option input {
            margin-top: 5px;
            accent-color: var(--primary);
        }

        .mode-option label {
            margin: 0;
            cursor: pointer;
        }

        .mode-option strong {
            display: block;
            font-size: 14px;
        }

        .mode-option small {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 400;
        }

        .question-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 14px;
        }

        .question-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 11px;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
        }

        .search-box {
            width: 100%;
            max-width: 320px;
        }

        .q-list {
            max-height: 390px;
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
            transition: border-color .2s, background .2s;
        }

        .q-item:last-child {
            margin-bottom: 0;
        }

        .q-item:hover {
            border-color: #93c5fd;
            background: #f8fbff;
        }

        .q-item input {
            margin-top: 5px;
            width: 17px;
            height: 17px;
            flex-shrink: 0;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .q-item label {
            flex: 1;
            margin: 0;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .q-grade {
            display: inline-block;
            margin-right: 7px;
            padding: 3px 7px;
            border-radius: 6px;
            background: #dcfce7;
            color: #166534;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .auto-panel {
            padding: 20px;
            border: 1px solid #bfdbfe;
            border-radius: 13px;
            background: #eff6ff;
        }

        .auto-panel p {
            margin: 0 0 15px;
            color: #475569;
            font-size: 13px;
        }

        .auto-count-input {
            max-width: 230px;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s, background .15s;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(15, 23, 42, .12);
        }

        .btn-primary {
            background: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: #475569;
        }

        .btn-secondary:hover {
            background: #334155;
        }

        .submit-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .btn-submit {
            min-width: 180px;
            padding: 13px 22px;
            background: var(--success);
            font-size: 15px;
        }

        .btn-submit:hover {
            background: #15803d;
        }

        .alert {
            padding: 15px 18px;
            margin-bottom: 22px;
            border: 1px solid transparent;
            border-radius: 12px;
            font-size: 14px;
        }

        .alert-success {
            color: #166534;
            background: #dcfce7;
            border-color: #bbf7d0;
        }

        .alert-danger {
            color: #991b1b;
            background: #fee2e2;
            border-color: #fecaca;
        }

        .empty-state {
            padding: 25px 15px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }

        [hidden] {
            display: none !important;
        }

        @media (max-width: 700px) {
            body {
                padding: 18px 12px;
            }

            .card {
                padding: 19px;
                border-radius: 14px;
            }

            .two-columns,
            .mode-box {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .mode-box {
                gap: 10px;
            }

            .page-header > .btn {
                width: 100%;
            }

            .submit-row .btn {
                width: 100%;
            }

            .search-box {
                max-width: none;
            }
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
                    Thiết lập đề thi và lựa chọn câu hỏi môn Lịch sử THCS.
                </p>
            </div>
        </div>

        <a href="../index.php" class="btn btn-secondary">
            🏠 Trang chủ
        </a>
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
                    <p class="section-description">
                        Đặt tên đề thi, chọn khối lớp và thời gian làm bài.
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label for="title">Tên đề thi</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    placeholder="Ví dụ: Đề thi giữa kỳ I - Lịch sử 9"
                    maxlength="255"
                    required
                >
            </div>

            <div class="two-columns">
                <div class="form-group">
                    <label for="gradeSelect">Khối lớp</label>
                    <select name="grade" id="gradeSelect">
                        <?php for ($g = 6; $g <= 9; $g++): ?>
                            <option
                                value="<?= $g ?>"
                                <?= $g === 9 ? 'selected' : '' ?>
                            >
                                Lớp <?= $g ?> — <?= $questionCounts[$g] ?> câu hỏi
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="duration">Thời gian làm bài (phút)</label>
                    <input
                        type="number"
                        id="duration"
                        name="duration"
                        value="15"
                        min="1"
                        max="300"
                        required
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
                    <p class="section-description">
                        Chọn câu hỏi cụ thể hoặc để hệ thống bốc ngẫu nhiên.
                    </p>
                </div>
            </div>

            <div class="mode-box">
                <div class="mode-option">
                    <input
                        type="radio"
                        id="modeManual"
                        name="create_mode"
                        value="manual"
                        checked
                    >
                    <label for="modeManual">
                        <strong>✍️ Chọn thủ công</strong>
                        <small>Tự chọn từng câu hỏi muốn đưa vào đề thi.</small>
                    </label>
                </div>

                <div class="mode-option">
                    <input
                        type="radio"
                        id="modeAuto"
                        name="create_mode"
                        value="auto"
                    >
                    <label for="modeAuto">
                        <strong>⚡ Bốc câu hỏi ngẫu nhiên</strong>
                        <small>Hệ thống tự chọn câu hỏi theo khối lớp.</small>
                    </label>
                </div>
            </div>

            <!-- CHỌN THỦ CÔNG -->
            <div id="manualSection">
                <div class="question-toolbar">
                    <div>
                        <label style="margin-bottom: 5px;">
                            Ngân hàng câu hỏi
                        </label>
                        <span class="question-count">
                            Đã chọn <span id="selectedCount">0</span> câu
                        </span>
                    </div>

                    <input
                        type="text"
                        id="questionSearch"
                        class="search-box"
                        placeholder="🔎 Tìm nội dung câu hỏi..."
                    >
                </div>

                <div class="q-list" id="questionList">
                    <?php if (!empty($questionsBank)): ?>

                        <?php foreach ($questionsBank as $q): ?>
                            <div
                                class="q-item question-row"
                                data-grade="<?= (int)$q['grade'] ?>"
                                data-search="<?= htmlspecialchars(
                                    mb_strtolower(
                                        $q['content'] . ' ' . $q['topic_name'],
                                        'UTF-8'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <input
                                    type="checkbox"
                                    name="question_ids[]"
                                    value="<?= (int)$q['question_id'] ?>"
                                    id="q_<?= (int)$q['question_id'] ?>"
                                    class="question-checkbox"
                                >

                                <label for="q_<?= (int)$q['question_id'] ?>">
                                    <span class="q-grade">
                                        Lớp <?= (int)$q['grade'] ?>
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $q['topic_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>

                                    <br>

                                    <?= htmlspecialchars(
                                        $q['content'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-state">
                            Chưa có câu hỏi trong ngân hàng.
                            Hãy thêm câu hỏi trước khi tạo đề thi.
                        </div>

                    <?php endif; ?>
                </div>

                <p class="section-description" style="margin-top: 12px;">
                    Chỉ những câu hỏi thuộc khối lớp đã chọn mới được hiển thị.
                </p>
            </div>

            <!-- BỐC NGẪU NHIÊN -->
            <div id="autoSection" hidden>
                <div class="auto-panel">
                    <label for="autoCount">
                        Số lượng câu hỏi muốn tạo
                    </label>

                    <p>
                        Hệ thống sẽ lấy ngẫu nhiên các câu hỏi trong ngân hàng
                        của khối lớp đã chọn. Số lượng thực tế không vượt quá
                        số câu hỏi hiện có.
                    </p>

                    <input
                        class="auto-count-input"
                        type="number"
                        id="autoCount"
                        name="auto_count"
                        value="10"
                        min="1"
                        max="100"
                        disabled
                    >
                </div>
            </div>

            <div class="submit-row">
                <button type="submit" class="btn btn-submit">
                    💾 Lưu đề thi
                </button>
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

    const autoCount = document.getElementById('autoCount');
    const questionSearch = document.getElementById('questionSearch');
    const selectedCount = document.getElementById('selectedCount');

    function toggleMode() {
        const isManual = modeManual.checked;

        manualSection.hidden = !isManual;
        autoSection.hidden = isManual;

        // Tránh gửi danh sách checkbox khi chọn chế độ tự động.
        document.querySelectorAll('.question-checkbox').forEach(checkbox => {
            checkbox.disabled = !isManual;
        });

        autoCount.disabled = isManual;

        updateQuestionList();
        updateSelectedCount();
    }

    function updateQuestionList() {
        const selectedGrade = gradeSelect.value;
        const keyword = questionSearch.value.trim().toLocaleLowerCase('vi');

        document.querySelectorAll('.question-row').forEach(row => {
            const sameGrade = row.dataset.grade === selectedGrade;
            const content = row.dataset.search || '';
            const matchesSearch = content.includes(keyword);

            row.hidden = !(sameGrade && matchesSearch);
        });
    }

    function updateSelectedCount() {
        const count = document.querySelectorAll(
            '.question-checkbox:checked:not(:disabled)'
        ).length;

        selectedCount.textContent = count;
    }

    gradeSelect.addEventListener('change', updateQuestionList);
    questionSearch.addEventListener('input', updateQuestionList);

    modeManual.addEventListener('change', toggleMode);
    modeAuto.addEventListener('change', toggleMode);

    document.querySelectorAll('.question-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    document.getElementById('examForm').addEventListener('submit', function(event) {
        if (modeManual.checked && selectedCount.textContent === '0') {
            event.preventDefault();
            alert('Vui lòng chọn ít nhất một câu hỏi để tạo đề thi.');
            return;
        }

        if (modeAuto.checked) {
            const count = Number(autoCount.value);

            if (!Number.isInteger(count) || count < 1 || count > 100) {
                event.preventDefault();
                alert('Số lượng câu hỏi phải từ 1 đến 100.');
            }
        }
    });

    toggleMode();
</script>

</body>
</html>

