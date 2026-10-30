<?php
// FILE: config/exam_attempts.php
// Các hàm dùng chung để quản lý lượt làm bài (exam_results.status = 'IN_PROGRESS').
// Lượt làm bài được tạo ngay khi học viên bắt đầu thi, nên thoát giữa chừng vẫn bị tính 1 lượt.

// Số giây cho phép nộp trễ sau khi hết giờ (phòng trường hợp mạng chậm).
const EXAM_SUBMIT_GRACE_SECONDS = 120;

/**
 * Tìm lượt làm bài đang diễn ra của học viên cho một đề thi.
 * Trả về ['result_id' => ..., 'elapsed' => số giây đã trôi qua] hoặc null.
 */
function find_in_progress_attempt(PDO $conn, int $user_id, int $exam_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT result_id, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS elapsed
         FROM exam_results
         WHERE user_id = :uid AND exam_id = :eid AND status = 'IN_PROGRESS'
         ORDER BY result_id DESC
         LIMIT 1"
    );
    $stmt->execute(['uid' => $user_id, 'eid' => $exam_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Đóng mọi lượt làm dở đã quá hạn (hết giờ + thời gian nộp trễ): tính FAILED, 0 điểm.
 */
function close_expired_attempts(PDO $conn): void
{
    $conn->exec(
        "UPDATE exam_results er
         JOIN exam e ON e.exam_id = er.exam_id
         SET er.status = 'FAILED',
             er.score = 0,
             er.correct_answers_count = 0,
             er.completion_time = e.duration * 60,
             er.submitted_at = NOW()
         WHERE er.status = 'IN_PROGRESS'
           AND TIMESTAMPDIFF(SECOND, er.created_at, NOW())
               > (e.duration * 60 + " . EXAM_SUBMIT_GRACE_SECONDS . ")"
    );
}

/**
 * Tạo lượt làm bài mới (tính lượt ngay khi bắt đầu). Trả về result_id.
 */
function start_attempt(PDO $conn, int $user_id, int $exam_id, int $total_questions): int
{
    $stmt = $conn->prepare(
        "INSERT INTO exam_results
            (user_id, exam_id, score, correct_answers_count, total_questions, completion_time, status)
         VALUES (:uid, :eid, 0, 0, :tq, 0, 'IN_PROGRESS')"
    );
    $stmt->execute(['uid' => $user_id, 'eid' => $exam_id, 'tq' => $total_questions]);

    return (int) $conn->lastInsertId();
}