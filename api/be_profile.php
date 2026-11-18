<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$message = '';
$is_success = false;
$user_info = [];

// Đảm bảo bảng users có cột school_name, bảng subjects có môn Lịch sử và bảng liên kết teacher_subjects tồn tại
try {
    $colSchoolUser = $conn->query("SHOW COLUMNS FROM `users` LIKE 'school_name'");
    if ($colSchoolUser && $colSchoolUser->rowCount() === 0) {
        $conn->exec("ALTER TABLE `users` ADD COLUMN `school_name` VARCHAR(255) NULL AFTER `email`");
    }

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

function getUserInfo(PDO $conn, int $userId): array
{
    $stmt = $conn->prepare(
        'SELECT user_id, username, password_hash, full_name,
                email, school_name, class_name, dob, role, status
         FROM users
         WHERE user_id = ?
         LIMIT 1'
    );

    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION = [];
        session_destroy();
        header('Location: /login.php?error=account');
        exit;
    }
    return $user;
}

function getTeacherProfile(PDO $conn, int $userId): array
{
    try {
        $stmt = $conn->prepare(
            'SELECT user_id, school_name, teaching_grades, teaching_subjects
             FROM teacher_profiles
             WHERE user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $profile = false;
    }

    if (!$profile) {
        return [
            'school_name'       => '',
            'teaching_grades'   => [],
            'teaching_subjects' => [],
        ];
    }

    $grades = [];
    $rawGrades = trim((string) ($profile['teaching_grades'] ?? ''));
    if ($rawGrades !== '') {
        $decodedGrades = json_decode($rawGrades, true);
        if (is_array($decodedGrades)) {
            $grades = array_values(array_unique(array_map('strval', $decodedGrades)));
        } else {
            preg_match_all('/\b([6-9])\b/', $rawGrades, $matches);
            if (!empty($matches[1])) {
                $grades = array_values(array_unique($matches[1]));
            }
        }
    }

    $subjects = [];
    try {
        $stmtTs = $conn->prepare(
            'SELECT DISTINCT TRIM(s.subject_name) AS subject_name
             FROM teacher_subjects ts
             JOIN subjects s ON ts.subject_id = s.subject_id
             WHERE ts.user_id = ?
             ORDER BY s.subject_name ASC'
        );
        $stmtTs->execute([$userId]);
        $subjects = array_values(array_filter($stmtTs->fetchAll(PDO::FETCH_COLUMN)));
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }

    if (empty($subjects)) {
        $rawSubjects = trim((string) ($profile['teaching_subjects'] ?? ''));
        if ($rawSubjects !== '') {
            $decodedSubjects = json_decode($rawSubjects, true);
            if (is_array($decodedSubjects)) {
                $subjects = array_values(array_filter(array_map('trim', $decodedSubjects)));
            } else {
                $subjects = array_values(array_filter(array_map('trim', explode(',', $rawSubjects))));
            }
        }
    }

    return [
        'school_name'       => trim((string) ($profile['school_name'] ?? '')),
        'teaching_grades'   => $grades,
        'teaching_subjects' => $subjects,
    ];
}

// Lấy danh sách môn dạy kèm khối từ bảng subjects trong CSDL
$subjectOptions = [];
$subjectGradesMap = [];
try {
    $subRows = $conn->query(
        "SELECT DISTINCT TRIM(subject_name) AS subject_name, grade
         FROM subjects
         WHERE subject_name IS NOT NULL AND TRIM(subject_name) <> ''
         ORDER BY subject_name ASC, grade ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subRows as $row) {
        $sName = (string) $row['subject_name'];
        $sGrade = (string) (int) $row['grade'];
        if (!in_array($sName, $subjectOptions, true)) {
            $subjectOptions[] = $sName;
        }
        if ($sGrade !== '0') {
            $subjectGradesMap[$sName][] = $sGrade;
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

// Lấy danh sách trường gợi ý từ teacher_profiles và users
$schoolSuggestions = [];
try {
    $stmtSch = $conn->query(
        "SELECT DISTINCT school_name FROM (
            SELECT TRIM(school_name) AS school_name FROM teacher_profiles WHERE school_name IS NOT NULL AND TRIM(school_name) <> ''
            UNION
            SELECT TRIM(school_name) AS school_name FROM users WHERE school_name IS NOT NULL AND TRIM(school_name) <> ''
         ) AS all_schools
         ORDER BY school_name ASC"
    );
    $schoolSuggestions = $stmtSch->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

try {
    $user_info = getUserInfo($conn, $user_id);
    $isTeacherPost = ($user_info['role'] ?? '') === 'TEACHER';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $dobInput = trim($_POST['dob'] ?? '');
        $password = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($fullName === '') {
            throw new Exception('Họ tên không được để trống.');
        }

        if ($email === '') {
            throw new Exception('Email không được để trống.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Địa chỉ email không hợp lệ.');
        }

        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1');
        $stmt->execute([$email, $user_id]);

        if ($stmt->fetch()) {
            throw new Exception('Email này đã được tài khoản khác sử dụng.');
        }

        $changePassword = $newPassword !== '';

        if ($changePassword) {
            if (!password_verify($password, $user_info['password_hash'])) {
                throw new Exception('Mật khẩu hiện tại không chính xác.');
            }
            if (strlen($newPassword) < 8) {
                throw new Exception('Mật khẩu mới phải có ít nhất 8 ký tự.');
            }
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Mật khẩu xác nhận không khớp.');
            }
        }

        $dob = !empty($dobInput) ? $dobInput : null;

        if ($isTeacherPost) {
            $schoolName = trim($_POST['school_name'] ?? '');
            if ($schoolName === '') {
                throw new Exception('Vui lòng nhập trường công tác.');
            }

            $rawGrades = $_POST['teaching_grades'] ?? [];
            if (!is_array($rawGrades)) {
                $rawGrades = [];
            }
            $validGrades = array_values(array_unique(array_intersect(['6', '7', '8', '9'], array_map('strval', $rawGrades))));
            sort($validGrades);
            $teachingGradesJson = json_encode($validGrades, JSON_UNESCAPED_UNICODE);

            $rawSubjects = $_POST['teaching_subjects'] ?? [];
            if (!is_array($rawSubjects)) {
                $rawSubjects = [];
            }
            $cleanSubjects = array_values(array_unique(array_intersect($subjectOptions, array_map('trim', $rawSubjects))));
            $teachingSubjectsJson = json_encode($cleanSubjects, JSON_UNESCAPED_UNICODE);

            $userSchoolName = $schoolName;
            $className = $user_info['class_name'] ?? null;
        } else {
            $userSchoolName = trim($_POST['school_name'] ?? '');
            $className = trim($_POST['class_name'] ?? '');
        }

        $conn->beginTransaction();

        // 1. Cập nhật bảng users (bao gồm school_name và class_name)
        if ($changePassword) {
            $stmt = $conn->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, school_name = ?, class_name = ?, dob = ?, password_hash = ?
                 WHERE user_id = ?'
            );
            $stmt->execute([
                $fullName,
                $email,
                $userSchoolName !== '' ? $userSchoolName : null,
                $className !== '' ? $className : null,
                $dob,
                password_hash($newPassword, PASSWORD_DEFAULT),
                $user_id
            ]);
        } else {
            $stmt = $conn->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, school_name = ?, class_name = ?, dob = ?
                 WHERE user_id = ?'
            );
            $stmt->execute([
                $fullName,
                $email,
                $userSchoolName !== '' ? $userSchoolName : null,
                $className !== '' ? $className : null,
                $dob,
                $user_id
            ]);
        }

        // 2. Cập nhật teacher_profiles và đồng bộ bảng liên kết teacher_subjects nếu là giáo viên
        if ($isTeacherPost) {
            $checkStmt = $conn->prepare('SELECT user_id FROM teacher_profiles WHERE user_id = ? LIMIT 1');
            $checkStmt->execute([$user_id]);

            if ($checkStmt->fetch()) {
                $stmtProfile = $conn->prepare(
                    'UPDATE teacher_profiles
                     SET school_name = ?, teaching_grades = ?, teaching_subjects = ?
                     WHERE user_id = ?'
                );
                $stmtProfile->execute([$schoolName, $teachingGradesJson, $teachingSubjectsJson, $user_id]);
            } else {
                $stmtProfile = $conn->prepare(
                    'INSERT INTO teacher_profiles (user_id, school_name, teaching_grades, teaching_subjects)
                     VALUES (?, ?, ?, ?)'
                );
                $stmtProfile->execute([$user_id, $schoolName, $teachingGradesJson, $teachingSubjectsJson]);
            }

            $conn->prepare('DELETE FROM teacher_subjects WHERE user_id = ?')->execute([$user_id]);

            if (!empty($cleanSubjects)) {
                $subjPlaceholders = implode(',', array_fill(0, count($cleanSubjects), '?'));
                $sqlFindSubj = "SELECT subject_id FROM subjects WHERE TRIM(subject_name) IN ($subjPlaceholders)";
                $paramsFind = $cleanSubjects;

                if (!empty($validGrades)) {
                    $gradePlaceholders = implode(',', array_fill(0, count($validGrades), '?'));
                    $sqlFindSubj .= " AND (grade IN ($gradePlaceholders) OR grade IS NULL OR grade = 0)";
                    $paramsFind = array_merge($paramsFind, array_map('intval', $validGrades));
                }

                $stmtFindSubj = $conn->prepare($sqlFindSubj);
                $stmtFindSubj->execute($paramsFind);
                $matchedSubjectIds = array_map('intval', $stmtFindSubj->fetchAll(PDO::FETCH_COLUMN));

                if (!empty($matchedSubjectIds)) {
                    $stmtInsTs = $conn->prepare('INSERT IGNORE INTO teacher_subjects (user_id, subject_id) VALUES (?, ?)');
                    foreach ($matchedSubjectIds as $sid) {
                        $stmtInsTs->execute([$user_id, $sid]);
                    }
                }
            }
        }

        $conn->commit();

        $_SESSION['full_name'] = $fullName;
        $_SESSION['username'] = $user_info['username'];
        $_SESSION['email'] = $email;

        $user_info = getUserInfo($conn, $user_id);
        $message = 'Cập nhật thông tin cá nhân thành công.';
        $is_success = true;
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log($e->getMessage());
    $message = 'Lỗi CSDL: ' . $e->getMessage();
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $message = $e->getMessage();
}

$role = $user_info['role'] ?? ($_SESSION['role'] ?? 'STUDENT');
$is_teacher = $role === 'TEACHER';

$role_labels = [
    'STUDENT' => 'Học viên',
    'TEACHER' => 'Giáo viên',
    'ADMIN'   => 'Quản trị viên'
];
$role_label = $role_labels[$role] ?? $role;

$display_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? $role_label;
$initials = mb_strtoupper(mb_substr(trim($display_name) !== '' ? trim($display_name) : '?', 0, 1, 'UTF-8'), 'UTF-8');

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$teacher_info = $is_teacher
    ? getTeacherProfile($conn, $user_id)
    : ['school_name' => '', 'teaching_grades' => [], 'teaching_subjects' => []];