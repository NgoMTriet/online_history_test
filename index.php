<?php include __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../config/database.php'; ?>

<?php
$isTeacher = (
    isset($_SESSION['user_id'], $_SESSION['role'])
    && strtoupper($_SESSION['role']) === 'TEACHER'
);
?>


<main class="container">

    <section class="hero">

        <div class="container">

            <div class="slider">

                <!-- Slide 1 -->
                <div class="slide active">

                    <img
                        src="image/anh1.jpg"
                        alt="Lịch sử Việt Nam"
                    >
                    <div class="slide-content">

                        <h2>
                            LỊCH SỬ VIỆT NAM
                        </h2>

                        <p style="text-indent: 20px;">
                            Khám phá những sự kiện lịch sử quan trọng qua các bài trắc nghiệm.
                        </p>
                    </div>

                </div>


                <!-- Slide 2 -->
                <div class="slide">

                    <img
                        src="image/anh2.jpg"
                        alt="Lịch sử Việt Nam"
                    >

                    <div class="slide-content">

                        <h2>
                            KHÁM PHÁ LỊCH SỬ
                        </h2>

                        <p style="text-indent: 20px;">
                            Ôn tập kiến thức Lịch sử THCS một cách dễ dàng và thú vị.
                        </p>
                    </div>

                </div>


                <!-- Slide 3 -->
                <div class="slide">

                    <img
                        src="image/anh3.jpg"
                        alt="Lịch sử Việt Nam"
                    >

                    <div class="slide-content">

                        <h2>
                            ÔN TẬP KIẾN THỨC
                        </h2>

                        <p style="text-indent: 20px;">
                            Củng cố kiến thức Lịch sử lớp 6, 7, 8 và 9.
                        </p>
                    </div>

                </div>


                <!-- Nút chuyển -->
                <button
                    class="slider-prev"
                    onclick="changeSlide(-1)"
                >
                    &#10094;
                </button>

                <button
                    class="slider-next"
                    onclick="changeSlide(1)"
                >
                    &#10095;
                </button>


                <!-- Dấu chấm -->
                <div class="slider-dots">

                    <span
                        class="dot active"
                        onclick="showSlide(0)"
                    ></span>

                    <span
                        class="dot"
                        onclick="showSlide(1)"
                    ></span>

                    <span
                        class="dot"
                        onclick="showSlide(2)"
                    ></span>

                </div>

            </div>

        </div>

    </section>


    <!-- ================================================= -->
    <!-- TÌM KIẾM & LỌC ĐỀ THI -->
    <!-- ================================================= -->

    <section class="exam-search">

        <h2>
            Tìm kiếm đề thi
        </h2>

        <form
            method="GET"
            action="index.php"
            class="exam-search-form"
        >

            <div class="exam-search-input">

                <input
                    type="text"
                    name="keyword"
                    placeholder="Nhập tên đề thi hoặc chủ đề..."
                    value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>"
                >

                <button type="submit">
                    Tìm kiếm
                </button>

            </div>


            <div class="exam-grade-filter">

                <label for="grade">
                    Khối lớp:
                </label>

                <select
                    name="grade"
                    id="grade"
                    onchange="this.form.submit()"
                >

                    <option value="">
                        Tất cả
                    </option>

                    <option
                        value="6"
                        <?= ($_GET['grade'] ?? '') == '6' ? 'selected' : '' ?>
                    >
                        Lớp 6
                    </option>

                    <option
                        value="7"
                        <?= ($_GET['grade'] ?? '') == '7' ? 'selected' : '' ?>
                    >
                        Lớp 7
                    </option>

                    <option
                        value="8"
                        <?= ($_GET['grade'] ?? '') == '8' ? 'selected' : '' ?>
                    >
                        Lớp 8
                    </option>

                    <option
                        value="9"
                        <?= ($_GET['grade'] ?? '') == '9' ? 'selected' : '' ?>
                    >
                        Lớp 9
                    </option>

                </select>

            </div>

        </form>

    </section>


    <!-- ================================================= -->
    <!-- KẾT QUẢ TÌM KIẾM / DANH SÁCH ĐỀ THI (NỐI CSDL) -->
    <!-- ================================================= -->

    <?php
    // Đọc bộ lọc tìm kiếm
    $keyword = trim($_GET['keyword'] ?? '');
    $grade = trim($_GET['grade'] ?? '');

    // Truy vấn dữ liệu thực tế từ CSDL MySQL bảng `exam`
    $sql = "SELECT exam_id, title, grade, duration, total_questions FROM exam WHERE 1=1";
    $params = [];

    if (!empty($keyword)) {$sql .= " AND title LIKE :keyword";
        $params['keyword'] = '%' .$keyword . '%';
    }

    if (!empty($grade)) {$sql .= " AND grade = :grade";
        $params['grade'] = (int)$grade;
    }

    $sql .= " ORDER BY exam_id DESC";

    $stmt_exams = $conn->prepare($sql);
    $stmt_exams->execute($params);
    $filtered_exams =$stmt_exams->fetchAll();
    ?>

    <section class="exam-results">

        <h2>
            Đề thi trắc nghiệm
        </h2>

        <div id="examList" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">

            <?php if (empty($filtered_exams)): ?>
                <p style="grid-column: 1 / -1; color: #7f8c8d;">Không tìm thấy đề thi phù hợp với yêu cầu tìm kiếm.</p>
            <?php else: ?>
                <?php foreach ($filtered_exams as$exam): ?>
                    <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <span style="background: #e1f5fe; color: #0288d1; font-size: 0.8rem; font-weight: bold; padding: 3px 8px; border-radius: 4px;">Lớp <?= $exam['grade'] ?></span>
                            <h3 style="font-size: 1.1rem; margin: 10px 0; color: #2c3e50;"><?= htmlspecialchars($exam['title']) ?></h3>
                            <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">⏱️ Thời gian: <?= $exam['duration'] ?> phút 📝 <?= $exam['total_questions'] ?> câu</p>
                        </div>
                        <a href="take_exam.php?exam_id=<?= $exam['exam_id'] ?>" class="slider-btn" style="text-align: center; display: block; text-decoration: none;">Vào làm bài</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </section>


    <!-- ================================================= -->
    <!-- CHỌN KHỐI LỚP - CODE CŨ GIỮ NGUYÊN -->
    <!-- ================================================= -->

    <section class="classes">

        <h2>
            Chọn khối lớp
        </h2>

        <div class="class-list">

            <a
                href="index.php?grade=6"
                class="class-card"
            >
                <h3>
                    Lịch sử 6
                </h3>

                <p>
                    Kiến thức Lịch sử lớp 6
                </p>
            </a>


            <a
                href="index.php?grade=7"
                class="class-card"
            >
                <h3>
                    Lịch sử 7
                </h3>

                <p>
                    Kiến thức Lịch sử lớp 7
                </p>
            </a>


            <a
                href="index.php?grade=8"
                class="class-card"
            >
                <h3>
                    Lịch sử 8
                </h3>

                <p>
                    Kiến thức Lịch sử lớp 8
                </p>
            </a>


            <a
                href="index.php?grade=9"
                class="class-card"
            >
                <h3>
                    Lịch sử 9
                </h3>

                <p>
                    Kiến thức Lịch sử lớp 9
                </p>
            </a>

        </div>

    </section>

</main>

<!-- ĐẶT CODE JAVASCRIPT SLIDER VÀO ĐÂY -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    let currentSlide = 0;
    const slides = document.querySelectorAll(".slide");
    const dots = document.querySelectorAll(".dot");

    if (slides.length === 0) return; // Nếu không có slide thì dừng

    function showSlide(index) {
        if (index >= slides.length) {
            currentSlide = 0;
        } else if (index < 0) {
            currentSlide = slides.length - 1;
        } else {
            currentSlide = index;
        }

        slides.forEach(slide => slide.classList.remove("active"));
        dots.forEach(dot => dot.classList.remove("active"));

        if (slides[currentSlide]) slides[currentSlide].classList.add("active");
        if (dots[currentSlide]) dots[currentSlide].classList.add("active");
    }

    // Đưa hàm ra window để các thuộc tính onclick="..." trong HTML gọi được
    window.changeSlide = function(direction) {
        showSlide(currentSlide + direction);
    };

    window.showSlide = function(index) {
        showSlide(index);
    };

    // Tự động chuyển ảnh mỗi 5s
    setInterval(() => {
        window.changeSlide(1);
    }, 5000);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>