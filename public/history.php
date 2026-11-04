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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0f2f5e;
            --navy-hero: #102b49;
            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --gold: #f5a623;
            --bg: #f4f8fd;
            --text: #19385f;
            --muted: #55708f;
            --line: #dce9f8;
            --ok: #17744b;
            --ok-bg: #e4f5eb;
            --ok-line: #8fd0ae;
            --bad: #b42318;
            --bad-bg: #fdeceb;
            --bad-line: #f1aaa5;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', 'Segoe UI', Tahoma, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding-bottom: 56px;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; }
        :focus-visible { outline: 3px solid #93b8f7; outline-offset: 2px; }

        /* ---------- Thanh trên cùng ---------- */
        .topbar { background: linear-gradient(135deg, var(--navy-hero), var(--navy)); color: #fff; }
        .topbar-inner {
            max-width: 1100px; margin: 0 auto; padding: 14px 24px;
            display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        }
        .brand { font-weight: 800; font-size: 1.15rem; text-decoration: none; color: #fff; }
        .brand span { color: var(--gold); }
        .nav { display: flex; gap: 6px; flex-wrap: wrap; }
        .nav a {
            padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: 600;
            font-size: 0.93rem; color: #cfe0f6;
        }
        .nav a:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }
        .nav a.current { background: rgba(255, 255, 255, 0.16); color: #fff; }

        .wrap { max-width: 1100px; margin: 0 auto; padding: 32px 24px 0; }

        .page-head { margin-bottom: 24px; }
        .page-head h1 { font-size: clamp(1.5rem, 3vw, 1.9rem); font-weight: 800; color: var(--navy); line-height: 1.25; }
        .page-head p { color: var(--muted); margin-top: 4px; }

        /* ---------- Thống kê nhanh ---------- */
        .stats {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px;
            background: var(--line); border: 1px solid var(--line); border-radius: 14px;
            overflow: hidden; margin-bottom: 24px;
        }
        .stat { background: #fff; padding: 18px 20px; }
        .stat-value { font-size: 1.8rem; font-weight: 800; color: var(--navy); line-height: 1.2; font-variant-numeric: tabular-nums; }
        .stat-value small { font-size: 0.95rem; font-weight: 600; color: var(--muted); }
        .stat-label { color: var(--muted); font-size: 0.88rem; }

        /* ---------- Bảng lịch sử ---------- */
        .panel { background: #fff; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
        .panel-head { padding: 18px 22px; border-bottom: 1px solid var(--line); }
        .panel-head h2 { font-size: 1.1rem; font-weight: 700; color: var(--navy); }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 18px; text-align: left; vertical-align: middle; }
        thead th {
            font-size: 0.82rem; font-weight: 700; color: var(--muted);
            background: #f6f9fd; border-bottom: 1px solid var(--line); white-space: nowrap;
        }
        tbody td { border-bottom: 1px solid #eaf1fa; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fbff; }
        .col-index { color: var(--muted); font-variant-numeric: tabular-nums; width: 56px; }
        .exam-name { font-weight: 700; color: var(--navy); }
        .muted { color: var(--muted); font-size: 0.93rem; }

        .score { font-size: 1.15rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        .score.is-pass { color: var(--ok); }
        .score.is-fail { color: var(--bad); }
        .score-max { color: var(--muted); font-size: 0.88rem; margin-left: 2px; }

        .badge {
            display: inline-flex; align-items: center; gap: 7px; padding: 3px 12px; border-radius: 999px;
            font-size: 0.82rem; font-weight: 700; white-space: nowrap;
        }
        .badge::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .badge-pass { background: var(--ok-bg); color: var(--ok); }
        .badge-fail { background: var(--bad-bg); color: var(--bad); }

        .btn-detail {
            display: inline-block; padding: 7px 14px; border-radius: 8px; border: 1px solid #b9d3f5;
            background: #fff; color: var(--blue-dark); font-weight: 700; font-size: 0.86rem;
            text-decoration: none; white-space: nowrap; transition: background 0.15s;
        }
        .btn-detail:hover { background: #eef5ff; }

        .empty { text-align: center; padding: 52px 20px; color: var(--muted); }
        .empty strong { display: block; color: var(--navy); font-size: 1.05rem; margin-bottom: 4px; }
        .btn-primary {
            display: inline-block; margin-top: 16px; padding: 10px 22px; border-radius: 10px;
            background: var(--blue); color: #fff; font-weight: 700; text-decoration: none;
        }
        .btn-primary:hover { background: var(--blue-dark); }

        /* ---------- Chi tiết bài làm ---------- */
        .back-link {
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 18px;
            color: var(--blue-dark); font-weight: 700; text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }

        .detail-head {
            display: flex; justify-content: space-between; align-items: center; gap: 24px; flex-wrap: wrap;
            background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 26px 28px; margin-bottom: 18px;
        }
        .detail-head h1 { font-size: clamp(1.3rem, 2.6vw, 1.7rem); font-weight: 800; color: var(--navy); line-height: 1.3; }
        .chips { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .chip {
            padding: 4px 12px; border-radius: 999px; background: #eef4fc; color: var(--muted);
            font-size: 0.86rem; font-weight: 600;
        }
        .chip strong { color: var(--navy); font-weight: 700; }

        .score-ring {
            --c: var(--ok);
            width: 118px; height: 118px; border-radius: 50%; flex-shrink: 0;
            background: conic-gradient(var(--c) calc(var(--p) * 1%), #e6eef9 0);
            display: grid; place-items: center;
        }
        .score-ring.is-fail { --c: var(--bad); }
        .score-ring-inner {
            width: 92px; height: 92px; border-radius: 50%; background: #fff;
            display: grid; place-content: center; text-align: center; line-height: 1.15;
        }
        .score-ring-inner strong { font-size: 1.75rem; font-weight: 800; color: var(--navy); }
        .score-ring-inner span { font-size: 0.8rem; color: var(--muted); font-weight: 600; }

        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 26px; }
        .summary-item { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 14px 18px; }
        .summary-item strong { display: block; font-size: 1.5rem; font-weight: 800; line-height: 1.2; }
        .summary-item span { color: var(--muted); font-size: 0.88rem; }
        .summary-item.correct strong { color: var(--ok); }
        .summary-item.wrong strong { color: var(--bad); }
        .summary-item.blank strong { color: var(--muted); }

        .section-title { font-size: 1.15rem; font-weight: 800; color: var(--navy); margin-bottom: 14px; }

        .question-card {
            background: #fff; border: 1px solid var(--line); border-radius: 14px;
            padding: 22px 24px; margin-bottom: 16px;
        }
        .question-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
        .question-title { font-weight: 700; font-size: 1.04rem; color: var(--navy); line-height: 1.5; }
        .question-title .num { color: var(--blue); margin-right: 6px; white-space: nowrap; }

        .state { padding: 3px 12px; border-radius: 999px; font-size: 0.8rem; font-weight: 700; white-space: nowrap; flex-shrink: 0; }
        .state-correct { background: var(--ok-bg); color: var(--ok); }
        .state-wrong { background: var(--bad-bg); color: var(--bad); }
        .state-blank { background: #eef2f7; color: var(--muted); }

        .options { display: flex; flex-direction: column; gap: 10px; }
        .option {
            display: flex; align-items: center; gap: 14px; padding: 11px 14px;
            border: 1px solid var(--line); border-radius: 10px; background: #fff;
        }
        .option-key {
            width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0; display: grid; place-items: center;
            background: #eef4fc; color: var(--muted); font-weight: 800; font-size: 0.88rem;
        }
        .option-text { flex: 1; }
        .option-tag { font-size: 0.8rem; font-weight: 700; white-space: nowrap; }

        .option.is-correct { background: var(--ok-bg); border-color: var(--ok-line); }
        .option.is-correct .option-key { background: var(--ok); color: #fff; }
        .option.is-correct .option-tag { color: var(--ok); }
        .option.is-wrong { background: var(--bad-bg); border-color: var(--bad-line); }
        .option.is-wrong .option-key { background: var(--bad); color: #fff; }
        .option.is-wrong .option-tag { color: var(--bad); }

        .explanation {
            margin-top: 16px; padding: 12px 16px; background: #fff8eb;
            border-left: 4px solid var(--gold); border-radius: 0 10px 10px 0;
            color: #5b4310; font-size: 0.95rem;
        }
        .explanation strong { color: #7a4f00; }

        /* ---------- Điện thoại ---------- */
        @media (max-width: 760px) {
            .wrap { padding: 24px 16px 0; }
            .topbar-inner { padding: 12px 16px; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            .summary { grid-template-columns: 1fr; }
            .detail-head { padding: 20px; }
            .question-card { padding: 18px 16px; }
            .question-top { flex-direction: column; gap: 8px; }

            .panel table, .panel tbody, .panel tr, .panel td { display: block; }
            .panel thead { position: absolute; left: -9999px; }
            .panel tbody tr { padding: 12px 0; border-bottom: 1px solid var(--line); }
            .panel tbody tr:last-child { border-bottom: none; }
            .panel tbody td {
                border: none; padding: 5px 18px; display: flex; justify-content: space-between;
                align-items: center; gap: 12px; text-align: right;
            }
            .panel tbody td::before { content: attr(data-label); color: var(--muted); font-size: 0.82rem; font-weight: 600; text-align: left; }
            .panel tbody td.col-index { display: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="index.php">Test<span>Su</span>Viet</a>
        <nav class="nav" aria-label="Menu chính">
            <a href="index.php">Trang chủ</a>
            <?php if ($is_teacher): ?>
                <a href="teacher_dashboard.php">Bảng điều khiển</a>
            <?php endif; ?>
            <a href="history.php" class="current" aria-current="page">Lịch sử làm bài</a>
        </nav>
    </div>
</header>

<main class="wrap">

<?php if ($attempt_detail): ?>
    <?php
        $detail_score = (float)$attempt_detail['score'];
        $detail_pass = $detail_score >= 5;
        $ring_percent = (int)round(min(10, max(0, $detail_score)) * 10);
    ?>

    <a href="history.php" class="back-link"><span aria-hidden="true">←</span> Quay lại danh sách lịch sử</a>

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