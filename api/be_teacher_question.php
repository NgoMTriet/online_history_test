<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\IOFactory;

// LẤY USER_ID CỦA GIÁO VIÊN ĐANG ĐĂNG NHẬP
$userId = (int)($_SESSION['user_id'] ?? 0);

$message = '';
$selected_grade = (int)($_POST['grade'] ?? $_GET['grade'] ?? 9);
if (!in_array($selected_grade, [6, 7, 8, 9], true)) {
    $selected_grade = 9;
}

$selected_topic_id = (int)($_POST['topic_id'] ?? $_GET['topic_id'] ?? 0);
$selected_subject_id = (int)($_POST['subject_id'] ?? $_GET['subject_id'] ?? 0);

function showMessage(string $type, string $text): string
{
    return '<div class="alert alert-' . $type . '">'
        . htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        . '</div>';
}

// ĐÃ SỬA LỖI PDO VÀ THÊM THAM SỐ $userId
function saveQuestion(PDO $conn, int $userId, int $topicId, string $question, array $options, string $correct, string $explanation, string $difficulty = 'EASY'): void {
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

    if ($userId <= 0) { throw new Exception('Phiên đăng nhập không hợp lệ.'); }
    if ($question === '') { throw new Exception('Nội dung câu hỏi không được để trống.'); }
    if (count($options) < 2) { throw new Exception('Mỗi câu hỏi cần ít nhất 2 đáp án.'); }
    if (!isset($options[$correct])) { throw new Exception('Đáp án đúng chưa được nhập hoặc không khớp.'); }

    // ĐÃ SỬA: Chèn thêm user_id theo đúng chuẩn PDO
    $stmt = $conn->prepare("INSERT INTO questions (topic_id, user_id, content, difficulty, explanation) VALUES (:topic_id, :user_id, :content, :difficulty, :explanation)");
    $stmt->execute([
        'topic_id' => $topicId,
        'user_id' => $userId,
        'content' => $question,
        'difficulty' => $difficulty,
        'explanation' => $explanation
    ]);

    $questionId = (int) $conn->lastInsertId();

    $stmtAnswer = $conn->prepare("INSERT INTO answers (question_id, option_code, content, is_correct) VALUES (:question_id, :option_code, :content, :is_correct)");
    foreach ($options as $code => $content) {
        $stmtAnswer->execute([
            'question_id' => $questionId,
            'option_code' => $code,
            'content' => trim((string) $content),
            'is_correct' => ($code === $correct) ? 1 : 0
        ]);
    }
}

// ---------- Môn học ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_subject') {
    $newSubjectName = trim($_POST['new_subject_name'] ?? '');

    if ($newSubjectName === '') {
        $message = showMessage('danger', 'Vui lòng nhập tên môn học.');
    } else {
        try {
            $stmt = $conn->prepare("SELECT subject_id FROM subjects WHERE subject_name = :name AND grade = :grade LIMIT 1");
            $stmt->execute(['name' => $newSubjectName, 'grade' => $selected_grade]);
            $existingSubjectId = $stmt->fetchColumn();

            if ($existingSubjectId) {
                $selected_subject_id = (int)$existingSubjectId;
                $message = showMessage('warning', 'Môn học đã tồn tại. Hệ thống đã chọn môn đó.');
            } else {
                $stmt = $conn->prepare("INSERT INTO subjects (subject_name, grade) VALUES (:name, :grade)");
                $stmt->execute(['name' => $newSubjectName, 'grade' => $selected_grade]);
                $selected_subject_id = (int)$conn->lastInsertId();
                $selected_topic_id = 0;
                $message = showMessage('success', 'Tạo môn học mới thành công!');
            }
        } catch (PDOException $e) {
            $message = showMessage('danger', 'Không thể tạo môn học. Vui lòng kiểm tra lại dữ liệu.');
        }
    }
}

$stmt = $conn->prepare("SELECT subject_id, subject_name FROM subjects WHERE grade = :grade ORDER BY subject_name ASC");
$stmt->execute(['grade' => $selected_grade]);
$subjects_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subject_ids = array_map('intval', array_column($subjects_list, 'subject_id'));
if (!in_array($selected_subject_id, $subject_ids, true)) {
    $selected_subject_id = $subject_ids[0] ?? 0;
}

$current_subject_name = '';
foreach ($subjects_list as $subjectRow) {
    if ((int)$subjectRow['subject_id'] === $selected_subject_id) {
        $current_subject_name = $subjectRow['subject_name'];
        break;
    }
}

