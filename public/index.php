<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container">

   <section class="hero">
    <div class="container">

        <div class="slider">

            <!-- Slide 1 -->
            <div class="slide active">
                <img src="image/anh1.jpg" alt="Lịch sử Việt Nam">

                <div class="slide-content">
                    <h2>LỊCH SỬ VIỆT NAM</h2>
                    <p>
                        Khám phá những sự kiện lịch sử quan trọng
                        qua các bài trắc nghiệm.
                    </p>

                    <a href="#" class="slider-btn">
                        Bắt đầu thi
                    </a>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="slide">
                <img src="image/anh2.jpg" alt="Lịch sử Việt Nam">

                <div class="slide-content">
                    <h2>KHÁM PHÁ LỊCH SỬ</h2>
                    <p>
                        Ôn tập kiến thức Lịch sử THCS
                        một cách dễ dàng và thú vị.
                    </p>

                    <a href="#" class="slider-btn">
                        Xem bài thi
                    </a>
                </div>
            </div>

            <!-- Slide 3 -->
            <div class="slide">
                <img src="image/anh3.jpg" alt="Lịch sử Việt Nam">

                <div class="slide-content">
                    <h2>ÔN TẬP KIẾN THỨC</h2>
                    <p>
                        Củng cố kiến thức Lịch sử lớp 6, 7, 8 và 9.
                    </p>

                    <a href="#" class="slider-btn">
                        Bắt đầu học
                    </a>
                </div>
            </div>

            <!-- Nút chuyển -->
            <button class="slider-prev" onclick="changeSlide(-1)">
                &#10094;
            </button>

            <button class="slider-next" onclick="changeSlide(1)">
                &#10095;
            </button>

            <!-- Dấu chấm -->
            <div class="slider-dots">
                <span class="dot active" onclick="showSlide(0)"></span>
                <span class="dot" onclick="showSlide(1)"></span>
                <span class="dot" onclick="showSlide(2)"></span>
            </div>

        </div>

    </div>
</section>

    <section class="classes">
        <h2>Chọn khối lớp</h2>

        <div class="class-list">

            <a href="pages/de-thi.php?lop=6" class="class-card">
                <h3>Lịch sử 6</h3>
                <p>Kiến thức Lịch sử lớp 6</p>
            </a>

            <a href="pages/de-thi.php?lop=7" class="class-card">
                <h3>Lịch sử 7</h3>
                <p>Kiến thức Lịch sử lớp 7</p>
            </a>

            <a href="pages/de-thi.php?lop=8" class="class-card">
                <h3>Lịch sử 8</h3>
                <p>Kiến thức Lịch sử lớp 8</p>
            </a>

            <a href="pages/de-thi.php?lop=9" class="class-card">
                <h3>Lịch sử 9</h3>
                <p>Kiến thức Lịch sử lớp 9</p>
            </a>

        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>




<!-- chuyển ảnh  -->

<script>

let currentSlide = 0;

const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");


function showSlide(index) {

    if (index >= slides.length) {
        currentSlide = 0;
    }

    else if (index < 0) {
        currentSlide = slides.length - 1;
    }

    else {
        currentSlide = index;
    }


    slides.forEach(slide => {
        slide.classList.remove("active");
    });

    dots.forEach(dot => {
        dot.classList.remove("active");
    });


    slides[currentSlide].classList.add("active");

    dots[currentSlide].classList.add("active");
}


function changeSlide(direction) {

    showSlide(currentSlide + direction);

}


/* Tự động chuyển ảnh */

setInterval(() => {

    changeSlide(1);

}, 5000);

</script>