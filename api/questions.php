<?php
session_start();

require_once __DIR__ . '/../../config/database.php';

/*
|--------------------------------------------------------------------------
| KIỂM TRA ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CHỈ GIÁO VIÊN MỚI ĐƯỢC QUẢN LÝ CÂU HỎI
|--------------------------------------------------------------------------
*/

if ($_SESSION["role"] !== "TEACHER") {
    die("Bạn không có quyền truy cập trang này.");
}

$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| XÓA CÂU HỎI
|--------------------------------------------------------------------------
*/

if (isset($_GET["delete"])) {

    $question_id = (int) $_GET["delete"];

    try {

        /*
         * Do bảng answers có ON DELETE CASCADE
         * nên khi xóa question thì 4 answers cũng bị xóa.
         */

        $stmt = $conn->prepare(
            "DELETE FROM questions
             WHERE question_id = ?"
        );

        $stmt->execute([$question_id]);

        $message = "Xóa câu hỏi thành công.";

    } catch (PDOException $e) {

        $error = "Không thể xóa câu hỏi.";
    }
}


/*
|--------------------------------------------------------------------------
| THÊM CÂU HỎI
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["action"])
    && $_POST["action"] === "add") {

    $topic_id = (int) ($_POST["topic_id"] ?? 0);

    $content = trim($_POST["content"] ?? "");

    $difficulty = $_POST["difficulty"] ?? "";

    $answer_a = trim($_POST["answer_a"] ?? "");
    $answer_b = trim($_POST["answer_b"] ?? "");
    $answer_c = trim($_POST["answer_c"] ?? "");
    $answer_d = trim($_POST["answer_d"] ?? "");

    $correct_answer = strtoupper(
        trim($_POST["correct_answer"] ?? "")
    );

    $explanation = trim($_POST["explanation"] ?? "");

    /*
     * Kiểm tra dữ liệu
     */

    if (
        $topic_id <= 0 ||
        $content === "" ||
        $answer_a === "" ||
        $answer_b === "" ||
        $answer_c === "" ||
        $answer_d === "" ||
        !in_array(
            $difficulty,
            ["EASY", "MEDIUM", "HARD"]
        ) ||
        !in_array(
            $correct_answer,
            ["A", "B", "C", "D"]
        )
    ) {

        $error = "Vui lòng nhập đầy đủ và chính xác thông tin.";

    } else {

        try {

            /*
             * Bắt đầu transaction.
             *
             * Nếu một bước lỗi thì toàn bộ dữ liệu
             * sẽ được rollback.
             */

            $conn->beginTransaction();


            /*
             * Thêm câu hỏi
             */

            $stmt = $conn->prepare(
                "INSERT INTO questions
                (
                    topic_id,
                    content,
                    difficulty,
                    explanation
                )
                VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $topic_id,
                $content,
                $difficulty,
                $explanation
            ]);


            /*
             * Lấy ID câu hỏi vừa tạo
             */

            $question_id = $conn->lastInsertId();


            /*
             * Danh sách 4 đáp án
             */

            $answers = [
                "A" => $answer_a,
                "B" => $answer_b,
                "C" => $answer_c,
                "D" => $answer_d
            ];


            /*
             * Thêm 4 đáp án
             */

            $stmtAnswer = $conn->prepare(
                "INSERT INTO answers
                (
                    question_id,
                    option_code,
                    content,
                    is_correct,
                    is_fixed
                )
                VALUES (?, ?, ?, ?, ?)"
            );


            foreach ($answers as $code => $answer) {

                /*
                 * Nếu là đáp án đúng:
                 * is_correct = 1
                 */

                $is_correct =
                    ($code === $correct_answer) ? 1 : 0;


                /*
                 * Mặc định không cố định.
                 *
                 * Giáo viên có thể sửa thành 1
                 * cho các đáp án đặc biệt.
                 */

                $is_fixed = 0;


                $stmtAnswer->execute([
                    $question_id,
                    $code,
                    $answer,
                    $is_correct,
                    $is_fixed
                ]);
            }


            /*
             * Hoàn tất transaction
             */

            $conn->commit();

            $message = "Thêm câu hỏi thành công.";

        } catch (PDOException $e) {

            /*
             * Nếu có lỗi thì hoàn tác
             */

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $error = "Lỗi khi thêm câu hỏi: "
                   . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH CHỦ ĐỀ
|--------------------------------------------------------------------------
*/

$topics = $conn
    ->query(
        "SELECT *
         FROM topics
         ORDER BY grade, topic_name"
    )
    ->fetchAll();


/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH CÂU HỎI
|--------------------------------------------------------------------------
*/

$questions = $conn
    ->query(
        "SELECT
            q.question_id,
            q.content,
            q.difficulty,
            q.explanation,
            t.topic_name,
            t.grade
         FROM questions q
         INNER JOIN topics t
             ON q.topic_id = t.topic_id
         ORDER BY q.question_id DESC"
    )
    ->fetchAll();

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Ngân hàng câu hỏi</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        h1 {
            margin-bottom: 20px;
        }

        .box {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 10px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 12px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 100px;
        }

        button {
            margin-top: 20px;
            padding: 10px 20px;
            border: none;
            background: #2563eb;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background: #1d4ed8;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            margin-bottom: 15px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f1f5f9;
        }

        .delete {
            color: red;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Ngân hàng câu hỏi</h1>


    <?php if ($message !== ""): ?>

        <div class="success">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <!-- ======================================================
         FORM THÊM CÂU HỎI
         ====================================================== -->

    <div class="box">

        <h2>Thêm câu hỏi</h2>

        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="add"
            >


            <label>Chủ đề</label>

            <select name="topic_id" required>

                <option value="">
                    -- Chọn chủ đề --
                </option>

                <?php foreach ($topics as $topic): ?>

                    <option value="<?= $topic["topic_id"] ?>">

                        Lớp <?= $topic["grade"] ?>
                        -
                        <?= htmlspecialchars(
                            $topic["topic_name"]
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <label>Mức độ khó</label>

            <select name="difficulty" required>

                <option value="">
                    -- Chọn mức độ --
                </option>

                <option value="EASY">
                    Dễ
                </option>

                <option value="MEDIUM">
                    Trung bình
                </option>

                <option value="HARD">
                    Khó
                </option>

            </select>


            <label>Nội dung câu hỏi</label>

            <textarea
                name="content"
                required
            ></textarea>


            <label>Đáp án A</label>

            <input
                type="text"
                name="answer_a"
                required
            >


            <label>Đáp án B</label>

            <input
                type="text"
                name="answer_b"
                required
            >


            <label>Đáp án C</label>

            <input
                type="text"
                name="answer_c"
                required
            >


            <label>Đáp án D</label>

            <input
                type="text"
                name="answer_d"
                required
            >


            <label>Đáp án đúng</label>

            <select name="correct_answer" required>

                <option value="">
                    -- Chọn đáp án đúng --
                </option>

                <option value="A">A</option>

                <option value="B">B</option>

                <option value="C">C</option>

                <option value="D">D</option>

            </select>


            <label>Lời giải</label>

            <textarea
                name="explanation"
                placeholder="Nhập lời giải nếu có..."
            ></textarea>


            <button type="submit">
                Lưu câu hỏi
            </button>

        </form>

    </div>


    <!-- ======================================================
         DANH SÁCH CÂU HỎI
         ====================================================== -->

    <div class="box">

        <h2>Danh sách câu hỏi</h2>

        <table>

            <tr>

                <th>ID</th>

                <th>Câu hỏi</th>

                <th>Chủ đề</th>

                <th>Lớp</th>

                <th>Độ khó</th>

                <th>Thao tác</th>

            </tr>


            <?php foreach ($questions as $question): ?>

                <tr>

                    <td>
                        <?= $question["question_id"] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $question["content"]
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $question["topic_name"]
                        ) ?>
                    </td>

                    <td>
                        <?= $question["grade"] ?>
                    </td>

                    <td>

                        <?php

                        if ($question["difficulty"] === "EASY") {
                            echo "Dễ";
                        } elseif (
                            $question["difficulty"] === "MEDIUM"
                        ) {
                            echo "Trung bình";
                        } else {
                            echo "Khó";
                        }

                        ?>69
                        

                    </td>

                    <td>

                        <a
                            href="edit-question.php?id=<?= $question["question_id"] ?>"
                        >
                            Sửa
                        </a>

                        |

                        <a
                            class="delete"
                            href="?delete=<?= $question["question_id"] ?>"
                            onclick="return confirm('Bạn có chắc muốn xóa câu hỏi này?')"
                        >
                            Xóa
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        </table>

    </div>

</div>

</body>

</html>
