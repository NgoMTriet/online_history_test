<?php
// FILE: public/result.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/exam_attempts.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (empty($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập lại.']);
        exit;
    }
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$is_post_submit = $_SERVER['REQUEST_METHOD'] === 'POST'
    && (($_POST['action'] ?? '') === 'submit_exam');

// Học viên chủ động bấm "Thoát bài thi": kết thúc lượt làm bài (vẫn tính 1 lượt, 0 điểm).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'abandon_exam')) {
    header('Content-Type: application/json');

    $exam_id = (int) ($_POST['exam_id'] ?? 0);
    $tab_switches = max(0, min(999, (int) ($_POST['tab_switches'] ?? 0)));

    try {
        $attempt = $exam_id > 0 ? find_in_progress_attempt($conn, $user_id, $exam_id) : null;

        if ($attempt) {
            $stmt_abandon = $conn->prepare(
                "UPDATE exam_results er
                 JOIN exam e ON e.exam_id = er.exam_id
                 SET er.status = 'FAILED',
                     er.score = 0,
                     er.correct_answers_count = 0,
                     er.completion_time = LEAST(TIMESTAMPDIFF(SECOND, er.created_at, NOW()), e.duration * 60),
                     er.tab_switches = :tab_switches,
                     er.submitted_at = NOW()
                 WHERE er.result_id = :rid AND er.user_id = :uid AND er.status = 'IN_PROGRESS'"
            );
            $stmt_abandon->execute([
                'rid' => (int) $attempt['result_id'],
                'uid' => $user_id,
                'tab_switches' => $tab_switches
            ]);
        }

        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        error_log('Lỗi thoát bài thi: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false]);
    }
    exit;
}

