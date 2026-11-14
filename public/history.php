<?php
require_once __DIR__ . '/../api/be_history.php';
include __DIR__ . '/../includes/menu.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử làm bài - TestSuViet</title>

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