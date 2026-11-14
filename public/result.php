<?php
require_once __DIR__ . '/../api/be_result.php';
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
    <?php $n = 0; foreach ($details as $detail): $n++; ?>
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