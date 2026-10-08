
<?php include __DIR__ . '/../includes/header.php'; ?>
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

                        <a href="#" class="slider-btn">
                            Bắt đầu thi
                        </a>

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

                        <a href="#" class="slider-btn">
                            Xem bài thi
                        </a>

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

                        <a href="#" class="slider-btn">
                            Bắt đầu học
                        </a>

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
    <!-- TÌM KIẾM & LỌC ĐỀ THI - PHẦN MỚI -->
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
                >

                    <option value="">
                        Tất cả
                    </option>

                    <option
                        value="6"
                        <?= ($_GET['grade'] ?? '') == '6'
                            ? 'selected'
                            : '' ?>
                    >
                        Lớp 6
                    </option>

                    <option
                        value="7"
                        <?= ($_GET['grade'] ?? '') == '7'
                            ? 'selected'
                            : '' ?>
                    >
                        Lớp 7
                    </option>

                    <option
                        value="8"
                        <?= ($_GET['grade'] ?? '') == '8'
                            ? 'selected'
                            : '' ?>
                    >
                        Lớp 8
                    </option>

                    <option
                        value="9"
                        <?= ($_GET['grade'] ?? '') == '9'
                            ? 'selected'
                            : '' ?>
                    >
                        Lớp 9
                    </option>

                </select>

            </div>

        </form>

    </section>


    <!-- ================================================= -->
    <!-- KẾT QUẢ TÌM KIẾM -->
    <!-- ================================================= -->

    <?php

    /*
     * Sau này phần này sẽ lấy dữ liệu
     * trực tiếp từ API / Database.
     *
     * Hiện tại giữ giao diện trước.
     */

    ?>

    <section class="exam-results">

        <h2>
            Đề thi
        </h2>

        <div id="examList">

            <p>
                Hãy nhập từ khóa hoặc chọn khối lớp để tìm đề thi.
            </p>

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
                href="pages/de-thi.php?lop=6"
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
                href="pages/de-thi.php?lop=7"
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
                href="pages/de-thi.php?lop=8"
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
                href="pages/de-thi.php?lop=9"
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

<?php include __DIR__ . '/../includes/footer.php'; ?>

