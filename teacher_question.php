<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\IOFactory;

$message = '';

$selected_grade = (int)($_POST['grade'] ?? $_GET['grade'] ?? 9);
if (!in_array($selected_grade, [6, 7, 8, 9], true)) {
    $selected_grade = 9;
}

$selected_topic_id = (int)($_POST['topic_id'] ?? $_GET['topic_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| HÀM HIỂN THỊ THÔNG BÁO
|--------------------------------------------------------------------------
*/
function showMessage(string $type, string $text): string
{
    return '<div class="alert alert-' . $type . '">'
        . htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        . '</div>';
}

/*
|--------------------------------------------------------------------------
| HÀM LƯU CÂU HỎI VÀ CÁC ĐÁP ÁN
|--------------------------------------------------------------------------
*/
function saveQuestion(
    PDO $conn,
    int $topicId,
    string $question,
    array $options,
    string $correct,
    string $explanation
): void {
    $options = array_filter(
        $options,
        fn($value) => trim((string)$value) !== ''
    );

    if ($question === '') {
        throw new Exception('Nội dung câu hỏi không được để trống.');
    }

    if (count($options) < 2) {
        throw new Exception('Mỗi câu hỏi cần ít nhất 2 đáp án.');
    }

    if (!isset($options[$correct])) {
        throw new Exception('Đáp án đúng chưa được nhập.');
    }

    $stmt = $conn->prepare(
        "INSERT INTO questions (topic_id, content, difficulty, explanation)
         VALUES (:topic_id, :content, 'EASY', :explanation)"
    );

    $stmt->execute([
        'topic_id' => $topicId,
        'content' => $question,
        'explanation' => $explanation
    ]);

    $questionId = (int)$conn->lastInsertId();

    $stmtAnswer = $conn->prepare(
        "INSERT INTO answers
         (question_id, option_code, content, is_correct, is_fixed)
         VALUES (:question_id, :option_code, :content, :is_correct, 0)"
    );

    foreach ($options as $code => $content) {
        $stmtAnswer->execute([
            'question_id' => $questionId,
            'option_code' => $code,
            'content' => trim((string)$content),
            'is_correct' => ($code === $correct) ? 1 : 0
        ]);
    }
}

/*
|--------------------------------------------------------------------------
| BƯỚC 1: TẠO CHỦ ĐỀ
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'create_topic'
) {
    $newTopicName = trim($_POST['new_topic_name'] ?? '');

    if ($newTopicName === '') {
        $message = showMessage('danger', 'Vui lòng nhập tên chủ đề.');
    } else {
        try {
            $stmt = $conn->prepare(
                "SELECT topic_id FROM topics
                 WHERE topic_name = :name AND grade = :grade LIMIT 1"
            );

            $stmt->execute([
                'name' => $newTopicName,
                'grade' => $selected_grade
            ]);

            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                $selected_topic_id = (int)$existingId;
                $message = showMessage(
                    'warning',
                    'Chủ đề đã tồn tại. Hệ thống đã chọn chủ đề đó.'
                );
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO topics (topic_name, grade)
                     VALUES (:name, :grade)"
                );

                $stmt->execute([
                    'name' => $newTopicName,
                    'grade' => $selected_grade
                ]);

                $selected_topic_id = (int)$conn->lastInsertId();

                $message = showMessage(
                    'success',
                    'Tạo chủ đề mới thành công!'
                );
            }
        } catch (PDOException $e) {
            $message = showMessage(
                'danger',
                'Không thể tạo chủ đề. Vui lòng kiểm tra lại dữ liệu.'
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| KIỂM TRA CHỦ ĐỀ ĐƯỢC CHỌN CÓ THUỘC ĐÚNG LỚP KHÔNG
|--------------------------------------------------------------------------
*/
$current_topic = null;

if ($selected_topic_id > 0) {
    $stmt = $conn->prepare(
        "SELECT topic_id, topic_name, grade
         FROM topics
         WHERE topic_id = :topic_id AND grade = :grade
         LIMIT 1"
    );

    $stmt->execute([
        'topic_id' => $selected_topic_id,
        'grade' => $selected_grade
    ]);

    $current_topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_topic) {
        $selected_topic_id = 0;
    }
}

/*
|--------------------------------------------------------------------------
| BƯỚC 2A: THÊM CÂU HỎI THỦ CÔNG
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'add_manual'
) {
    if (!$current_topic) {
        $message = showMessage(
            'danger',
            'Vui lòng chọn một chủ đề hợp lệ trước khi thêm câu hỏi.'
        );
    } else {
        $question = trim($_POST['question'] ?? '');

        $options = [
            'A' => trim($_POST['option_a'] ?? ''),
            'B' => trim($_POST['option_b'] ?? ''),
            'C' => trim($_POST['option_c'] ?? ''),
            'D' => trim($_POST['option_d'] ?? '')
        ];

        $correct = strtoupper(trim($_POST['correct_option'] ?? 'A'));
        $explanation = trim($_POST['explanation'] ?? '');

        try {
            $conn->beginTransaction();

            saveQuestion(
                $conn,
                $selected_topic_id,
                $question,
                $options,
                $correct,
                $explanation
            );

            $conn->commit();

            $message = showMessage(
                'success',
                'Thêm câu hỏi thủ công thành công!'
            );
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $message = showMessage(
                'danger',
                $e instanceof Exception
                    ? $e->getMessage()
                    : 'Có lỗi xảy ra khi lưu câu hỏi.'
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| BƯỚC 2B: IMPORT EXCEL
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'import_excel'
) {
    if (
        !$current_topic
        || !isset($_FILES['excel_file'])
        || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK
    ) {
        $message = showMessage(
            'danger',
            'Vui lòng chọn chủ đề và tải lên file Excel hợp lệ.'
        );
    } elseif (!class_exists(IOFactory::class)) {
        $message = showMessage(
            'danger',
            'Chưa tìm thấy PhpSpreadsheet. Vui lòng cài thư viện trước khi import.'
        );
    } else {
        $file = $_FILES['excel_file'];

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        if (!in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            $message = showMessage(
                'danger',
                'Chỉ chấp nhận file .xlsx, .xls hoặc .csv.'
            );
        } else {
            try {
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                $count = 0;
                $errors = [];

                $conn->beginTransaction();

                foreach ($rows as $index => $row) {
                    // Bỏ qua hàng tiêu đề đầu tiên.
                    if ($index === 0) {
                        continue;
                    }

                    $question = trim((string)($row[0] ?? ''));

                    $options = [
                        'A' => trim((string)($row[1] ?? '')),
                        'B' => trim((string)($row[2] ?? '')),
                        'C' => trim((string)($row[3] ?? '')),
                        'D' => trim((string)($row[4] ?? ''))
                    ];

                    $correct = strtoupper(
                        trim((string)($row[5] ?? ''))
                    );

                    // Cột I tương ứng chỉ số 8.
                    $explanation = trim(
                        (string)($row[8] ?? '')
                    );

                    // Bỏ qua hàng hoàn toàn trống.
                    if (
                        $question === ''
                        && count(array_filter($options)) === 0
                    ) {
                        continue;
                    }

                    try {
                        saveQuestion(
                            $conn,
                            $selected_topic_id,
                            $question,
                            $options,
                            $correct,
                            $explanation
                        );

                        $count++;
                    } catch (Throwable $e) {
                        $errors[] = 'Dòng ' . ($index + 1)
                            . ': ' . $e->getMessage();
                    }
                }

                if ($count === 0) {
                    $conn->rollBack();

                    $message = showMessage(
                        'danger',
                        'Không có câu hỏi hợp lệ để import. Hãy kiểm tra file Excel.'
                    );
                } elseif (!empty($errors)) {
                    // Có lỗi thì hoàn tác toàn bộ để tránh import một phần.
                    $conn->rollBack();

                    $message = showMessage(
                        'danger',
                        'Import bị hủy vì có dòng không hợp lệ. '
                        . implode(' | ', array_slice($errors, 0, 5))
                    );
                } else {
                    $conn->commit();

                    $message = showMessage(
                        'success',
                        "Import thành công {$count} câu hỏi!"
                    );
                }

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            } catch (Throwable $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                $message = showMessage(
                    'danger',
                    'Không thể xử lý file Excel. Hãy kiểm tra cấu trúc file và dữ liệu.'
                );
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH CHỦ ĐỀ THEO LỚP
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare(
    "SELECT topic_id, topic_name
     FROM topics
     WHERE grade = :grade
     ORDER BY topic_id DESC"
);

$stmt->execute(['grade' => $selected_grade]);
$topics_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH CÂU HỎI GẦN ĐÂY
|--------------------------------------------------------------------------
*/
$stmt = $conn->query(
    "SELECT q.question_id, q.content, t.grade, t.topic_name
     FROM questions q
     JOIN topics t ON q.topic_id = t.topic_id
     ORDER BY q.question_id DESC
     LIMIT 50"
);

$questions_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý ngân hàng câu hỏi | TestSuViet</title>

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #15803d;
            --warning: #b45309;
            --danger: #b91c1c;
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

        input, select, textarea, button {
            font: inherit;
        }

        .container {
            max-width: 1180px;
            margin: 0 auto;
        }

        .header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-icon {
            display: grid;
            place-items: center;
            width: 54px;
            height: 54px;
            flex-shrink: 0;
            border-radius: 16px;
            background: #dbeafe;
            font-size: 27px;
        }

        h1 {
            margin: 0;
            font-size: clamp(22px, 3vw, 30px);
            line-height: 1.3;
            color: #0f172a;
        }

        .subtitle {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .card {
            background: var(--white);
            padding: 26px;
            border: 1px solid #e8edf4;
            border-radius: 18px;
            box-shadow: 0 6px 22px rgba(15, 23, 42, .045);
            margin-bottom: 24px;
        }

        .card-heading {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            margin-bottom: 22px;
        }

        .step-number {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            background: #dbeafe;
            color: var(--primary-dark);
            font-weight: 800;
            border-radius: 12px;
        }

        h2 {
            font-size: 19px;
            margin: 0;
            line-height: 1.5;
            color: #0f172a;
        }

        .section-description {
            color: var(--muted);
            font-size: 13px;
            margin: 4px 0 0;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .topic-create {
            padding-left: 22px;
            border-left: 1px solid var(--border);
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #334155;
        }

        input[type="text"],
        input[type="file"],
        select,
        textarea {
            display: block;
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 10px;
            padding: 11px 18px;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s, background .15s;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(15, 23, 42, .12);
        }

        .btn-primary { background: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); }

        .btn-success { background: #16a34a; }
        .btn-success:hover { background: #15803d; }

        .btn-warning { background: #d97706; }
        .btn-warning:hover { background: #b45309; }

        .btn-secondary { background: #475569; }
        .btn-secondary:hover { background: #334155; }

        .alert {
            padding: 15px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            border: 1px solid transparent;
            font-size: 14px;
        }

        .alert-success {
            color: #166534;
            background: #dcfce7;
            border-color: #bbf7d0;
        }

        .alert-warning {
            color: #92400e;
            background: #fef3c7;
            border-color: #fde68a;
        }

        .alert-danger {
            color: #991b1b;
            background: #fee2e2;
            border-color: #fecaca;
        }

        .alert-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #eff6ff;
            color: #1e40af;
            border-color: #bfdbfe;
        }

        .file-hint {
            margin-top: 12px;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            color: #475569;
            font-size: 13px;
        }

        .file-hint strong {
            color: #1e293b;
        }

        .option-label {
            display: inline-block;
            color: var(--primary-dark);
            background: #dbeafe;
            border-radius: 6px;
            padding: 2px 8px;
            margin-right: 5px;
        }

        .badge, .badge-topic {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-topic {
            background: #dcfce7;
            color: #166534;
        }

        .table-wrapper {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
            font-size: 14px;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8fbff;
        }

        .question-content {
            min-width: 260px;
            max-width: 520px;
            overflow-wrap: anywhere;
        }

        .empty-state {
            text-align: center;
            color: var(--muted);
            padding: 25px;
            background: #f8fafc;
            border-radius: 12px;
        }

        .form-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        @media (max-width: 760px) {
            body {
                padding: 18px 12px;
            }

            .card {
                padding: 19px;
                border-radius: 14px;
            }

            .grid-2 {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .topic-create {
                padding: 20px 0 0;
                border-left: 0;
                border-top: 1px solid var(--border);
                margin-top: 8px;
            }

            .header-bar {
                align-items: flex-start;
            }

            .header-bar > .btn {
                width: 100%;
            }

            .form-footer .btn,
            .form-group > .btn {
                width: 100%;
            }
        }

        @media (max-width: 420px) {
            .header-icon {
                width: 44px;
                height: 44px;
                font-size: 23px;
            }

            h1 {
                font-size: 21px;
            }

            h2 {
                font-size: 17px;
            }
        }
    </style>

    <script>
        function onGradeChange(select) {
            window.location.href = '?grade=' + encodeURIComponent(select.value);
        }

        function onTopicSelect(select) {
            const grade = document.getElementById('grade_select').value;
            const topicId = select.value;

            window.location.href =
                '?grade=' + encodeURIComponent(grade) +
                '&topic_id=' + encodeURIComponent(topicId);
        }
    </script>
</head>

<body>
<div class="container">

    <header class="header-bar">
        <div class="header-title">
            <div class="header-icon">📚</div>
            <div>
                <h1>Ngân hàng câu hỏi</h1>
                <p class="subtitle">
                    Quản lý chủ đề, nhập câu hỏi và đáp án môn Lịch sử THCS
                </p>
            </div>
        </div>

        <a href="../index.php" class="btn btn-secondary">
            🏠 Trang chủ
        </a>
    </header>

    <?= $message ?>

    <!-- BƯỚC 1: CHỌN VÀ TẠO CHỦ ĐỀ -->
    <section class="card">
        <div class="card-heading">
            <div class="step-number">01</div>
            <div>
                <h2>Chọn hoặc tạo chủ đề</h2>
                <p class="section-description">
                    Chọn khối lớp trước khi nhập câu hỏi vào ngân hàng.
                </p>
            </div>
        </div>

        <div class="grid-2">
            <div>
                <div class="form-group">
                    <label for="grade_select">Khối lớp</label>

                    <select
                        id="grade_select"
                        onchange="onGradeChange(this)"
                    >
                        <?php for ($grade = 6; $grade <= 9; $grade++): ?>
                            <option
                                value="<?= $grade ?>"
                                <?= $selected_grade === $grade ? 'selected' : '' ?>
                            >
                                Lớp <?= $grade ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="topic_select">Chủ đề hiện có</label>

                    <select
                        id="topic_select"
                        onchange="onTopicSelect(this)"
                    >
                        <option value="0">-- Chọn chủ đề --</option>

                        <?php foreach ($topics_list as $topic): ?>
                            <option
                                value="<?= (int)$topic['topic_id'] ?>"
                                <?= $selected_topic_id === (int)$topic['topic_id']
                                    ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $topic['topic_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
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
                        <input
                            type="hidden"
                            name="topic_id"
                            value="<?= $selected_topic_id ?>"
                        >
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="new_topic_name">
                            Tạo chủ đề cho lớp <?= $selected_grade ?>
                        </label>

                        <input
                            type="text"
                            id="new_topic_name"
                            name="new_topic_name"
                            placeholder="Ví dụ: Việt Nam từ 1930 đến 1945"
                            maxlength="255"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-warning">
                        ＋ Tạo chủ đề mới
                    </button>
                </form>
            </div>
        </div>
    </section>

    <?php if ($current_topic): ?>

        <div class="alert alert-info">
            <span>🎯</span>
            <div>
                Đang làm việc với chủ đề
                <strong>
                    <?= htmlspecialchars(
                        $current_topic['topic_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
                — Lớp <?= (int)$current_topic['grade'] ?>
            </div>
        </div>

        <!-- BƯỚC 2A: IMPORT EXCEL -->
        <section class="card">
            <div class="card-heading">
                <div class="step-number">02A</div>
                <div>
                    <h2>Import câu hỏi từ Excel</h2>
                    <p class="section-description">
                        Thêm nhiều câu hỏi cùng lúc vào chủ đề đang chọn.
                    </p>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_excel">
                <input type="hidden" name="grade" value="<?= $selected_grade ?>">
                <input type="hidden" name="topic_id" value="<?= $selected_topic_id ?>">

                <div class="form-group">
                    <label for="excel_file">Chọn file Excel</label>
                    <input
                        type="file"
                        id="excel_file"
                        name="excel_file"
                        accept=".xlsx,.xls,.csv"
                        required
                    >
                </div>

                <div class="file-hint">
                    <strong>📌 Cấu trúc file Excel:</strong><br><br>
                    <span class="option-label">Cột A</span> Nội dung câu hỏi<br>
                    <span class="option-label">Cột B–E</span> Đáp án A, B, C, D<br>
                    <span class="option-label">Cột F</span> Đáp án đúng: A, B, C hoặc D<br>
                    <span class="option-label">Cột I</span> Lời giải chi tiết<br><br>
                    <strong>Lưu ý:</strong> Hàng đầu tiên là hàng tiêu đề. Mỗi câu hỏi phải có ít nhất hai đáp án và đáp án đúng phải tồn tại trong các đáp án đã nhập.
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-success">
                        📥 Import câu hỏi
                    </button>
                </div>
            </form>
        </section>

        <!-- BƯỚC 2B: NHẬP THỦ CÔNG -->
        <section class="card">
            <div class="card-heading">
                <div class="step-number">02B</div>
                <div>
                    <h2>Thêm câu hỏi thủ công</h2>
                    <p class="section-description">
                        Nhập từng câu hỏi, lựa chọn đáp án đúng và lời giải.
                    </p>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="add_manual">
                <input type="hidden" name="grade" value="<?= $selected_grade ?>">
                <input type="hidden" name="topic_id" value="<?= $selected_topic_id ?>">

                <div class="form-group">
                    <label for="question">Nội dung câu hỏi</label>
                    <textarea
                        id="question"
                        name="question"
                        rows="3"
                        placeholder="Nhập nội dung câu hỏi..."
                        required
                    ></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="option_a">Đáp án A</label>
                        <input
                            type="text"
                            id="option_a"
                            name="option_a"
                            placeholder="Nhập đáp án A"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="option_b">Đáp án B</label>
                        <input
                            type="text"
                            id="option_b"
                            name="option_b"
                            placeholder="Nhập đáp án B"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="option_c">Đáp án C (không bắt buộc)</label>
                        <input
                            type="text"
                            id="option_c"
                            name="option_c"
                            placeholder="Nhập đáp án C"
                        >
                    </div>

                    <div class="form-group">
                        <label for="option_d">Đáp án D (không bắt buộc)</label>
                        <input
                            type="text"
                            id="option_d"
                            name="option_d"
                            placeholder="Nhập đáp án D"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="correct_option">Đáp án đúng</label>
                    <select
                        id="correct_option"
                        name="correct_option"
                        style="max-width: 240px;"
                        required
                    >
                        <option value="A">Đáp án A</option>
                        <option value="B">Đáp án B</option>
                        <option value="C">Đáp án C</option>
                        <option value="D">Đáp án D</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="explanation">Lời giải chi tiết</label>
                    <textarea
                        id="explanation"
                        name="explanation"
                        rows="3"
                        placeholder="Giải thích vì sao đáp án được chọn là đúng..."
                    ></textarea>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">
                        💾 Lưu câu hỏi
                    </button>
                </div>
            </form>
        </section>

    <?php else: ?>

        <div class="alert alert-warning">
            ⚠️ Hãy chọn hoặc tạo chủ đề ở Bước 1 trước khi thêm câu hỏi.
        </div>

    <?php endif; ?>

    <!-- DANH SÁCH CÂU HỎI GẦN ĐÂY -->
    <section class="card">
        <div class="card-heading">
            <div class="step-number">03</div>
            <div>
                <h2>Câu hỏi mới thêm gần đây</h2>
                <p class="section-description">
                    Hiển thị tối đa 50 câu hỏi mới nhất trong hệ thống.
                </p>
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

                                <td>
                                    <span class="badge">
                                        Lớp <?= (int)$q['grade'] ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge-topic">
                                        <?= htmlspecialchars(
                                            $q['topic_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

                                <td class="question-content">
                                    <?= htmlspecialchars(
                                        $q['content'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <div class="empty-state">
                Chưa có câu hỏi nào trong ngân hàng câu hỏi.
            </div>

        <?php endif; ?>
    </section>

</div>
</body>
</html>