if ($is_post_submit) {
    header('Content-Type: application/json');

    $exam_id = (int) ($_POST['exam_id'] ?? 0);
    $user_answers = $_POST['answers'] ?? [];
    $completion_time = max(0, (int) ($_POST['completion_time'] ?? 0));
    $tab_switches = max(0, min(999, (int) ($_POST['tab_switches'] ?? 0)));
    
    if ($exam_id <= 0 || !is_array($user_answers)) {
        echo json_encode(['success' => false, 'message' => 'Thông tin bài thi không hợp lệ.']);
        exit;
    }

    try {
        // 1. Kiểm tra thông tin đề thi
        $stmt_exam = $conn->prepare('SELECT title, duration FROM exam WHERE exam_id = :eid LIMIT 1');
        $stmt_exam->execute(['eid' => $exam_id]);
        $exam_info = $stmt_exam->fetch(PDO::FETCH_ASSOC);
        if (!$exam_info) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đề thi.']);
            exit;
        }

        $duration_seconds = max(1, (int) $exam_info['duration']) * 60;

        // 2. Lấy lượt làm bài đang diễn ra (đã được tạo và tính lượt từ lúc bắt đầu thi)
        $attempt = find_in_progress_attempt($conn, $user_id, $exam_id);
        if (!$attempt) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy lượt làm bài đang diễn ra hoặc bài đã được nộp. Vui lòng quay lại trang đề thi.'
            ]);
            exit;
        }

        $result_id = (int) $attempt['result_id'];
        $elapsed = (int) $attempt['elapsed'];

        // Nộp quá hạn (hết giờ + thời gian nộp trễ): ghi nhận FAILED, 0 điểm.
        if ($elapsed > $duration_seconds + EXAM_SUBMIT_GRACE_SECONDS) {
            $stmt_late = $conn->prepare(
                "UPDATE exam_results
                 SET status = 'FAILED', score = 0, correct_answers_count = 0,
                     completion_time = :ct, tab_switches = :tab_switches, submitted_at = NOW()
                 WHERE result_id = :rid AND status = 'IN_PROGRESS'"
            );
            $stmt_late->execute([
                'ct' => $duration_seconds,
                'tab_switches' => $tab_switches,
                'rid' => $result_id
            ]);

            $_SESSION['submitted_exam_result_' . $exam_id] = $result_id;
            $_SESSION['exam_completed_' . $exam_id] = true;

            echo json_encode([
                'success' => true,
                'redirect_url' => 'result.php?result_id=' . $result_id
            ]);
            exit;
        }

        // Thời gian làm bài tính theo máy chủ, không tin giá trị do trình duyệt gửi lên.
        $completion_time = min($elapsed, $duration_seconds);

        // 3. Lấy danh sách câu hỏi
        $stmt_qs = $conn->prepare('SELECT q.question_id, q.content AS question, q.explanation FROM questions q INNER JOIN exam_questions eq ON q.question_id = eq.question_id WHERE eq.exam_id = :eid');
        $stmt_qs->execute(['eid' => $exam_id]);
        $questions = $stmt_qs->fetchAll(PDO::FETCH_ASSOC);
        $total_questions = count($questions);

        if ($total_questions === 0) {
            echo json_encode(['success' => false, 'message' => 'Đề thi chưa có câu hỏi.']);
            exit;
        }

        // 4. Chấm điểm
        $correct_count = 0;
        $answer_rows = [];
        $stmt_ans = $conn->prepare('SELECT answer_id, is_correct FROM answers WHERE question_id = :qid');
        foreach ($questions as $q) {
            $qid = (int) $q['question_id'];
            $stmt_ans->execute(['qid' => $qid]);
            $answers = $stmt_ans->fetchAll(PDO::FETCH_ASSOC);
            $selected = $user_answers[$qid] ?? null;
            if ($selected === null || $selected === '') {
                $selected = null;
            } else {
                $selected = filter_var($selected, FILTER_VALIDATE_INT);
                if ($selected === false) $selected = null;
            }
            $valid = false;
            $correct = false;
            foreach ($answers as $a) {
                if ($selected !== null && (int)$a['answer_id'] === (int)$selected) {
                    $valid = true;
                    $correct = (int)$a['is_correct'] === 1;
                    break;
                }
            }
            if (!$valid) $selected = null;
            if ($correct) $correct_count++;
            $answer_rows[] = ['question_id'=>$qid, 'selected_answer_id'=>$selected, 'is_correct'=>$correct ? 1 : 0];
        }
        $score = round(($correct_count / $total_questions) * 10, 2);
        $status = $score >= 5 ? 'PASSED' : 'FAILED';

        // 5. Lưu kết quả: cập nhật lượt đang làm thay vì thêm dòng mới
        $conn->beginTransaction();
        $update = $conn->prepare(
            "UPDATE exam_results
             SET score = :score,
                 correct_answers_count = :correct_count,
                 total_questions = :total_count,
                 completion_time = :completion_time,
                 status = :status,
                 tab_switches = :tab_switches,
                 submitted_at = NOW()
             WHERE result_id = :rid AND user_id = :uid AND status = 'IN_PROGRESS'"
        );
        $update->execute([
            'score' => $score,
            'correct_count' => $correct_count,
            'total_count' => $total_questions,
            'completion_time' => $completion_time,
            'status' => $status,
            'tab_switches' => $tab_switches,
            'rid' => $result_id,
            'uid' => $user_id
        ]);

        // Không cập nhật được dòng nào: bài đã được nộp (hoặc đóng) trước đó.
        if ($update->rowCount() !== 1) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Bài thi này đã được nộp trước đó.']);
            exit;
        }

        $insertAnswer = $conn->prepare('INSERT INTO student_answers (result_id, question_id, selected_answer_id, is_correct) VALUES (:rid, :qid, :aid, :correct)');
        foreach ($answer_rows as $r) {
            $insertAnswer->execute([
                'rid' => $result_id,
                'qid' => $r['question_id'],
                'aid' => $r['selected_answer_id'],
                'correct' => $r['is_correct']
            ]);
        }
        $conn->commit();

        // Ghi nhận session
        $_SESSION['submitted_exam_result_' . $exam_id] = $result_id;
        $_SESSION['exam_completed_' . $exam_id] = true;

        // Trả về URL chuyển hướng dạng JSON
        echo json_encode([
            'success' => true,
            'redirect_url' => 'result.php?result_id=' . $result_id
        ]);
        exit;

    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('Lỗi nộp bài thi: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Không thể lưu kết quả bài thi.']);
        exit;
    }
} else {
    $result_id = (int) ($_GET['result_id'] ?? 0);
    if ($result_id <= 0) {
        header('Location: index.php');
        exit;
    }
}

