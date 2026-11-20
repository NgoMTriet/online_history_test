<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (strtoupper($_SESSION['role'] ?? '') === 'TEACHER') {
    header('Location: teacher_dashboard.php');
    exit;
}

$rootDir = dirname(__DIR__);

$configPath = file_exists($rootDir . '/config/database.php')
    ? $rootDir . '/config/database.php'
    : $rootDir . '/../config/database.php';

$headerPath = file_exists($rootDir . '/includes/header.php')
    ? $rootDir . '/includes/header.php'
    : $rootDir . '/../includes/header.php';

$footerPath = file_exists($rootDir . '/includes/footer.php')
    ? $rootDir . '/includes/footer.php'
    : $rootDir . '/../includes/footer.php';

require_once $configPath;

// Tự động bổ sung cột còn thiếu nếu CSDL chưa chạy ALTER TABLE
try {
    $colSchoolUser = $conn->query("SHOW COLUMNS FROM `users` LIKE 'school_name'");
    if ($colSchoolUser && $colSchoolUser->rowCount() === 0) {
        $conn->exec("ALTER TABLE `users` ADD COLUMN `school_name` VARCHAR(255) NULL AFTER `email`");
    }

    $colVisExam = $conn->query("SHOW COLUMNS FROM `exam` LIKE 'visibility'");
    if ($colVisExam && $colVisExam->rowCount() === 0) {
        $conn->exec("ALTER TABLE `exam` ADD COLUMN `visibility` ENUM('PUBLIC', 'SCHOOL', 'GRADE', 'CLASS') NOT NULL DEFAULT 'PUBLIC' AFTER `exam_type`");
    }

    $colSchoolExam = $conn->query("SHOW COLUMNS FROM `exam` LIKE 'school_name'");
    if ($colSchoolExam && $colSchoolExam->rowCount() === 0) {
        $conn->exec("ALTER TABLE `exam` ADD COLUMN `school_name` VARCHAR(255) NULL AFTER `visibility`");
    }

    $colClassesExam = $conn->query("SHOW COLUMNS FROM `exam` LIKE 'target_classes'");
    if ($colClassesExam && $colClassesExam->rowCount() === 0) {
        $conn->exec("ALTER TABLE `exam` ADD COLUMN `target_classes` VARCHAR(255) NULL AFTER `school_name`");
    }

    $colAttemptsExam = $conn->query("SHOW COLUMNS FROM `exam` LIKE 'max_attempts'");
    if ($colAttemptsExam && $colAttemptsExam->rowCount() === 0) {
        $conn->exec("ALTER TABLE `exam` ADD COLUMN `max_attempts` INT NOT NULL DEFAULT 0 COMMENT '0 = Không giới hạn số lượt làm' AFTER `duration`");
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

ob_start();
include $headerPath;
$headerHtml = ob_get_clean();

$styleTag = '<link rel="stylesheet" href="assets/css/index.css?v=1">' . "\n";
if (stripos($headerHtml, '</head>') !== false) {
    $headerHtml = preg_replace_callback('~</head>~i', function () use ($styleTag) {
        return $styleTag . '</head>';
    }, $headerHtml, 1);
} else {
    $headerHtml .= $styleTag;
}

// Lấy thông tin tài khoản hiện tại (class_name, school_name)
$currentUserId   = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = strtoupper((string)($_SESSION['role'] ?? ''));
$currentSchool   = '';
$currentClass    = '';

if ($currentUserId > 0) {
    $stmtMe = $conn->prepare("SELECT role, school_name, class_name FROM users WHERE user_id = :uid LIMIT 1");
    $stmtMe->execute(['uid' => $currentUserId]);
    $meRow = $stmtMe->fetch(PDO::FETCH_ASSOC);

    if ($meRow) {
        $currentUserRole = strtoupper((string)($meRow['role'] ?? $currentUserRole));
        $currentSchool   = trim((string)($meRow['school_name'] ?? ''));
        $currentClass    = trim((string)($meRow['class_name'] ?? ''));
    }
}

/**
 * Điều kiện SQL giới hạn phạm vi đề thi:
 * - PUBLIC: Mọi người đều thấy
 * - SCHOOL: Học sinh cùng trường (e.school_name == users.school_name)
 * - GRADE:  Học sinh thuộc khối e.grade (VD: 7a1 -> khối 7) và trùng trường (nếu HS đã có trường)
 * - CLASS:  Học sinh thuộc lớp trong e.target_classes (VD: 7a1 khớp 7A1) và trùng trường (nếu HS đã có trường)
 */
function buildExamVisibilityClause(
    int $userId,
    string $role,
    string $school,
    string $className,
    string $prefix,
    array &$params
): string {
    if ($role === 'ADMIN') {
        return '1=1';
    }

    if ($userId <= 0) {
        return "COALESCE(e.visibility, 'PUBLIC') = 'PUBLIC'";
    }

    $conditions = [
        "COALESCE(e.visibility, 'PUBLIC') = 'PUBLIC'",
        "e.user_id = :{$prefix}_uid"
    ];
    $params["{$prefix}_uid"] = $userId;

    $schoolNorm = mb_strtolower(trim($school), 'UTF-8');

    // Chuẩn hóa lớp học sinh: "Lớp 7a1" -> "7a1", tách khối -> 7
    $classClean = preg_replace('/^lớp\s*/iu', '', trim($className));
    $classClean = mb_strtolower(preg_replace('/\s+/', '', $classClean), 'UTF-8');

    $studentGrade = 0;
    if (preg_match('/^([6-9])/', $classClean, $m)) {
        $studentGrade = (int)$m[1];
    }

    // 1. Phạm vi THEO TRƯỜNG
    if ($schoolNorm !== '') {
        $conditions[] = "(e.visibility = 'SCHOOL' AND LOWER(TRIM(IFNULL(e.school_name, ''))) = :{$prefix}_school_1)";
        $params["{$prefix}_school_1"] = $schoolNorm;
    }

    // Điều kiện trường bổ trợ cho GRADE / CLASS (nếu HS đã nhập trường thì phải khớp trường đề thi; nếu HS chưa nhập trường thì vẫn cho khớp theo khối/lớp)
    $schoolMatchExpr = ($schoolNorm !== '')
        ? "AND (TRIM(IFNULL(e.school_name, '')) = '' OR LOWER(TRIM(e.school_name)) = :{$prefix}_school_sub)"
        : '';
    if ($schoolNorm !== '') {
        $params["{$prefix}_school_sub"] = $schoolNorm;
    }

    // 2. Phạm vi THEO KHỐI (VD: GV chọn Khối 7 -> HS lớp 7a1, 7a2 đều thấy)
    if ($studentGrade > 0) {
        $conditions[] = "(e.visibility = 'GRADE' AND e.grade = :{$prefix}_st_grade {$schoolMatchExpr})";
        $params["{$prefix}_st_grade"] = $studentGrade;
    }

    // 3. Phạm vi THEO LỚP RA ĐỀ (VD: GV chọn 7A1 -> HS lớp 7a1 thấy)
    if ($classClean !== '') {
        $conditions[] = "(e.visibility = 'CLASS'
                          AND CONCAT(',', LOWER(REPLACE(IFNULL(e.target_classes, ''), ' ', '')), ',') LIKE :{$prefix}_target_class
                          {$schoolMatchExpr})";
        $params["{$prefix}_target_class"] = '%,' . $classClean . ',%';
    }

    return '(' . implode(' OR ', $conditions) . ')';
}

$keyword   = trim($_GET['keyword'] ?? '');
$grade     = trim($_GET['grade'] ?? '');
$subject   = trim($_GET['subject'] ?? '');
$exam_type = trim($_GET['exam_type'] ?? '');

$allowedExamTypes = ['REGULAR', 'MIDTERM_1', 'FINAL_1', 'MIDTERM_2', 'FINAL_2'];
if (!in_array($exam_type, $allowedExamTypes, true)) {
    $exam_type = '';
}

$params = [];
$visibilitySql = buildExamVisibilityClause(
    $currentUserId,
    $currentUserRole,
    $currentSchool,
    $currentClass,
    'vis',
    $params
);

$sql = "SELECT e.exam_id, e.title, e.grade, e.exam_type, e.visibility, e.school_name, e.target_classes,
               e.duration, e.max_attempts, e.total_questions,
               u.full_name AS teacher_name,
               s.subject_name,
               GROUP_CONCAT(DISTINCT t.topic_name SEPARATOR ', ') AS topic_names
        FROM exam e
        LEFT JOIN users u ON e.user_id = u.user_id
        LEFT JOIN subjects s ON e.subject_id = s.subject_id
        LEFT JOIN exam_questions eq ON e.exam_id = eq.exam_id
        LEFT JOIN questions q ON eq.question_id = q.question_id
        LEFT JOIN topics t ON q.topic_id = t.topic_id
        WHERE {$visibilitySql}";

if ($keyword !== '') {
    $sql .= " AND (e.title LIKE :kw_title OR t.topic_name LIKE :kw_topic)";
    $params['kw_title'] = '%' . $keyword . '%';
    $params['kw_topic'] = '%' . $keyword . '%';
}

if ($grade !== '') {
    $sql .= " AND e.grade = :grade";
    $params['grade'] = (int)$grade;
}

if ($subject !== '') {
    $sql .= " AND s.subject_name = :subject";
    $params['subject'] = $subject;
}

if ($exam_type !== '') {
    $sql .= " AND e.exam_type = :exam_type";
    $params['exam_type'] = $exam_type;
}

$sql .= " GROUP BY e.exam_id, e.title, e.grade, e.exam_type, e.visibility, e.school_name, e.target_classes,
                   e.duration, e.max_attempts, e.total_questions, u.full_name, s.subject_name
          ORDER BY e.exam_id DESC";

$stmt_exams = $conn->prepare($sql);
$stmt_exams->execute($params);

$filtered_exams = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);
$total_filtered = count($filtered_exams);
$initial_limit  = 6;

$subject_names = $conn->query("SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name ASC")
                      ->fetchAll(PDO::FETCH_COLUMN);

$cardParams = [];
$cardVisibilitySql = buildExamVisibilityClause(
    $currentUserId,
    $currentUserRole,
    $currentSchool,
    $currentClass,
    'cvis',
    $cardParams
);

$stmtCards = $conn->prepare(
    "SELECT s.subject_name, s.grade, COUNT(e.exam_id) AS exam_count
     FROM subjects s
     LEFT JOIN exam e ON e.subject_id = s.subject_id AND {$cardVisibilitySql}
     GROUP BY s.subject_id, s.subject_name, s.grade
     ORDER BY s.grade ASC, s.subject_name ASC"
);
$stmtCards->execute($cardParams);
$subject_cards = $stmtCards->fetchAll(PDO::FETCH_ASSOC);