<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'submit_exam') {
    header("Location: take_exam.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$exam_id = (int) ($_POST['exam_id'] ?? 0);
$user_answers = $_POST['answers'] ?? [];

// 🎯 Lấy thời gian làm bài thực tế (tính bằng giây) từ POST do take_exam.php gửi sang
$completion_time = (int) ($_POST['completion_time'] ?? 0);

if ($exam_id <= 0) {
    exit("Mã đề thi không hợp lệ.");
}

// 1. Lấy thông tin đề thi
$stmt_exam = $conn->prepare("SELECT title FROM exam WHERE exam_id = :id");
$stmt_exam->execute(['id' => $exam_id]);
$exam_title = $stmt_exam->fetchColumn() ?: "Kết Quả Bài Thi";

// 2. Lấy tất cả câu hỏi thuộc đề thi
$stmt_qs = $conn->prepare("
    SELECT q.question_id, q.content AS question, q.explanation
    FROM questions q
    JOIN exam_questions eq ON q.question_id = eq.question_id
    WHERE eq.exam_id = :exam_id
");
$stmt_qs->execute(['exam_id' => $exam_id]);
$questions = $stmt_qs->fetchAll();

$correct_count = 0;
$total_questions = count($questions);
$result_details = [];

// 3. Lấy đáp án từ bảng 'answers'
$stmt_ans = $conn->prepare("SELECT answer_id, content, is_correct FROM answers WHERE question_id = :q_id");

foreach ($questions as $q) {
    $q_id = $q['question_id'];
    $stmt_ans->execute(['q_id' => $q_id]);
    
    $answers = $stmt_ans->fetchAll();

    $selected_ans_id = $user_answers[$q_id] ?? null;
    $is_correct = false;

    foreach ($answers as $a) {
        if ($a['is_correct'] && $a['answer_id'] == $selected_ans_id) {
            $is_correct = true;
        }
    }

    if ($is_correct) $correct_count++;

    $result_details[$q_id] = [
        'question' => $q['question'],
        'explanation' => $q['explanation'],
        'answers' => $answers,
        'selected_id' => $selected_ans_id,
        'is_correct' => $is_correct
    ];
}

$score = round(($correct_count / max($total_questions, 1)) * 10, 2);
$status = ($score >= 5.0) ? 'PASSED' : 'FAILED';

// 4. Lưu kết quả thi vào bảng exam_results (Đã sửa c_time lấy theo biến $completion_time)
$stmt_res = $conn->prepare("
    INSERT INTO exam_results (user_id, exam_id, score, correct_answers_count, total_questions, completion_time, status)
    VALUES (:u_id, :e_id, :score, :c_count, :total_q, :c_time, :status)
");
$stmt_res->execute([
    'u_id' => $user_id,
    'e_id' => $exam_id,
    'score' => $score,
    'c_count' => $correct_count,
    'total_q' => $total_questions,
    'c_time' => $completion_time, // 🎯 Đã thay 900 bằng thời gian làm bài thực tế!
    'status' => $status
]);
$result_id = $conn->lastInsertId();

// 5. Lưu lựa chọn học viên vào bảng student_answers
$stmt_sa = $conn->prepare("
    INSERT INTO student_answers (result_id, question_id, selected_answer_id, is_correct)
    VALUES (:r_id, :q_id, :s_id, :is_corr)
");
foreach ($result_details as $q_id => $det) {
    $stmt_sa->execute([
        'r_id' => $result_id,
        'q_id' => $q_id,
        's_id' => $det['selected_id'],
        'is_corr' => $det['is_correct'] ? 1 : 0
    ]);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($exam_title); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f6f9; color: #333; padding-bottom: 80px; }
        header { background: #2c3e50; color: white; padding: 15px 30px; text-align: center; }
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .result-card { background: white; border-radius: 8px; padding: 30px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .score-display { font-size: 3.5rem; font-weight: bold; color: #27ae60; margin: 15px 0; }
        .time-display { font-size: 1rem; color: #555; margin-bottom: 10px; }
        .question-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .question-title { font-weight: 600; font-size: 1.1rem; margin-bottom: 15px; }
        .options-list { display: flex; flex-direction: column; gap: 10px; }
        .option-item { padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; }
        .badge-correct { background-color: #d4edda; color: #155724; padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .badge-wrong { background-color: #f8d7da; color: #721c24; padding: 3px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .explanation-box { margin-top: 10px; padding: 12px; background-color: #eef7fc; border-left: 4px solid #3498db; border-radius: 4px; }
        .btn-submit { background-color: #27ae60; color: white; padding: 12px 35px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .btn-home { background-color: #3498db; color: white; padding: 12px 35px; border-radius: 6px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <header>
        <div style="font-size: 1.3rem; font-weight: bold;"><?php echo htmlspecialchars($exam_title); ?></div>
    </header>

    <div class="container">
        <div class="result-card">
            <h2>KẾT QUẢ BÀI THI</h2>
            <div class="score-display"><?php echo $score; ?> / 10</div>
            <p class="time-display">⏱️ Thời gian hoàn thành: <strong><?php echo floor($completion_time / 60); ?> phút <?php echo ($completion_time % 60); ?> giây</strong></p>
            <p>Số câu đúng: <strong><?php echo $correct_count; ?>/<?php echo $total_questions; ?></strong> câu</p>
        </div>

        <h3>Chi tiết đáp án & Lời giải:</h3><br>

        <?php foreach ($result_details as $q_id => $res): ?>
            <div class="question-card">
                <div class="question-title">
                    <?php echo htmlspecialchars($res['question']); ?>
                    <?php if ($res['is_correct']): ?>
                        <span class="badge-correct">Đúng</span>
                    <?php else: ?>
                        <span class="badge-wrong">Sai</span>
                    <?php endif; ?>
                </div>

                <div class="options-list">
                    <?php foreach ($res['answers'] as $a): 
                        $style = "";
                        if ($a['is_correct']) {
                            $style = "background-color: #d4edda; border-color: #28a745; font-weight: bold;";
                        } elseif ($a['answer_id'] == $res['selected_id'] && !$res['is_correct']) {
                            $style = "background-color: #f8d7da; border-color: #dc3545;";
                        }
                    ?>
                        <div class="option-item" style="<?php echo $style; ?>">
                            <?php echo htmlspecialchars($a['content']); ?>
                            <?php if ($a['answer_id'] == $res['selected_id']): ?>
                                <em>&nbsp;(Lựa chọn của bạn)</em>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($res['explanation'])): ?>
                    <div class="explanation-box">
                        <strong>💡 Lời giải:</strong> <?php echo htmlspecialchars($res['explanation']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div style="text-align: center; margin-top: 30px; display: flex; justify-content: center; gap: 15px;">
            <a href="take_exam.php?exam_id=<?php echo $exam_id; ?>" class="btn-submit">Làm lại bài thi</a>
            <a href="index.php" class="btn-home">Về trang chủ</a>
        </div>
    </div>

</body>
</html>