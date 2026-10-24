<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// Yêu cầu đăng nhập để xem lịch sử của đúng tài khoản.
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = (int) $_SESSION['user_id'];
$is_teacher = strtoupper((string)($_SESSION['role'] ?? '')) === 'TEACHER';
$detail_attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : null;

$user_columns = $conn->query('SHOW COLUMNS FROM `users`')->fetchAll(PDO::FETCH_COLUMN);
$name_candidates = ['full_name', 'fullname', 'name', 'username', 'user_name', 'ho_ten', 'hoten'];
$name_column = null;
foreach ($name_candidates as $candidate) {
    if (in_array($candidate, $user_columns, true)) {
        $name_column = $candidate;
        break;
    }
}
if ($name_column === null) {
    $name_expression = "CONCAT('Học viên #', r.user_id)";
} else {
    $safe_name_column = '`' . str_replace('`', '``', $name_column) . '`';
    $name_expression = "COALESCE(NULLIF(u.$safe_name_column, ''), CONCAT('Học viên #', r.user_id))";
}

$student_name = '';
if ($name_column !== null) {
    $name_stmt = $conn->prepare("SELECT `$name_column` FROM `users` WHERE `user_id` = :user_id LIMIT 1");
    $name_stmt->execute(['user_id' => $user_id]);
    $student_name = (string)($name_stmt->fetchColumn() ?: '');
}
if ($student_name === '') {
    $student_name = $_SESSION['full_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'Người dùng';
}

$sql_history = "
    SELECT r.result_id AS attempt_id, r.user_id AS student_id,
           $name_expression AS student_name,
           e.title AS exam_title, r.submitted_at,
           CONCAT(FLOOR(r.completion_time / 60), ' phút ', MOD(r.completion_time, 60), ' giây') AS completion_time,
           r.score, (r.status = 'PASSED') AS passed
    FROM exam_results r
    JOIN exam e ON r.exam_id = e.exam_id
    LEFT JOIN `users` u ON u.user_id = r.user_id
";
if (!$is_teacher) {
    $sql_history .= " WHERE r.user_id = :user_id";
}
$sql_history .= " ORDER BY r.submitted_at DESC";
$stmt = $conn->prepare($sql_history);
if (!$is_teacher) {
    $stmt->execute(['user_id' => $user_id]);
} else {
    $stmt->execute();
}
$history_list = $stmt->fetchAll();

// Chi tiết lượt thi khi chọn
$attempt_detail = null;
if ($detail_attempt_id) {
    $stmt_info = $conn->prepare("
        SELECT r.result_id AS attempt_id, r.user_id AS student_id,
               e.title AS exam_title, r.score, r.submitted_at,
               CONCAT(FLOOR(r.completion_time / 60), ' phút ', MOD(r.completion_time, 60), ' giây') AS completion_time
        FROM exam_results r
        JOIN exam e ON r.exam_id = e.exam_id
        WHERE r.result_id = :r_id
          AND (:is_teacher = 1 OR r.user_id = :u_id)
    ");
    $stmt_info->execute([
        'r_id' => $detail_attempt_id,
        'is_teacher' => $is_teacher ? 1 : 0,
        'u_id' => $user_id
    ]);
    $info = $stmt_info->fetch();

    if ($info) {
        $detail_student_name = $student_name;
        if ($is_teacher && $name_column !== null) {
            $detail_name_stmt = $conn->prepare("SELECT `$name_column` FROM `users` WHERE `user_id` = :uid LIMIT 1");
            $detail_name_stmt->execute(['uid' => (int)$info['student_id']]);
            $detail_student_name = (string)($detail_name_stmt->fetchColumn() ?: ('Học viên #' . $info['student_id']));
        } elseif ($is_teacher) {
            $detail_student_name = 'Học viên #' . $info['student_id'];
        }
        $stmt_qs = $conn->prepare("
            SELECT q.question_id, q.content AS question, q.explanation, sa.selected_answer_id
            FROM student_answers sa
            JOIN questions q ON sa.question_id = q.question_id
            WHERE sa.result_id = :r_id
        ");
        $stmt_qs->execute(['r_id' => $detail_attempt_id]);
        $raw_qs = $stmt_qs->fetchAll();

        $questions = [];
        $stmt_ans = $conn->prepare("SELECT answer_id, content, is_correct FROM answers WHERE question_id = :q_id");

        foreach ($raw_qs as $rq) {
            $stmt_ans->execute(['q_id' => $rq['question_id']]);
            $ans_list = $stmt_ans->fetchAll();

            $options = [];
            $correct_key = '';
            $selected_key = '';
            $labels = ['A', 'B', 'C', 'D'];

            foreach ($ans_list as $idx => $a) {
                $key = $labels[$idx] ?? $a['answer_id'];
                $options[$key] = $a['content'];
                if ($a['is_correct']) $correct_key = $key;
                if ($a['answer_id'] == $rq['selected_answer_id']) $selected_key = $key;
            }

            $questions[] = [
                'id' => $rq['question_id'],
                'question' => $rq['question'],
                'options' => $options,
                'selected' => $selected_key,
                'correct' => $correct_key,
                'explanation' => $rq['explanation']
            ];
        }

        $attempt_detail = [
            'attempt_id' => $info['attempt_id'],
            'student_id' => $info['student_id'],
            'student_name' => $detail_student_name,
            'exam_title' => $info['exam_title'],
            'score' => $info['score'],
            'submitted_at' => $info['submitted_at'],
            'completion_time' => $info['completion_time'],
            'questions' => $questions
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch Sử Làm Bài Thi</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f4f6f9; color: #333; padding-bottom: 50px; }
        header { background: #2c3e50; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.3rem; font-weight: bold; }
        .btn-home { background-color: #3498db; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: bold; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .history-card { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .history-card h2 { margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        .student-name { margin-bottom: 18px; padding: 12px 16px; background: #eaf4ff; border-left: 4px solid #3498db; border-radius: 6px; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; color: #2c3e50; }
        .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: bold; }
        .status-passed { background-color: #d4edda; color: #155724; }
        .status-failed { background-color: #f8d7da; color: #721c24; }
        .btn-detail { background-color: #27ae60; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: bold; }
        .detail-header-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .score-box { font-size: 2rem; font-weight: bold; color: #27ae60; }
        .question-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .question-title { font-weight: 600; font-size: 1.05rem; margin-bottom: 15px; color: #2c3e50; }
        .options-list { display: flex; flex-direction: column; gap: 10px; }
        .option-item { padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; }
        .option-correct { background-color: #d4edda; border-color: #28a745; color: #155724; font-weight: bold; }
        .option-wrong { background-color: #f8d7da; border-color: #dc3545; color: #721c24; font-weight: bold; }
        .explanation-box { margin-top: 12px; padding: 10px 15px; background-color: #eef7fc; border-left: 4px solid #3498db; }
        .btn-back-history { display: inline-block; margin-bottom: 20px; background-color: #7f8c8d; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <header>
        <div class="page-title">📜 Lịch Sử Làm Bài Thi</div>
        <a href="index.php" class="btn-home">🏠 Trang Chủ</a>
    </header>

    <div class="container">

        <?php if ($attempt_detail): ?>
            <a href="history.php" class="btn-back-history">⬅️ Quay lại danh sách lịch sử</a>

            <div class="detail-header-card">
                <div>
                    <h2><?php echo htmlspecialchars($attempt_detail['exam_title']); ?></h2>
                    <?php if ($is_teacher): ?>
                        <p style="margin-top:8px;">👤 Học sinh: <strong><?php echo htmlspecialchars((string)$attempt_detail['student_name'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                    <?php endif; ?>
                    <p style="color: #666; margin-top: 5px;">
                        🕒 Ngày nộp: <?php echo date('d/m/Y H:i', strtotime($attempt_detail['submitted_at'])); ?> 
                        | ⏱️ Thời gian: <?php echo $attempt_detail['completion_time']; ?>
                    </p>
                </div>
                <div class="score-box">
                    <?php echo $attempt_detail['score']; ?> <span style="font-size: 1rem; color: #666;">/ 10 điểm</span>
                </div>
            </div>

            <h3>Chi tiết bài làm:</h3><br>

            <?php foreach ($attempt_detail['questions'] as $index => $q): 
                $is_user_correct = ($q['selected'] === $q['correct']);
            ?>
                <div class="question-card">
                    <div class="question-title">
                        Câu <?php echo ($index + 1); ?>: <?php echo htmlspecialchars($q['question']); ?>
                    </div>

                    <div class="options-list">
                        <?php foreach ($q['options'] as $key => $value): 
                            $class_name = "";
                            $icon = "";
                            $user_label = "";

                            if ($key === $q['correct']) {
                                $class_name = "option-correct";
                                $icon = '<span style="color: #28a745; font-weight: bold;">✓</span>';
                            }
                            if ($key === $q['selected'] && !$is_user_correct) {
                                $class_name = "option-wrong";
                                $icon = '<span style="color: #dc3545; font-weight: bold;">✗</span>';
                            }
                            if ($key === $q['selected']) {
                                $user_label = ' <span style="font-style: italic; font-size: 0.85rem;">(Lựa chọn của bạn)</span>';
                            }
                        ?>
                            <div class="option-item <?php echo $class_name; ?>">
                                <div>
                                    <strong><?php echo $key; ?>.</strong> <?php echo htmlspecialchars($value); ?>
                                    <?php echo $user_label; ?>
                                </div>
                                <div><?php echo $icon; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="explanation-box">
                        <strong>💡 Lời giải chi tiết:</strong> <?php echo htmlspecialchars($q['explanation']); ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="history-card">
                <div class="student-name"><?php if ($is_teacher): ?>👩‍🏫 Chế độ giáo viên: danh sách bài thi của tất cả học sinh<?php else: ?>👤 Người thi: <strong><?php echo htmlspecialchars((string)$student_name, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?></div>
                <h2>Danh sách các bài đã thi</h2>
                <?php if (empty($history_list)): ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">Bạn chưa thực hiện bài thi nào.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <?php if ($is_teacher): ?><th>Học sinh</th><?php endif; ?>
                                <th>Tên đề thi</th>
                                <th>Ngày làm</th>
                                <th>Thời gian làm</th>
                                <th>Điểm</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history_list as $index => $item): ?>
                                <tr>
                                    <td><strong><?php echo $index + 1; ?></strong></td>
                                    <?php if ($is_teacher): ?><td><?php echo htmlspecialchars((string)$item['student_name'], ENT_QUOTES, 'UTF-8'); ?></td><?php endif; ?>
                                    <td><strong><?php echo htmlspecialchars($item['exam_title']); ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($item['submitted_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($item['completion_time']); ?></td>
                                    <td><strong style="color: #27ae60; font-size: 1.1rem;"><?php echo $item['score']; ?></strong> / 10</td>
                                    <td>
                                        <?php if ($item['passed']): ?>
                                            <span class="badge-status status-passed">Đạt</span>
                                        <?php else: ?>
                                            <span class="badge-status status-failed">Không đạt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="history.php?attempt_id=<?php echo $item['attempt_id']; ?>" class="btn-detail">Xem chi tiết</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>