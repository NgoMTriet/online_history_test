<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

$message = '';
$messageType = '';

// Tự động bổ sung cột phạm vi & bảng liên kết teacher_subjects nếu chưa có
try {
    $colUserSchool = $conn->query("SHOW COLUMNS FROM `users` LIKE 'school_name'");
    if ($colUserSchool && $colUserSchool->rowCount() === 0) {
        $conn->exec("ALTER TABLE `users` ADD COLUMN `school_name` VARCHAR(255) NULL AFTER `email`");
    }

    $colVis = $conn->query("SHOW COLUMNS FROM `exam` LIKE 'visibility'");
    if ($colVis && $colVis->rowCount() === 0) {
        $conn->exec("ALTER TABLE `exam` ADD COLUMN `visibility` ENUM('PUBLIC', 'SCHOOL', 'GRADE', 'CLASS') NOT NULL DEFAULT 'PUBLIC' AFTER `exam_type`");
    }

    $colSchool = $conn->query("SHOW COLUMNS FROM `exam` LIKE 'school_name'");
    if ($colSchool && $colSchool->rowCount() === 0) {
        $conn->exec("ALTER TABLE `exam` ADD COLUMN `school_name` VARCHAR(255) NULL AFTER `visibility`");
    }

    $colClasses = $conn->query("SHOW COLUMNS FROM `exam` LIKE 'target_classes'");
    if ($colClasses && $colClasses->rowCount() === 0) {
        $conn->exec("ALTER TABLE `exam` ADD COLUMN `target_classes` VARCHAR(255) NULL AFTER `school_name`");
    }

    // Đảm bảo subjects có đủ môn Lịch sử cho khối 6, 7, 8, 9
    $cntHist = (int)$conn->query("SELECT COUNT(*) FROM subjects WHERE LOWER(TRIM(subject_name)) = 'lịch sử'")->fetchColumn();
    if ($cntHist === 0) {
        $stmtSeed = $conn->prepare("INSERT INTO subjects (subject_name, grade) VALUES ('Lịch sử', ?)");
        foreach ([6, 7, 8, 9] as $gSeed) {
            $stmtSeed->execute([$gSeed]);
        }
    }

    $conn->exec(
        "CREATE TABLE IF NOT EXISTS teacher_subjects (
            user_id INT NOT NULL,
            subject_id INT NOT NULL,
            PRIMARY KEY (user_id, subject_id),
            CONSTRAINT fk_ts_teacher_profile
                FOREIGN KEY (user_id) REFERENCES teacher_profiles(user_id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_ts_subject
                FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
}

function normalizeClassLabel(string $raw): string
{
    $clean = preg_replace('/^lớp\s*/iu', '', trim($raw));
    $clean = preg_replace('/\s+/', '', $clean);
    return mb_strtoupper($clean, 'UTF-8');
}

function extractGradeFromClass(string $className): int
{
    $norm = normalizeClassLabel($className);
    if (preg_match('/^([6-9])/', $norm, $m)) {
        return (int)$m[1];
    }
    return 0;
}

function normalizeSubjectName(?string $name, int $subjectId = 0): string
{
    $norm = mb_strtolower(trim((string)$name), 'UTF-8');
    if ($norm !== '') {
        return $norm;
    }
    if ($subjectId <= 0) {
        return 'lịch sử';
    }
    return '';
}

// 1. Lấy thông tin giáo viên từ teacher_profiles & bảng liên kết teacher_subjects
$teacherSchoolName   = '';
$teacherGrades       = [];
$teacherSubjects     = [];
$linkedSubjectRows   = [];

if ($userId > 0) {
    try {
        $stmtTp = $conn->prepare(
            "SELECT school_name, teaching_grades, teaching_subjects
             FROM teacher_profiles
             WHERE user_id = :uid
             LIMIT 1"
        );
        $stmtTp->execute(['uid' => $userId]);
        $tpRow = $stmtTp->fetch(PDO::FETCH_ASSOC);

        if ($tpRow) {
            $teacherSchoolName = trim((string)($tpRow['school_name'] ?? ''));

            $rawGrades = trim((string)($tpRow['teaching_grades'] ?? ''));
            if ($rawGrades !== '') {
                $decodedGrades = json_decode($rawGrades, true);
                if (is_array($decodedGrades)) {
                    $teacherGrades = array_map('intval', $decodedGrades);
                } else {
                    preg_match_all('/\b([6-9])\b/', $rawGrades, $m);
                    if (!empty($m[1])) {
                        $teacherGrades = array_map('intval', $m[1]);
                    }
                }
                $teacherGrades = array_values(array_unique(array_intersect([6, 7, 8, 9], $teacherGrades)));
                sort($teacherGrades);
            }

            $rawSubjects = trim((string)($tpRow['teaching_subjects'] ?? ''));
            if ($rawSubjects !== '') {
                $decodedSubjects = json_decode($rawSubjects, true);
                if (is_array($decodedSubjects)) {
                    $teacherSubjects = array_values(array_filter(array_map('trim', $decodedSubjects), fn($s) => $s !== ''));
                } else {
                    $teacherSubjects = array_values(array_filter(array_map('trim', explode(',', $rawSubjects)), fn($s) => $s !== ''));
                }
            }
        }

        // Lấy các môn đã liên kết trực tiếp qua khóa ngoại teacher_subjects -> subjects
        $stmtLinked = $conn->prepare(
            "SELECT s.subject_id, TRIM(s.subject_name) AS subject_name, IFNULL(s.grade, 0) AS grade
             FROM teacher_subjects ts
             JOIN subjects s ON ts.subject_id = s.subject_id
             WHERE ts.user_id = :uid
             ORDER BY s.grade ASC, s.subject_name ASC"
        );
        $stmtLinked->execute(['uid' => $userId]);
        $linkedSubjectRows = $stmtLinked->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// 2. Xác định danh sách môn học hiển thị (không bao giờ để trống subject)
$rawSubjectsDb = $conn->query(
    "SELECT subject_id, TRIM(subject_name) AS subject_name, IFNULL(grade, 0) AS grade
     FROM subjects
     WHERE subject_name IS NOT NULL AND TRIM(subject_name) <> ''
     ORDER BY grade ASC, subject_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$hasProfileSubjects = false;
$allSubjects = [];

if (!empty($linkedSubjectRows)) {
    $allSubjects = $linkedSubjectRows;
    $hasProfileSubjects = true;
} elseif (!empty($teacherSubjects)) {
    $teacherSubjectsNorm = array_map(fn($s) => mb_strtolower(trim($s), 'UTF-8'), $teacherSubjects);
    foreach ($rawSubjectsDb as $subj) {
        $sNorm = mb_strtolower(trim((string)$subj['subject_name']), 'UTF-8');
        if (in_array($sNorm, $teacherSubjectsNorm, true)) {
            $allSubjects[] = $subj;
        }
    }
    if (!empty($allSubjects)) {
        $hasProfileSubjects = true;
    }
}

if (empty($allSubjects)) {
    $allSubjects = $rawSubjectsDb;
}

// Danh sách khối hiển thị: ưu tiên theo teacher_profiles, dự phòng đủ [6, 7, 8, 9]
$gradeOptions = !empty($teacherGrades) ? $teacherGrades : [6, 7, 8, 9];
sort($gradeOptions);

$defaultGrade = $gradeOptions[0];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    $postedGrade = (int)$_POST['grade'];
    if (in_array($postedGrade, $gradeOptions, true)) {
        $defaultGrade = $postedGrade;
    }
}

$defaultSubjectId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject_id'])) {
    $postedSubjId = (int)$_POST['subject_id'];
    foreach ($allSubjects as $subj) {
        if ((int)$subj['subject_id'] === $postedSubjId) {
            $defaultSubjectId = $postedSubjId;
            break;
        }
    }
}
if ($defaultSubjectId === 0) {
    foreach ($allSubjects as $subj) {
        if ((int)$subj['grade'] === $defaultGrade || (int)$subj['grade'] === 0) {
            $defaultSubjectId = (int)$subj['subject_id'];
            break;
        }
    }
    if ($defaultSubjectId === 0 && !empty($allSubjects)) {
        $defaultSubjectId = (int)$allSubjects[0]['subject_id'];
    }
}

// 3. Lấy danh sách lớp học sinh và trường học hiện có
$availableClassItems = [];
$availableSchools    = [];
try {
    $stmtCls = $conn->query(
        "SELECT DISTINCT TRIM(class_name) AS class_name, TRIM(IFNULL(school_name, '')) AS school_name
         FROM users
         WHERE role = 'STUDENT' AND class_name IS NOT NULL AND TRIM(class_name) <> ''
         ORDER BY class_name ASC"
    );
    $seenClassKeys = [];
    foreach ($stmtCls->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $normClass = normalizeClassLabel((string)$row['class_name']);
        if ($normClass === '') continue;

        $sch = trim((string)$row['school_name']);
        $key = mb_strtolower($sch . '|' . $normClass, 'UTF-8');
        if (isset($seenClassKeys[$key])) continue;
        $seenClassKeys[$key] = true;

        $availableClassItems[] = [
            'class_name'  => $normClass,
            'grade'       => extractGradeFromClass($normClass),
            'school_name' => $sch,
        ];
    }

    usort($availableClassItems, fn($a, $b) => strnatcasecmp($a['class_name'], $b['class_name']));

    $stmtSch = $conn->query(
        "SELECT DISTINCT TRIM(school_name) AS school_name
         FROM teacher_profiles
         WHERE school_name IS NOT NULL AND TRIM(school_name) <> ''
         ORDER BY school_name ASC"
    );
    $availableSchools = $stmtSch->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

// 4. Xử lý tạo đề thi khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_exam') {
    $title        = trim($_POST['title'] ?? '');
    $grade        = (int)($_POST['grade'] ?? $defaultGrade);
    $subjectId    = (int)($_POST['subject_id'] ?? $defaultSubjectId);
    $examType     = trim($_POST['exam_type'] ?? 'REGULAR');
    $visibility   = trim($_POST['visibility'] ?? 'PUBLIC');
    $schoolName   = trim($_POST['school_name'] ?? '');
    $duration     = (int)($_POST['duration'] ?? 15);
    $examPassword = trim($_POST['exam_password'] ?? '');
    $mode         = $_POST['create_mode'] ?? 'manual';

    $checkedClasses = $_POST['target_class_list'] ?? [];
    if (!is_array($checkedClasses)) {
        $checkedClasses = [];
    }
    $customClassesRaw = trim($_POST['custom_target_classes'] ?? '');
    $customClasses = $customClassesRaw !== ''
        ? array_map('trim', explode(',', $customClassesRaw))
        : [];

    $mergedClasses = [];
    foreach (array_merge($checkedClasses, $customClasses) as $rawCls) {
        $normCls = normalizeClassLabel((string)$rawCls);
        if ($normCls !== '' && !in_array($normCls, $mergedClasses, true)) {
            $mergedClasses[] = $normCls;
        }
    }
    natcasesort($mergedClasses);
    $mergedClasses = array_values($mergedClasses);
    $targetClassesStr = !empty($mergedClasses) ? implode(', ', $mergedClasses) : null;

    $startTimeRaw = trim($_POST['start_time'] ?? '');
    $startTime = null;
    if ($startTimeRaw !== '') {
        $timestampStart = strtotime($startTimeRaw);
        if ($timestampStart !== false) {
            $startTime = date('Y-m-d H:i:s', $timestampStart);
        }
    }

    $endTimeRaw = trim($_POST['end_time'] ?? '');
    $endTime = null;
    if ($endTimeRaw !== '') {
        $timestampEnd = strtotime($endTimeRaw);
        if ($timestampEnd !== false) {
            $endTime = date('Y-m-d H:i:s', $timestampEnd);
        }
    }

    $subjectValid = false;
    $selectedSubjectNorm = '';
    if ($subjectId > 0) {
        $stmtSubject = $conn->prepare("SELECT subject_name FROM subjects WHERE subject_id = :sid LIMIT 1");
        $stmtSubject->execute(['sid' => $subjectId]);
        $subjRow = $stmtSubject->fetch(PDO::FETCH_ASSOC);
        if ($subjRow) {
            $subjectValid = true;
            $selectedSubjectNorm = mb_strtolower(trim((string)$subjRow['subject_name']), 'UTF-8');
        }
    }

    $allowedExamTypes  = ['REGULAR', 'MIDTERM_1', 'FINAL_1', 'MIDTERM_2', 'FINAL_2'];
    $allowedVisibility = ['PUBLIC', 'SCHOOL', 'GRADE', 'CLASS'];

    if ($userId <= 0) {
        $message = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
        $messageType = 'danger';
    } elseif ($title === '') {
        $message = 'Vui lòng nhập tên đề thi.';
        $messageType = 'danger';
    } elseif (!in_array($grade, [6, 7, 8, 9], true)) {
        $message = 'Khối lớp không hợp lệ.';
        $messageType = 'danger';
    } elseif (!$subjectValid) {
        $message = 'Vui lòng chọn môn học hợp lệ.';
        $messageType = 'danger';
    } elseif (!in_array($examType, $allowedExamTypes, true)) {
        $message = 'Loại kỳ thi không hợp lệ.';
        $messageType = 'danger';
    } elseif (!in_array($visibility, $allowedVisibility, true)) {
        $message = 'Phạm vi hiển thị đề thi không hợp lệ.';
        $messageType = 'danger';
    } elseif (in_array($visibility, ['SCHOOL', 'GRADE', 'CLASS'], true) && $schoolName === '') {
        $message = 'Vui lòng nhập hoặc chọn trường áp dụng cho đề thi.';
        $messageType = 'danger';
    } elseif ($visibility === 'CLASS' && $targetClassesStr === null) {
        $message = 'Vui lòng chọn hoặc nhập ít nhất một lớp ra đề.';
        $messageType = 'danger';
    } elseif ($duration < 1 || $duration > 300) {
        $message = 'Thời gian làm bài phải từ 1 đến 300 phút.';
        $messageType = 'danger';
    } elseif ($startTime !== null && $endTime !== null && strtotime($endTime) <= strtotime($startTime)) {
        $message = 'Thời gian đóng đề thi phải sau thời gian mở đề thi.';
        $messageType = 'danger';
    } elseif (!in_array($mode, ['manual', 'auto'], true)) {
        $message = 'Phương thức chọn câu hỏi không hợp lệ.';
        $messageType = 'danger';
    } else {
        try {
            $selectedQuestionIds = [];
            $isHistorySubject = (mb_strpos($selectedSubjectNorm, 'lịch sử') !== false);

            $subjectMatchSql = $isHistorySubject
                ? "(t.subject_id = ? OR LOWER(TRIM(IFNULL(ts.subject_name, ''))) = ? OR t.subject_id IS NULL OR t.subject_id = 0)"
                : "(t.subject_id = ? OR LOWER(TRIM(IFNULL(ts.subject_name, ''))) = ?)";

            if ($mode === 'manual') {
                $rawIds = $_POST['question_ids'] ?? [];
                if (!is_array($rawIds)) {
                    $rawIds = [];
                }

                $selectedQuestionIds = array_values(
                    array_unique(
                        array_filter(array_map('intval', $rawIds), fn($id) => $id > 0)
                    )
                );

                if (count($selectedQuestionIds) > 0) {
                    $placeholders = implode(',', array_fill(0, count($selectedQuestionIds), '?'));
                    $stmtCheck = $conn->prepare(
                        "SELECT q.question_id
                         FROM questions q
                         JOIN topics t ON q.topic_id = t.topic_id
                         LEFT JOIN subjects ts ON t.subject_id = ts.subject_id
                         WHERE t.grade = ?
                         AND {$subjectMatchSql}
                         AND q.user_id = ?
                         AND q.question_id IN ($placeholders)"
                    );
                    $stmtCheck->execute(array_merge([$grade, $subjectId, $selectedSubjectNorm, $userId], $selectedQuestionIds));
                    $validIds = array_map('intval', $stmtCheck->fetchAll(PDO::FETCH_COLUMN));
                    $selectedQuestionIds = array_values(array_intersect($selectedQuestionIds, $validIds));
                }
            } else {
                $topicId     = (int)($_POST['auto_topic_id'] ?? 0);
                $countEasy   = max(0, (int)($_POST['count_easy'] ?? 0));
                $countMedium = max(0, (int)($_POST['count_medium'] ?? 0));
                $countHard   = max(0, (int)($_POST['count_hard'] ?? 0));

                if ($countEasy + $countMedium + $countHard === 0) {
                    throw new Exception('Vui lòng nhập số lượng cho ít nhất 1 mức độ câu hỏi.');
                }

                $levelsMap = [
                    'EASY'   => $countEasy,
                    'MEDIUM' => $countMedium,
                    'HARD'   => $countHard
                ];

                $autoSubjectSql = $isHistorySubject
                    ? "(t.subject_id = :subject_id OR LOWER(TRIM(IFNULL(ts.subject_name, ''))) = :subject_name OR t.subject_id IS NULL OR t.subject_id = 0)"
                    : "(t.subject_id = :subject_id OR LOWER(TRIM(IFNULL(ts.subject_name, ''))) = :subject_name)";

                foreach ($levelsMap as $level => $count) {
                    if ($count <= 0) continue;

                    $sql = "SELECT q.question_id
                            FROM questions q
                            JOIN topics t ON q.topic_id = t.topic_id
                            LEFT JOIN subjects ts ON t.subject_id = ts.subject_id
                            WHERE t.grade = :grade 
                            AND {$autoSubjectSql}
                            AND q.user_id = :user_id 
                            AND q.difficulty = :level";

                    if ($topicId > 0) {
                        $sql .= " AND q.topic_id = :topic_id";
                    }
                    $sql .= " ORDER BY RAND() LIMIT :limit";

                    $stmtRandom = $conn->prepare($sql);
                    $stmtRandom->bindValue(':grade', $grade, PDO::PARAM_INT);
                    $stmtRandom->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
                    $stmtRandom->bindValue(':subject_name', $selectedSubjectNorm, PDO::PARAM_STR);
                    $stmtRandom->bindValue(':user_id', $userId, PDO::PARAM_INT);
                    $stmtRandom->bindValue(':level', $level, PDO::PARAM_STR);
                    $stmtRandom->bindValue(':limit', $count, PDO::PARAM_INT);

                    if ($topicId > 0) {
                        $stmtRandom->bindValue(':topic_id', $topicId, PDO::PARAM_INT);
                    }

                    $stmtRandom->execute();
                    $fetchedIds = array_map('intval', $stmtRandom->fetchAll(PDO::FETCH_COLUMN));
                    $selectedQuestionIds = array_merge($selectedQuestionIds, $fetchedIds);
                }
            }

            $totalQuestions = count($selectedQuestionIds);
            if ($totalQuestions === 0) {
                throw new Exception('Không tìm thấy câu hỏi phù hợp với cấu hình.');
            }

            $saveSchoolName    = ($visibility !== 'PUBLIC' && $schoolName !== '') ? $schoolName : null;
            $saveTargetClasses = ($visibility === 'CLASS') ? $targetClassesStr : null;

            $conn->beginTransaction();

            $stmtExam = $conn->prepare(
                "INSERT INTO exam
                 (title, grade, subject_id, exam_type, visibility, school_name, target_classes, duration, start_time, end_time, exam_password, total_questions, is_randomized, user_id)
                 VALUES (:title, :grade, :subject_id, :exam_type, :visibility, :school_name, :target_classes, :duration, :start_time, :end_time, :exam_password, :total_questions, :is_randomized, :user_id)"
            );

            $stmtExam->execute([
                'title'           => $title,
                'grade'           => $grade,
                'subject_id'      => $subjectId,
                'exam_type'       => $examType,
                'visibility'      => $visibility,
                'school_name'     => $saveSchoolName,
                'target_classes'  => $saveTargetClasses,
                'duration'        => $duration,
                'start_time'      => $startTime,
                'end_time'        => $endTime,
                'exam_password'   => $examPassword !== '' ? $examPassword : null,
                'total_questions' => $totalQuestions,
                'is_randomized'   => ($mode === 'auto') ? 1 : 0,
                'user_id'         => $userId
            ]);

            $examId = (int)$conn->lastInsertId();

            $stmtLink = $conn->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (:exam_id, :question_id)");
            foreach ($selectedQuestionIds as $questionId) {
                $stmtLink->execute(['exam_id' => $examId, 'question_id' => $questionId]);
            }

            $conn->commit();
            $message = "Tạo đề thi thành công! Đề thi gồm {$totalQuestions} câu hỏi.";
            $messageType = 'success';
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// 5. Lấy chủ đề và câu hỏi
$stmtTopics = $conn->prepare(
    "SELECT t.topic_id, t.topic_name, t.grade, t.subject_id, ts.subject_name
     FROM topics t
     LEFT JOIN subjects ts ON t.subject_id = ts.subject_id
     WHERE t.user_id = :user_id
     ORDER BY t.grade ASC, t.topic_name ASC"
);
$stmtTopics->execute(['user_id' => $userId]);
$allTopics = $stmtTopics->fetchAll(PDO::FETCH_ASSOC);
foreach ($allTopics as &$tItem) {
    $tItem['subject_norm'] = normalizeSubjectName($tItem['subject_name'] ?? null, (int)($tItem['subject_id'] ?? 0));
}
unset($tItem);

$stmtQuestions = $conn->prepare(
    "SELECT q.question_id, q.content, q.difficulty, t.grade, t.topic_name, t.topic_id, t.subject_id, ts.subject_name
     FROM questions q
     JOIN topics t ON q.topic_id = t.topic_id
     LEFT JOIN subjects ts ON t.subject_id = ts.subject_id
     WHERE q.user_id = :user_id
     ORDER BY t.grade ASC, q.question_id DESC"
);
$stmtQuestions->execute(['user_id' => $userId]);

$questionsBank = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);
$questionCounts = [6 => 0, 7 => 0, 8 => 0, 9 => 0];
$jsQuestionsData = [];

foreach ($questionsBank as &$question) {
    $question['subject_norm'] = normalizeSubjectName($question['subject_name'] ?? null, (int)($question['subject_id'] ?? 0));
    $g = (int)$question['grade'];
    if (isset($questionCounts[$g])) {
        $questionCounts[$g]++;
    }
    $jsQuestionsData[] = [
        'g' => (int)$question['grade'],
        't' => (int)$question['topic_id'],
        's' => (int)$question['subject_id'],
        'd' => strtoupper($question['difficulty'] ?? 'EASY')
    ];
}
unset($question);