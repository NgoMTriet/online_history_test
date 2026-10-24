<?php
session_start();
require_once __DIR__ . '/../config/database.php';


// Không lưu trang làm bài vào bộ nhớ đệm.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$exam_id = (int) ($_GET['exam_id'] ?? 0);

if ($exam_id <= 0) {
    header('Location: index.php');
    exit;
}

// Không chặn ở đây chỉ vì đã có kết quả cũ.
// Phần giới hạn lượt thi bên dưới sẽ xử lý theo cột max_attempts của đề.


// Lấy ID người dùng từ Session
$user_id =$_SESSION['user_id'] ?? 0;
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 1;

// 1. Lấy thông tin đề thi
$stmt =$conn->prepare("SELECT * FROM exam WHERE exam_id = :id");
$stmt->execute(['id' =>$exam_id]);
$exam =$stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Đề thi không tồn tại!");
}

/*
|--------------------------------------------------------------------------
| 🎯 1. KIỂM TRA GIỚI HẠN SỐ LẦN LÀM BÀI (MAX ATTEMPTS)
|--------------------------------------------------------------------------
*/
$max_attempts = (int)($exam['max_attempts'] ?? 0);

if ($max_attempts > 0 &&$user_id > 0) {
    // Đếm số lần thí sinh này đã nộp bài thi
    $stmt_check =$conn->prepare("
        SELECT COUNT(*) 
        FROM exam_results 
        WHERE user_id = :user_id AND exam_id = :exam_id
    ");
    $stmt_check->execute([
        'user_id' => $user_id,
        'exam_id' => $exam_id
    ]);
    $user_attempts = (int)$stmt_check->fetchColumn();

    // Nếu số lần làm đã vượt quá giới hạn -> Chặn làm bài
    if ($user_attempts >=$max_attempts) {
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <title>Hết lượt làm bài</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
                .card-error { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: center; max-width: 480px; width: 90%; }
                .card-error h2 { color: #e74c3c; margin-bottom: 15px; }
                .card-error p { color: #555; line-height: 1.6; margin-bottom: 25px; }
                .btn-home { display: inline-block; background: #3498db; color: white; text-decoration: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; }
                .btn-home:hover { background: #2980b9; }
            </style>
        </head>
        <body>
            <div class="card-error">
                <h2>🚫 Bạn đã hết lượt làm bài!</h2>
                <p>
                    Đề thi <strong>"<?php echo htmlspecialchars($exam['title']); ?>"</strong> chỉ cho phép làm tối đa <strong><?php echo $max_attempts; ?></strong> lần.<br>
                    Bạn đã thực hiện <strong><?php echo $user_attempts; ?>/<?php echo$max_attempts; ?></strong> lượt.
                </p>
                <a href="index.php" class="btn-home">Quay lại trang chủ</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 🔑 2. KIỂM TRA MẬT KHẨU ĐỀ THI (EXAM PASSWORD)
|--------------------------------------------------------------------------
*/
$require_password = !empty($exam['exam_password']);$password_error = '';

if ($require_password) {
    $session_pass_key = 'exam_auth_' .$exam_id;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam_password'])) {
        $input_password = trim($_POST['input_password'] ?? '');
        if ($input_password ===$exam['exam_password']) {
            $_SESSION[$session_pass_key] = true;
        } else {
            $password_error = 'Mật khẩu bài thi không chính xác!';
        }
    }

    if (empty($_SESSION[$session_pass_key])) {
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <title>Yêu cầu mật khẩu bài thi</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
                .card-password { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: center; max-width: 400px; width: 90%; }
                .card-password h2 { color: #2c3e50; margin-bottom: 10px; font-size: 22px; }
                .card-password p { color: #666; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
                .form-input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 15px; box-sizing: border-box; font-size: 15px; outline: none; }
                .form-input:focus { border-color: #2563eb; }
                .btn-submit { width: 100%; background: #2563eb; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 15px; }
                .btn-submit:hover { background: #1d4ed8; }
                .error-msg { color: #dc2626; font-size: 13px; margin-bottom: 15px; }
                .btn-back { display: inline-block; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="card-password">
                <h2>🔒 Đề thi có mật khẩu</h2>
                <p>Nhập mật khẩu do giáo viên cung cấp để bắt đầu bài thi <strong>"<?php echo htmlspecialchars($exam['title']); ?>"</strong>.</p>

                <?php if ($password_error !== ''): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($password_error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="password" name="input_password" class="form-input" placeholder="Nhập mật khẩu bài thi..." required autoFocus>
                    <button type="submit" name="submit_exam_password" class="btn-submit">Bắt đầu làm bài</button>
                </form>

                <a href="index.php" class="btn-back">⬅ Quay lại trang chủ</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// 3. Lấy danh sách câu hỏi
$stmt_q =$conn->prepare("
    SELECT q.question_id, q.content 
    FROM questions q
    JOIN exam_questions eq ON q.question_id = eq.question_id
    WHERE eq.exam_id = :exam_id
");
$stmt_q->execute(['exam_id' =>$exam_id]);
$raw_questions =$stmt_q->fetchAll(PDO::FETCH_ASSOC);

if (empty($raw_questions)) {
    die("Đề thi này chưa có câu hỏi nào!");
}

// 🎲 XÁO TRỘN THỨ TỰ CÂU HỎI
shuffle($raw_questions);

// 4. Lấy danh sách đáp án từ bảng answers
$stmt_ans =$conn->prepare("
    SELECT * FROM answers
    WHERE question_id = :q_id
");

$questions_for_client = [];

foreach ($raw_questions as$q) {
    $stmt_ans->execute(['q_id' =>$q['question_id']]);
    $answers =$stmt_ans->fetchAll(PDO::FETCH_ASSOC);

    if (empty($answers)) {
        echo '<p style="color:red; text-align:center;">'
            . 'Chưa có đáp án cho câu hỏi ID: '
            . (int)$q['question_id']
            . '</p>';
    }

    // 🎲 XÁO TRỘN ĐÁP ÁN CỦA TỪNG CÂU
    shuffle($answers);

    $options = [];$labels = ['A', 'B', 'C', 'D'];

    foreach ($answers as$idx => $a) {$options[] = [
            'id'      => $a['answer_id'],
            'label'   => $labels[$idx] ?? (string)($idx + 1), 'content' =>$a['content']
        ];
    }

    $questions_for_client[] = [
        'id'       => $q['question_id'],
        'question' => $q['content'],
        'options'  => $options
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($exam['title'] ?? 'Làm bài thi'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f6f9; color: #333; padding-bottom: 80px; }
        header { background: #2c3e50; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; }
        .exam-title { font-size: 1.2rem; font-weight: bold; }
        .timer-box { background: #e74c3c; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; }
        .btn-exit-header { background-color: #7f8c8d; color: white; text-decoration: none; padding: 8px 16px; border-radius: 20px; font-weight: bold; }
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .question-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .question-title { font-weight: 600; font-size: 1.1rem; margin-bottom: 15px; color: #2c3e50; }
        .options-list { display: flex; flex-direction: column; gap: 10px; }
        .option-item { display: flex; align-items: center; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; transition: background 0.2s; }
        .option-item:hover { background-color: #f8f9fa; }
        .btn-submit { background-color: #27ae60; color: white; border: none; padding: 12px 35px; font-size: 1.1rem; font-weight: bold; border-radius: 6px; cursor: pointer; }
        .btn-submit:hover { background-color: #219150; }
    </style>
</head>
<body>

    <header>
        <div class="exam-title"><?php echo htmlspecialchars($exam['title'] ?? 'Bài thi'); ?></div>
        <div style="display: flex; gap: 15px; align-items: center;">
            <div class="timer-box">⏱️ Thời gian còn lại: <span id="timer-display">00:00</span></div>
            <a href="index.php" class="btn-exit-header" onclick="return confirm('Bạn có chắc chắn muốn thoát?');">Thoát</a>
        </div>
    </header>

    <div class="container">
        <form id="exam-form" method="POST" action="result.php">
            <input type="hidden" name="action" value="submit_exam">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            <input type="hidden" name="completion_time" id="completion_time_input" value="0">

            <?php foreach ($questions_for_client as $index =>$q): ?>
                <div class="question-card">
                    <div class="question-title">
                        Câu <?php echo ($index + 1); ?>: <?php echo htmlspecialchars($q['question']); ?>
                    </div>
                    <div class="options-list">
                        <?php foreach ($q['options'] as$option): ?>
                            <label class="option-item">
                                <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo $option['id']; ?>" style="margin-right: 10px;" required>
                                <strong><?php echo $option['label']; ?>.</strong>&nbsp;<?php echo htmlspecialchars($option['content']); ?>
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
        let totalSeconds = <?php echo (int)($exam['duration'] ?? 15) * 60; ?>;
        const timerDisplay = document.getElementById('timer-display');
        const examForm = document.getElementById('exam-form');
        const completionTimeInput = document.getElementById('completion_time_input');

        const startTime = Date.now();

        function calculateTimeSpent() {
            const endTime = Date.now();
            const secondsSpent = Math.round((endTime - startTime) / 1000);
            completionTimeInput.value = secondsSpent;
        }

        examForm.addEventListener('submit', function() {
            calculateTimeSpent();
        });

        function updateTimer() {
            let minutes = Math.floor(totalSeconds / 60);
            let seconds = totalSeconds % 60;
            
            timerDisplay.textContent = `${minutes < 10 ? '0' + minutes : minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
            
            if (totalSeconds <= 0) {
                alert('Đã hết giờ làm bài!');
                calculateTimeSpent();
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