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
$teacher_info = [
    'school_name' => '',
    'teaching_grades' => [],
    'teaching_subjects' => []
];

function getUserInfo(PDO $conn, int $userId): array
{
    $stmt = $conn->prepare(
        'SELECT user_id, username, password_hash, full_name,
                email, class_name, dob, role, status
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

function getTeacherInfo(PDO $conn, int $userId): array
{
    $stmt = $conn->prepare(
        'SELECT school_name, teaching_grades, teaching_subjects
         FROM teacher_profiles
         WHERE user_id = ?
         LIMIT 1'
    );

    $stmt->execute([$userId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        return [
            'school_name' => '',
            'teaching_grades' => [],
            'teaching_subjects' => []
        ];
    }

    return [
        'school_name' => $data['school_name'] ?? '',
        'teaching_grades' => json_decode($data['teaching_grades'] ?? '[]', true) ?: [],
        'teaching_subjects' => json_decode($data['teaching_subjects'] ?? '[]', true) ?: []
    ];
}

try {
    $user_info = getUserInfo($conn, $user_id);
    $isTeacherAccount = strtoupper($user_info['role'] ?? '') === 'TEACHER';

    if ($isTeacherAccount) {
        $teacher_info = getTeacherInfo($conn, $user_id);
    }

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['action'] ?? '') === 'update_profile'
    ) {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Giáo viên không dùng trường class_name trong hồ sơ.
        $className = $isTeacherAccount
            ? ($user_info['class_name'] ?? '')
            : trim($_POST['class_name'] ?? '');

        $dobInput = trim($_POST['dob'] ?? '');
        $password = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($fullName === '' || $email === '') {
            throw new Exception('Họ tên và email không được để trống.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Địa chỉ email không hợp lệ.');
        }

        $stmt = $conn->prepare(
            'SELECT user_id FROM users
             WHERE email = ? AND user_id <> ?
             LIMIT 1'
        );
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

        $dob = $dobInput !== '' ? $dobInput : null;

        if ($changePassword) {
            $stmt = $conn->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, class_name = ?,
                     dob = ?, password_hash = ?
                 WHERE user_id = ?'
            );

            $stmt->execute([
                $fullName,
                $email,
                $className,
                $dob,
                password_hash($newPassword, PASSWORD_DEFAULT),
                $user_id
            ]);
        } else {
            $stmt = $conn->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, class_name = ?, dob = ?
                 WHERE user_id = ?'
            );

            $stmt->execute([
                $fullName,
                $email,
                $className,
                $dob,
                $user_id
            ]);
        }

        // Lưu thông tin giảng dạy riêng của giáo viên.
        if ($isTeacherAccount) {
            $schoolName = trim($_POST['school_name'] ?? '');

            $allowedGrades = ['6', '7', '8', '9'];
            $allowedSubjects = [
    'Ngữ văn',
    'Toán',
    'Ngoại ngữ 1',
    'Giáo dục công dân',
    'Lịch sử và Địa lí',
    'Khoa học tự nhiên',
    'Công nghệ',
    'Tin học',
    'Giáo dục thể chất',
    'Âm nhạc',
    'Mĩ thuật',
    'Hoạt động trải nghiệm, hướng nghiệp',
    'Nội dung giáo dục của địa phương',
    'Tiếng dân tộc thiểu số',
    'Ngoại ngữ 2'
];

            $grades = $_POST['teaching_grades'] ?? [];
            $subjects = $_POST['teaching_subjects'] ?? [];

            if (!is_array($grades)) {
                $grades = [];
            }

            if (!is_array($subjects)) {
                $subjects = [];
            }

            $grades = array_values(array_intersect($grades, $allowedGrades));
            $subjects = array_values(array_intersect($subjects, $allowedSubjects));

            if ($schoolName === '') {
                throw new Exception('Vui lòng nhập tên trường công tác.');
            }

            if (empty($grades)) {
                throw new Exception('Vui lòng chọn ít nhất một khối giảng dạy.');
            }

            if (empty($subjects)) {
                throw new Exception('Vui lòng chọn ít nhất một môn giảng dạy.');
            }

            $stmt = $conn->prepare(
                'INSERT INTO teacher_profiles
                    (user_id, school_name, teaching_grades, teaching_subjects)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    school_name = VALUES(school_name),
                    teaching_grades = VALUES(teaching_grades),
                    teaching_subjects = VALUES(teaching_subjects)'
            );

            $stmt->execute([
                $user_id,
                $schoolName,
                json_encode($grades, JSON_UNESCAPED_UNICODE),
                json_encode($subjects, JSON_UNESCAPED_UNICODE)
            ]);
        }

        $_SESSION['full_name'] = $fullName;
        $_SESSION['username'] = $user_info['username'];
        $_SESSION['email'] = $email;

        $user_info = getUserInfo($conn, $user_id);

        if ($isTeacherAccount) {
            $teacher_info = getTeacherInfo($conn, $user_id);
        }

        $message = 'Cập nhật thông tin cá nhân thành công.';
        $is_success = true;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $message = 'Không thể cập nhật thông tin. Vui lòng kiểm tra cấu trúc CSDL.';
} catch (Exception $e) {
    $message = $e->getMessage();
}

/* Chuẩn bị dữ liệu hiển thị */
$role = $user_info['role'] ?? ($_SESSION['role'] ?? 'STUDENT');
$is_teacher = strtoupper($role) === 'TEACHER';

$role_labels = [
    'STUDENT' => 'Học viên',
    'TEACHER' => 'Giáo viên',
    'ADMIN' => 'Quản trị viên'
];

$role_label = $role_labels[$role] ?? $role;

$display_name = $_SESSION['full_name']
    ?? $_SESSION['username']
    ?? $role_label;

$initials = mb_strtoupper(
    mb_substr(
        trim($display_name) !== '' ? trim($display_name) : '?',
        0,
        1,
        'UTF-8'
    ),
    'UTF-8'
);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$class_name_value = trim((string) ($user_info['class_name'] ?? ''));
$dob_value = $user_info['dob'] ?? '';