// ---------- Chủ đề (TOPICS) - ĐÃ TÁCH RIÊNG THEO USER_ID ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_topic') {
    $newTopicName = trim($_POST['new_topic_name'] ?? '');

    if ($newTopicName === '') {
        $message = showMessage('danger', 'Vui lòng nhập tên chủ đề.');
    } elseif ($selected_subject_id <= 0) {
        $message = showMessage('danger', 'Vui lòng chọn hoặc tạo môn học trước khi tạo chủ đề.');
    } else {
        try {
            // Kiểm tra trùng tên trong phạm vi chủ đề của chính giáo viên này
            $stmt = $conn->prepare("SELECT topic_id FROM topics WHERE topic_name = :name AND grade = :grade AND subject_id = :subject_id AND user_id = :user_id LIMIT 1");
            $stmt->execute(['name' => $newTopicName, 'grade' => $selected_grade, 'subject_id' => $selected_subject_id, 'user_id' => $userId]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                $selected_topic_id = (int)$existingId;
                $message = showMessage('warning', 'Chủ đề đã tồn tại. Hệ thống đã chọn chủ đề đó.');
            } else {
                // Lưu thêm user_id khi tạo chủ đề mới
                $stmt = $conn->prepare("INSERT INTO topics (topic_name, grade, subject_id, user_id) VALUES (:name, :grade, :subject_id, :user_id)");
                $stmt->execute(['name' => $newTopicName, 'grade' => $selected_grade, 'subject_id' => $selected_subject_id, 'user_id' => $userId]);
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
    // Chỉ cho phép chọn chủ đề thuộc về giáo viên đang đăng nhập
    $stmt = $conn->prepare("SELECT topic_id, topic_name, grade FROM topics WHERE topic_id = :topic_id AND grade = :grade AND subject_id = :subject_id AND user_id = :user_id LIMIT 1");
    $stmt->execute(['topic_id' => $selected_topic_id, 'grade' => $selected_grade, 'subject_id' => $selected_subject_id, 'user_id' => $userId]);
    $current_topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_topic) { $selected_topic_id = 0; }
}

// ---------- Thêm câu hỏi thủ công ----------
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
            saveQuestion($conn, $userId, $selected_topic_id, $question, $options, $correct, $explanation, $difficulty);
            $conn->commit();
            $message = showMessage('success', 'Thêm câu hỏi thủ công thành công!');
        } catch (Throwable $e) {
            if ($conn->inTransaction()) { $conn->rollBack(); }
            $message = showMessage('danger', $e instanceof Exception ? $e->getMessage() : 'Có lỗi xảy ra khi lưu câu hỏi.');
        }
    }
}

// ---------- Import Excel ----------
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
                        // Kiểm tra chủ đề có thuộc về giáo viên đang đăng nhập không
                        $checkTopic = $conn->prepare('SELECT topic_id FROM topics WHERE topic_id = :id AND user_id = :user_id LIMIT 1');
                        $checkTopic->execute(['id' => $importTopicId, 'user_id' => $userId]);
                        if (!$checkTopic->fetchColumn()) { throw new Exception('Chủ đề ID ' . $importTopicId . ' không tồn tại hoặc không thuộc quyền quản lý của bạn.'); }

                        saveQuestion($conn, $userId, $importTopicId, $question, $options, $correct, $explanation, $difficulty);
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

// ĐÃ SỬA: Chỉ lấy danh sách chủ đề của giáo viên đang đăng nhập
$stmt = $conn->prepare("SELECT topic_id, topic_name FROM topics WHERE grade = :grade AND subject_id = :subject_id AND user_id = :user_id ORDER BY topic_id DESC");
$stmt->execute(['grade' => $selected_grade, 'subject_id' => $selected_subject_id, 'user_id' => $userId]);
$topics_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ĐÃ SỬA: Chỉ lấy danh sách câu hỏi của giáo viên đang đăng nhập
$stmt = $conn->prepare("SELECT q.question_id, q.content, t.grade, t.topic_name, s.subject_name FROM questions q JOIN topics t ON q.topic_id = t.topic_id LEFT JOIN subjects s ON t.subject_id = s.subject_id WHERE q.user_id = :user_id ORDER BY q.question_id DESC LIMIT 50");
$stmt->execute(['user_id' => $userId]);
$questions_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
