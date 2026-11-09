<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$current_page = 'history.php';
include __DIR__ . '/../includes/menu.php'; 
// Yêu cầu đăng nhập để xem lịch sử của đúng tài khoản.
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = (int) $_SESSION['user_id'];
$is_teacher = strtoupper((string)($_SESSION['role'] ?? '')) === 'TEACHER';
$detail_attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : null;

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Hiển thị điểm gọn: 7.50 -> 7.5, 8.00 -> 8
function fmt_score($score): string
{
    return rtrim(rtrim(number_format((float)$score, 2, '.', ''), '0'), '.');
}

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

// Danh sách lịch sử: bỏ qua các lượt đang làm dở (IN_PROGRESS)
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
$where = ["r.status <> 'IN_PROGRESS'"];
if (!$is_teacher) {
    $where[] = 'r.user_id = :user_id';
}
$sql_history .= ' WHERE ' . implode(' AND ', $where);
$sql_history .= ' ORDER BY r.submitted_at DESC';

$stmt = $conn->prepare($sql_history);
if (!$is_teacher) {
    $stmt->execute(['user_id' => $user_id]);
} else {
    $stmt->execute();
}
$history_list = $stmt->fetchAll();

// Thống kê nhanh cho đầu trang
$total_attempts = count($history_list);
$score_sum = 0.0;
$passed_count = 0;
$best_score = null;
foreach ($history_list as $row) {
    $score_value = (float)$row['score'];
    $score_sum += $score_value;
    if ($row['passed']) {
        $passed_count++;
    }
    $best_score = $best_score === null ? $score_value : max($best_score, $score_value);
}
$avg_score = $total_attempts > 0 ? $score_sum / $total_attempts : 0;
$pass_rate = $total_attempts > 0 ? (int)round($passed_count * 100 / $total_attempts) : 0;

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
          AND r.status <> 'IN_PROGRESS'
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
        $stmt_ans = $conn->prepare("SELECT answer_id, content, is_correct FROM answers WHERE question_id = :q_id ORDER BY answer_id");

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

        // Đếm số câu đúng / sai / chưa chọn
        $cnt_correct = $cnt_wrong = $cnt_blank = 0;
        foreach ($questions as $q) {
            if ($q['selected'] === '') {
                $cnt_blank++;
            } elseif ($q['selected'] === $q['correct']) {
                $cnt_correct++;
            } else {
                $cnt_wrong++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử làm bài - TestSuViet</title>
<!-- Thêm đoạn này vào thẻ <head> của includes/menu.php -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/../assets/css/history.css">
</head>
<body>

<main class="wrap">

<?php if ($attempt_detail): ?>
    <?php
        $detail_score = (float)$attempt_detail['score'];
        $detail_pass = $detail_score >= 5;
        $ring_percent = (int)round(min(10, max(0, $detail_score)) * 10);
    ?>

    <section class="detail-head">
        <div>
            <h1><?= h($attempt_detail['exam_title']) ?></h1>
            <div class="chips">
                <?php if ($is_teacher): ?>
                    <span class="chip">Học sinh: <strong><?= h($attempt_detail['student_name']) ?></strong></span>
                <?php endif; ?>
                <span class="chip">Ngày nộp: <strong><?= h(date('d/m/Y H:i', strtotime($attempt_detail['submitted_at']))) ?></strong></span>
                <span class="chip">Thời gian làm: <strong><?= h($attempt_detail['completion_time']) ?></strong></span>
            </div>
        </div>
        <div class="score-ring <?= $detail_pass ? '' : 'is-fail' ?>" style="--p: <?= $ring_percent ?>;"
             role="img" aria-label="Điểm <?= h(fmt_score($detail_score)) ?> trên 10">
            <div class="score-ring-inner">
                <strong><?= h(fmt_score($detail_score)) ?></strong>
                <span>/ 10 điểm</span>
            </div>
        </div>
    </section>

    <section class="summary" aria-label="Tổng kết bài làm">
        <div class="summary-item correct"><strong><?= (int)$cnt_correct ?></strong><span>Câu đúng</span></div>
        <div class="summary-item wrong"><strong><?= (int)$cnt_wrong ?></strong><span>Câu sai</span></div>
        <div class="summary-item blank"><strong><?= (int)$cnt_blank ?></strong><span>Chưa trả lời</span></div>
    </section>

    <h2 class="section-title">Chi tiết bài làm</h2>

    <?php foreach ($attempt_detail['questions'] as $index => $q): ?>
        <?php
            if ($q['selected'] === '') {
                $state_class = 'state-blank';
                $state_text = 'Chưa trả lời';
            } elseif ($q['selected'] === $q['correct']) {
                $state_class = 'state-correct';
                $state_text = 'Đúng';
            } else {
                $state_class = 'state-wrong';
                $state_text = 'Sai';
            }
        ?>
        <article class="question-card">
            <div class="question-top">
                <div class="question-title">
                    <span class="num">Câu <?= $index + 1 ?>.</span><?= h($q['question']) ?>
                </div>
                <span class="state <?= $state_class ?>"><?= $state_text ?></span>
            </div>

            <div class="options">
                <?php foreach ($q['options'] as $key => $value): ?>
                    <?php
                        $is_correct_option = ((string)$key === (string)$q['correct']);
                        $is_selected_option = ($q['selected'] !== '' && (string)$key === (string)$q['selected']);
                        $option_class = '';
                        $tag = '';

                        if ($is_correct_option) {
                            $option_class = 'is-correct';
                            $tag = $is_selected_option ? 'Bạn chọn · Đáp án đúng' : 'Đáp án đúng';
                        } elseif ($is_selected_option) {
                            $option_class = 'is-wrong';
                            $tag = 'Bạn chọn';
                        }
                    ?>
                    <div class="option <?= $option_class ?>">
                        <span class="option-key"><?= h($key) ?></span>
                        <span class="option-text"><?= h($value) ?></span>
                        <?php if ($tag !== ''): ?>
                            <span class="option-tag"><?= h($tag) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($q['explanation'])): ?>
                <div class="explanation">
                    <strong>Lời giải chi tiết:</strong> <?= nl2br(h($q['explanation'])) ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>

<?php else: ?>

    <div class="page-head">
        <h1>Lịch sử làm bài</h1>
        <p>
            <?php if ($is_teacher): ?>
                Chế độ giáo viên: kết quả bài thi của tất cả học sinh.
            <?php else: ?>
                Người thi: <strong><?= h($student_name) ?></strong>
            <?php endif; ?>
        </p>
    </div>

    <?php if ($total_attempts > 0): ?>
        <section class="stats" aria-label="Thống kê nhanh">
            <div class="stat">
                <div class="stat-value"><?= number_format($total_attempts) ?></div>
                <div class="stat-label">Bài đã nộp</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= h(fmt_score($avg_score)) ?> <small>/ 10</small></div>
                <div class="stat-label">Điểm trung bình</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= h(fmt_score($best_score)) ?> <small>/ 10</small></div>
                <div class="stat-label">Điểm cao nhất</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= $pass_rate ?><small>%</small></div>
                <div class="stat-label">Tỷ lệ đạt</div>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-head"><h2>Danh sách các bài đã thi</h2></div>

        <?php if (empty($history_list)): ?>
            <div class="empty">
                <strong><?= $is_teacher ? 'Chưa có học sinh nào nộp bài' : 'Bạn chưa thực hiện bài thi nào' ?></strong>
                <?php if (!$is_teacher): ?>
                    Hãy chọn một đề thi và làm bài đầu tiên của bạn.<br>
                    <a class="btn-primary" href="index.php">Làm bài ngay</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-wrap">
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
                                <td class="col-index" data-label="STT"><?= $index + 1 ?></td>
                                <?php if ($is_teacher): ?>
                                    <td data-label="Học sinh"><?= h($item['student_name']) ?></td>
                                <?php endif; ?>
                                <td data-label="Đề thi"><span class="exam-name"><?= h($item['exam_title']) ?></span></td>
                                <td class="muted" data-label="Ngày làm"><?= h(date('d/m/Y H:i', strtotime($item['submitted_at']))) ?></td>
                                <td class="muted" data-label="Thời gian"><?= h($item['completion_time']) ?></td>
                                <td data-label="Điểm">
                                    <span>
                                        <span class="score <?= $item['passed'] ? 'is-pass' : 'is-fail' ?>"><?= h(fmt_score($item['score'])) ?></span><span class="score-max">/10</span>
                                    </span>
                                </td>
                                <td data-label="Trạng thái">
                                    <?php if ($item['passed']): ?>
                                        <span class="badge badge-pass">Đạt</span>
                                    <?php else: ?>
                                        <span class="badge badge-fail">Không đạt</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Thao tác">
                                    <a href="history.php?attempt_id=<?= (int)$item['attempt_id'] ?>" class="btn-detail">Xem chi tiết</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

<?php endif; ?>

</main>
</body>
</html>