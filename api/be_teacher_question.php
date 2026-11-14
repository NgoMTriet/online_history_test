<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

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

function showMessage(string $type, string $text): string
{
    return '<div class="alert alert-' . $type . '">'
        . htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        . '</div>';
}

function saveQuestion(PDO $conn, int $topicId, string $question, array $options, string $correct, string $explanation, string $difficulty = 'EASY'): void {
    $options = array_filter($options, fn($value) => trim((string)$value) !== '');

    $difficultyRaw = trim((string)$difficulty);
    $difficultyText = mb_strtolower($difficultyRaw, 'UTF-8');
    $difficultyText = preg_replace('/\s+/u', ' ', $difficultyText) ?? $difficultyText;

    $difficultyMap = [
        'easy' => 'EASY', 'dễ' => 'EASY', 'de' => 'EASY',
        'medium' => 'MEDIUM', 'trung bình' => 'MEDIUM', 'trung binh' => 'MEDIUM',
        'hard' => 'HARD', 'khó' => 'HARD', 'kho' => 'HARD'
    ];

    if (isset($difficultyMap[$difficultyText])) {
        $difficulty = $difficultyMap[$difficultyText];
    } else {
        $folded = str_replace(['đ', 'Đ'], 'd', $difficultyText);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $folded);
        $ascii = strtolower(trim($ascii === false ? $folded : $ascii));
        $difficulty = $difficultyMap[$ascii] ?? '';
    }

    if (!in_array($difficulty, ['EASY', 'MEDIUM', 'HARD'], true)) {
        throw new Exception('Độ khó không hợp lệ: ' . $difficultyRaw);
    }

    if ($question === '') { throw new Exception('Nội dung câu hỏi không được để trống.'); }
    if (count($options) < 2) { throw new Exception('Mỗi câu hỏi cần ít nhất 2 đáp án.'); }
    if (!isset($options[$correct])) { throw new Exception('Đáp án đúng chưa được nhập hoặc không khớp.'); }

    $stmt = $conn->prepare("INSERT INTO questions (topic_id, content, difficulty, explanation) VALUES (:topic_id, :content, :difficulty, :explanation)");
    $stmt->execute([
        'topic_id' => $topicId,
        'content' => $question,
        'difficulty' => $difficulty,
        'explanation' => $explanation
    ]);

    $questionId = (int) $conn->lastInsertId();

    $stmtAnswer = $conn->prepare("INSERT INTO answers (question_id, option_code, content, is_correct, is_fixed) VALUES (:question_id, :option_code, :content, :is_correct, 0)");
    foreach ($options as $code => $content) {
        $stmtAnswer->execute([
            'question_id' => $questionId,
            'option_code' => $code,
            'content' => trim((string) $content),
            'is_correct' => ($code === $correct) ? 1 : 0
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_topic') {
    $newTopicName = trim($_POST['new_topic_name'] ?? '');

    if ($newTopicName === '') {
        $message = showMessage('danger', 'Vui lòng nhập tên chủ đề.');
    } else {
        try {
            $stmt = $conn->prepare("SELECT topic_id FROM topics WHERE topic_name = :name AND grade = :grade LIMIT 1");
            $stmt->execute(['name' => $newTopicName, 'grade' => $selected_grade]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                $selected_topic_id = (int)$existingId;
                $message = showMessage('warning', 'Chủ đề đã tồn tại. Hệ thống đã chọn chủ đề đó.');
            } else {
                $stmt = $conn->prepare("INSERT INTO topics (topic_name, grade) VALUES (:name, :grade)");
                $stmt->execute(['name' => $newTopicName, 'grade' => $selected_grade]);
                $selected_topic_id = (int)$conn->lastInsertId();
                $message = showMessage('success', 'Tạo chủ đề mới thành công!');
            }
        } catch (PDOException $e) {
            $message = showMessage('danger', 'Không thể tạo chủ đề. Vui lòng kiểm tra lại dữ liệu.');
        }
    }
}

$current_topic = null;
if ($selected_topic_id > 0) {
    $stmt = $conn->prepare("SELECT topic_id, topic_name, grade FROM topics WHERE topic_id = :topic_id AND grade = :grade LIMIT 1");
    $stmt->execute(['topic_id' => $selected_topic_id, 'grade' => $selected_grade]);
    $current_topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_topic) { $selected_topic_id = 0; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_manual') {
    if (!$current_topic) {
        $message = showMessage('danger', 'Vui lòng chọn một chủ đề hợp lệ trước khi thêm câu hỏi.');
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
        $difficulty = strtoupper(trim($_POST['difficulty'] ?? 'EASY'));

        try {
            $conn->beginTransaction();
            saveQuestion($conn, $selected_topic_id, $question, $options, $correct, $explanation, $difficulty);
            $conn->commit();
            $message = showMessage('success', 'Thêm câu hỏi thủ công thành công!');
        } catch (Throwable $e) {
            if ($conn->inTransaction()) { $conn->rollBack(); }
            $message = showMessage('danger', $e instanceof Exception ? $e->getMessage() : 'Có lỗi xảy ra khi lưu câu hỏi.');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_excel') {
    if (!$current_topic || !isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $message = showMessage('danger', 'Vui lòng chọn chủ đề và tải lên file Excel hợp lệ.');
    } elseif (!class_exists(IOFactory::class)) {
        $message = showMessage('danger', 'Chưa tìm thấy PhpSpreadsheet. Vui lòng cài thư viện trước khi import.');
    } else {
        $file = $_FILES['excel_file'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            $message = showMessage('danger', 'Chỉ chấp nhận file .xlsx, .xls hoặc .csv.');
        } else {
            try {
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                $header = array_map(static function ($value): string {
                    $value = trim((string)$value);
                    $value = mb_strtolower($value, 'UTF-8');
                    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
                    $ascii = $ascii === false ? $value : $ascii;
                    return preg_replace('/[^a-z0-9]+/', '', strtolower($ascii));
                }, $rows[0] ?? []);

                $findColumn = static function (array $aliases) use ($header): ?int {
                    foreach ($header as $columnIndex => $label) {
                        if (in_array($label, $aliases, true)) { return (int)$columnIndex; }
                    }
                    return null;
                };

                $questionCol = $findColumn(['noidungcauhoi', 'cauhoi', 'question', 'content']) ?? 0;
                $optionACol = $findColumn(['dapana', 'optiona', 'answera']) ?? 1;
                $optionBCol = $findColumn(['dapanb', 'optionb', 'answerb']) ?? 2;
                $optionCCol = $findColumn(['dapanc', 'optionc', 'answerc']) ?? 3;
                $optionDCol = $findColumn(['dapand', 'optiond', 'answerd']) ?? 4;
                $correctCol = $findColumn(['dapandungabcd', 'dapandung', 'correctanswer', 'correctoption']) ?? 5;
                $explanationCol = $findColumn(['loigiaichitiet', 'loigiai', 'explanation']) ?? 8;
                $difficultyCol = $findColumn(['dokho', 'dokhocauhoi', 'difficulty', 'mucdo', 'mucdokho', 'mucdocauhoi', 'level']) ?? 6;
                $topicIdCol = $findColumn(['chudeid', 'topicid', 'idchude', 'topic']);

                $count = 0;
                $errors = [];
                $conn->beginTransaction();

                foreach ($rows as $index => $row) {
                    if ($index === 0) continue;

                    $question = trim((string)($row[$questionCol] ?? ''));
                    $options = [
                        'A' => trim((string)($row[$optionACol] ?? '')),
                        'B' => trim((string)($row[$optionBCol] ?? '')),
                        'C' => trim((string)($row[$optionCCol] ?? '')),
                        'D' => trim((string)($row[$optionDCol] ?? ''))
                    ];
                    $correct = strtoupper(trim((string)($row[$correctCol] ?? '')));
                    $explanation = trim((string)($row[$explanationCol] ?? ''));
                    $difficulty = trim((string)($row[$difficultyCol] ?? ''));
                    $rowTopicId = $topicIdCol !== null ? (int)($row[$topicIdCol] ?? 0) : 0;
                    $importTopicId = $rowTopicId > 0 ? $rowTopicId : $selected_topic_id;

                    if ($difficulty === '') { $difficulty = 'EASY'; }
                    if ($question === '' && count(array_filter($options)) === 0) { continue; }

                    try {
                        if ($importTopicId <= 0) { throw new Exception('Chưa chọn chủ đề hợp lệ.'); }
                        $checkTopic = $conn->prepare('SELECT topic_id FROM topics WHERE topic_id = :id LIMIT 1');
                        $checkTopic->execute(['id' => $importTopicId]);
                        if (!$checkTopic->fetchColumn()) { throw new Exception('Chủ đề ID ' . $importTopicId . ' không tồn tại.'); }

                        saveQuestion($conn, $importTopicId, $question, $options, $correct, $explanation, $difficulty);
                        $count++;
                    } catch (Throwable $e) {
                        $errors[] = 'Dòng ' . ($index + 1) . ': ' . $e->getMessage();
                    }
                }

                if ($count === 0 || !empty($errors)) {
                    $conn->rollBack();
                    $message = showMessage('danger', 'Import thất bại: ' . implode(' | ', array_slice($errors, 0, 5)));
                } else {
                    $conn->commit();
                    $message = showMessage('success', "Import thành công {$count} câu hỏi!");
                }

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            } catch (Throwable $e) {
                if ($conn->inTransaction()) { $conn->rollBack(); }
                $message = showMessage('danger', 'Không thể xử lý file Excel: ' . $e->getMessage());
            }
        }
    }
}

$stmt = $conn->prepare("SELECT topic_id, topic_name FROM topics WHERE grade = :grade ORDER BY topic_id DESC");
$stmt->execute(['grade' => $selected_grade]);
$topics_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT q.question_id, q.content, t.grade, t.topic_name FROM questions q JOIN topics t ON q.topic_id = t.topic_id ORDER BY q.question_id DESC LIMIT 50");
$questions_list = $stmt->fetchAll(PDO::FETCH_ASSOC);