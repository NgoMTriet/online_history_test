<?php
session_start();
require_once __DIR__ . '/database.php';

$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 1;

// Lấy thông tin đề thi
$stmt =$conn->prepare("SELECT * FROM exam WHERE exam_id = :id");
$stmt->execute(['id' =>$exam_id]);
$exam =$stmt->fetch();

if (!$exam) {
    die("Đề thi không tồn tại!");
}

// Lấy danh sách câu hỏi và các lựa chọn (giấu đáp án đúng)
$stmt_q =$conn->prepare("
    SELECT q.question_id, q.content 
    FROM questions q
    JOIN exam_questions eq ON q.question_id = eq.question_id
    WHERE eq.exam_id = :exam_id
");
$stmt_q->execute(['exam_id' =>$exam_id]);
$raw_questions =$stmt_q->fetchAll();

$questions_for_client = [];
$stmt_ans =$conn->prepare("SELECT answer_id, content FROM answers WHERE question_id = :q_id");

foreach ($raw_questions as$q) {
    $stmt_ans->execute(['q_id' =>$q['question_id']]);
    $answers =$stmt_ans->fetchAll();

    $options = [];$labels = ['A', 'B', 'C', 'D'];
    foreach ($answers as$idx => $a) {$options[$a['answer_id']] =$a['content'];
    }

    $questions_for_client[] = [
        'id' => $q['question_id'],
        'question' => $q['content'],
        'options' => $options
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($exam['title']); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f6f9; color: #333; padding-bottom: 80px; }
        header { background: #2c3e50; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; }
        .exam-title { font-size: 1.2rem; font-weight: bold; }
        .timer-box { background: #e74c3c; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; }
        .btn-exit-header { background-color: #7f8c8d; color: white; text-decoration: none; padding: 8px 16px; border-radius: 20px; font-weight: bold; }
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .question-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .question-title { font-weight: 600; font-size: 1.1rem; margin-bottom: 15px; color: #2c3e50; }
        .options-list { display: flex; flex-direction: column; gap: 10px; }
        .option-item { display: flex; align-items: center; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; }
        .btn-submit { background-color: #27ae60; color: white; border: none; padding: 12px 35px; font-size: 1.1rem; font-weight: bold; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>

    <header>
        <div class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></div>
        <div style="display: flex; gap: 15px; align-items: center;">
            <div class="timer-box">⏱️ Thời gian còn lại: <span id="timer-display">00:00</span></div>
            <a href="index.php" class="btn-exit-header" onclick="return confirm('Bạn có chắc chắn muốn thoát?');">Thoát</a>
        </div>
    </header>

    <div class="container">
        <form id="exam-form" method="POST" action="result.php">
            <input type="hidden" name="action" value="submit_exam">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">

            <?php foreach ($questions_for_client as $index =>$q): ?>
                <div class="question-card">
                    <div class="question-title">
                        Câu <?php echo ($index + 1); ?>: <?php echo htmlspecialchars($q['question']); ?>
                    </div>
                    <div class="options-list">
                        <?php foreach ($q['options'] as $ans_id =>$value): ?>
                            <label class="option-item">
                                <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo $ans_id; ?>" style="margin-right: 10px;">
                                <?php echo htmlspecialchars($value); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn-submit" onclick="return confirm('Bạn có chắc chắn muốn nộp bài?');">Nộp bài thi</button>
            </div>
        </form>
    </div>

    <script>
        let totalSeconds = <?php echo $exam['duration'] * 60; ?>;
        const timerDisplay = document.getElementById('timer-display');
        const examForm = document.getElementById('exam-form');

        function updateTimer() {
            let minutes = Math.floor(totalSeconds / 60);
            let seconds = totalSeconds % 60;
            timerDisplay.textContent = `${minutes < 10 ? '0' + minutes : minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
            if (totalSeconds <= 0) {
                alert('Đã hết giờ làm bài!');
                examForm.submit();
            } else {
                totalSeconds--;
            }
        }
        setInterval(updateTimer, 1000);
        updateTimer();
    </script>
</body>
</html>