// Đọc kết quả đã lưu và chỉ cho chính chủ xem.
$stmt_result = $conn->prepare("SELECT er.*, e.title AS exam_title FROM exam_results er INNER JOIN exam e ON e.exam_id = er.exam_id WHERE er.result_id = :rid AND er.user_id = :uid AND er.status <> 'IN_PROGRESS' LIMIT 1");
$stmt_result->execute(['rid'=>$result_id, 'uid'=>$user_id]);
$result = $stmt_result->fetch(PDO::FETCH_ASSOC);
if (!$result) {
    header('Location: index.php');
    exit;
}
$exam_id = (int)$result['exam_id'];
$correct_count = (int)$result['correct_answers_count'];
$total_questions = (int)$result['total_questions'];
$score = (float)$result['score'];
$completion_time = (int)$result['completion_time'];

// Chi tiết câu hỏi, đáp án đã chọn và đáp án đúng.
$stmt_details = $conn->prepare('SELECT q.question_id, q.content AS question, q.explanation, sa.selected_answer_id, sa.is_correct AS student_is_correct, a.answer_id, a.content AS answer_content, a.is_correct AS answer_is_correct FROM questions q INNER JOIN exam_questions eq ON eq.question_id = q.question_id LEFT JOIN student_answers sa ON sa.question_id = q.question_id AND sa.result_id = :rid LEFT JOIN answers a ON a.question_id = q.question_id WHERE eq.exam_id = :eid ORDER BY q.question_id');
$stmt_details->execute(['rid'=>$result_id, 'eid'=>$exam_id]);
$rows = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
$details = [];
foreach ($rows as $row) {
    $qid = (int)$row['question_id'];
    if (!isset($details[$qid])) {
        $details[$qid] = ['question'=>$row['question'], 'explanation'=>$row['explanation'], 'selected'=>(int)($row['selected_answer_id'] ?? 0), 'is_correct'=>(int)($row['student_is_correct'] ?? 0), 'answers'=>[]];
    }
    if ($row['answer_id'] !== null) {
        $details[$qid]['answers'][] = ['id'=>(int)$row['answer_id'], 'content'=>$row['answer_content'], 'correct'=>(int)$row['answer_is_correct'] === 1];
    }
}
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$minutes = intdiv($completion_time, 60);
$seconds = $completion_time % 60;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kết quả bài thi - <?= h($result['exam_title']) ?></title>
<link rel="stylesheet" href="/../assets/css/result.css">
</head>
<body>
<div class="wrap">
  <section class="panel summary">
    <h1>Kết quả bài thi</h1>
    <p><strong><?= h($result['exam_title']) ?></strong></p>
    <div class="score"><?= number_format($score, 2) ?>/10</div>
    <p class="<?= $score >= 5 ? 'passed' : 'failed' ?>"><strong><?= $score >= 5 ? 'ĐẠT' : 'CHƯA ĐẠT' ?></strong></p>
    <div class="stats">
      <div class="stat">Số câu đúng<strong><?= $correct_count ?>/<?= $total_questions ?></strong></div>
      <div class="stat">Số câu sai/chưa chọn<strong><?= max(0, $total_questions - $correct_count) ?></strong></div>
      <div class="stat">Thời gian làm bài<strong><?= sprintf('%02d:%02d', $minutes, $seconds) ?></strong></div>
    </div>
    <div class="actions"><a class="btn home" href="index.php">Về trang chủ</a><a class="btn history" href="history.php">Lịch sử làm bài</a></div>
  </section>
  <section class="panel">
    <h2>Chi tiết đáp án</h2>
    <?php if (!$details): ?><p>Không tìm thấy chi tiết câu hỏi của bài thi này.</p><?php endif; ?>
    <?php $n=0; foreach ($details as $detail): $n++; ?>
      <article class="question">
        <h3>Câu <?= $n ?>: <?= h($detail['question']) ?></h3>
        <?php foreach ($detail['answers'] as $answer):
          $chosen = $detail['selected'] === $answer['id'];
          $cls = $answer['correct'] ? 'correct' : ($chosen ? 'chosen-wrong' : '');
        ?>
          <div class="answer <?= $cls ?>">
            <?= h($answer['content']) ?>
            <?php if ($answer['correct']): ?><span class="tag">— Đáp án đúng</span><?php endif; ?>
            <?php if ($chosen): ?><span class="tag">— Bạn đã chọn</span><?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (!empty($detail['explanation'])): ?><div class="explanation"><strong>Giải thích:</strong> <?= nl2br(h($detail['explanation'])) ?></div><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>
</div>
</body>
</html>