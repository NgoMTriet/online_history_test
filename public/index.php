<?php
require_once __DIR__ . '/../api/be_index.php';
echo $headerHtml;
?>

<main class="container">

    <!-- BANNER GIỚI THIỆU -->
    <section class="hero">
        <div class="container">
            <div class="slider">

                <div class="slide active">
                    <img src="image/anh1.png" alt="Lịch sử Việt Nam" onerror="this.src='../image/anh1.png'">
                    <div class="slide-content">
                        <span class="subtitle">Khám phá quá khứ</span>
                        <h2>LỊCH SỬ VIỆT NAM</h2>
                        <div class="hero-buttons">
                            <a href="#danh-muc" class="btn-hero-primary">▶ Bắt đầu thi</a>
                            <a href="#huong-dan" class="btn-hero-outline">Khám phá đề thi</a>
                        </div>
                    </div>
                    <div class="quote-box">
                        <p>“Dân ta phải biết sử ta<br>Cho tường gốc tích nước nhà Việt Nam”</p>
                        <span>— Hồ Chí Minh —</span>
                    </div>
                </div>

                <div class="slide">
                    <img src="image/anh2.png" alt="Di tích lịch sử Việt Nam" onerror="this.src='../image/anh2.png'">
                    <div class="slide-content">
                        <span class="subtitle">Mỗi bài học là một hành trình</span>
                        <h2>KHÁM PHÁ LỊCH SỬ</h2>
                        <div class="hero-buttons">
                            <a href="#danh-muc" class="btn-hero-primary">▶ Bắt đầu thi</a>
                            <a href="#huong-dan" class="btn-hero-outline">Khám phá đề thi</a>
                        </div>
                    </div>
                    <div class="quote-box">
                        <p>“Nước Nam ta là một nước văn hiến<br>Đã có lịch sử lâu đời và truyền thống hào hùng”</p>
                        <span>— Nguyễn Trãi —</span>
                    </div>
                </div>

                <div class="slide">
                    <img src="image/anh3.png" alt="Tư liệu lịch sử Việt Nam" onerror="this.src='../image/anh3.png'">
                    <div class="slide-content">
                        <span class="subtitle">Ôn luyện và bứt phá</span>
                        <h2>CHINH PHỤC KIẾN THỨC</h2>
                        <div class="hero-buttons">
                            <a href="#danh-muc" class="btn-hero-primary">▶ Bắt đầu thi</a>
                            <a href="#huong-dan" class="btn-hero-outline">Khám phá đề thi</a>
                        </div>
                    </div>
                    <div class="quote-box">
                        <p>“Đoàn kết, đoàn kết, đại đoàn kết<br>Thành công, thành công, đại thành công”</p>
                        <span>— Hồ Chí Minh —</span>
                    </div>
                </div>

                <button class="slider-prev" type="button" onclick="changeSlide(-1)" aria-label="Ảnh trước">&#10094;</button>
                <button class="slider-next" type="button" onclick="changeSlide(1)" aria-label="Ảnh tiếp theo">&#10095;</button>

                <div class="slider-dots">
                    <span class="dot active" onclick="showSlide(0)" aria-label="Slide 1"></span>
                    <span class="dot" onclick="showSlide(1)" aria-label="Slide 2"></span>
                    <span class="dot" onclick="showSlide(2)" aria-label="Slide 3"></span>
                </div>
            </div>
        </div>
    </section>

    <!-- TÌM KIẾM VÀ LỌC ĐỀ THI -->
    <section class="exam-search" aria-label="Tìm kiếm đề thi">
        <form class="exam-search-form" action="index.php" method="GET">
            <div class="exam-search-input">
                <span class="search-icon" aria-hidden="true">⌕</span>
                <input
                    type="text"
                    name="keyword"
                    value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Tìm kiếm đề thi theo tên, chủ đề..."
                    aria-label="Tìm kiếm đề thi"
                >
            </div>

            <div class="exam-grade-filter">
                <span class="filter-icon" aria-hidden="true">▣</span>
                <select name="grade" id="grade" aria-label="Chọn khối lớp">
                    <option value="">Tất cả khối lớp</option>
                    <option value="6" <?= $grade === '6' ? 'selected' : '' ?>>Lớp 6</option>
                    <option value="7" <?= $grade === '7' ? 'selected' : '' ?>>Lớp 7</option>
                    <option value="8" <?= $grade === '8' ? 'selected' : '' ?>>Lớp 8</option>
                    <option value="9" <?= $grade === '9' ? 'selected' : '' ?>>Lớp 9</option>
                </select>
            </div>

            <div class="exam-subject-label">📚 Môn học: <strong>Lịch sử</strong></div>
            <button type="submit" class="btn-search-submit"><span>⌕</span> Tìm kiếm</button>
        </form>
    </section>

    <!-- DANH MỤC LỚP 6-9 -->
    <section class="classes" id="danh-muc">
        <div class="section-header">
            <div>
                <h2><span class="section-icon">▣</span> Danh mục đề thi</h2>
                <p>Chọn khối lớp để bắt đầu luyện tập</p>
            </div>
            <a href="#de-thi" class="section-more">Xem đề thi <span>→</span></a>
        </div>

        <div class="class-list">
            <a href="index.php?grade=6" class="class-card grade-6">
                <div class="class-card-cover" style="background-image: linear-gradient(90deg, rgba(224,242,254,.30), rgba(224,242,254,.12)), url('image/anh1.jpg')">
                    <span class="class-badge">6</span>
                    <span class="class-cover-title">LỊCH SỬ</span>
                </div>
                <div class="class-card-content">
                    <h3>Lịch sử 6</h3>
                    <p>Từ thời nguyên thủy đến thời kỳ phong kiến Việt Nam.</p>
                    <span class="btn-link">Luyện thi ngay <span>→</span></span>
                </div>
            </a>

            <a href="index.php?grade=7" class="class-card grade-7">
                <div class="class-card-cover" style="background-image: linear-gradient(90deg, rgba(255,237,213,.30), rgba(255,237,213,.12)), url('image/anh2.jpg')">
                    <span class="class-badge">7</span>
                    <span class="class-cover-title">LỊCH SỬ</span>
                </div>
                <div class="class-card-content">
                    <h3>Lịch sử 7</h3>
                    <p>Từ thời Trần đến thời Lê sơ và các giai đoạn tiếp theo.</p>
                    <span class="btn-link">Luyện thi ngay <span>→</span></span>
                </div>
            </a>

            <a href="index.php?grade=8" class="class-card grade-8">
                <div class="class-card-cover" style="background-image: linear-gradient(90deg, rgba(220,252,231,.30), rgba(220,252,231,.12)), url('image/anh3.jpg')">
                    <span class="class-badge">8</span>
                    <span class="class-cover-title">LỊCH SỬ</span>
                </div>
                <div class="class-card-content">
                    <h3>Lịch sử 8</h3>
                    <p>Tìm hiểu các cuộc đấu tranh và những chuyển biến lịch sử.</p>
                    <span class="btn-link">Luyện thi ngay <span>→</span></span>
                </div>
            </a>

            <a href="index.php?grade=9" class="class-card grade-9">
                <div class="class-card-cover" style="background-image: linear-gradient(90deg, rgba(243,232,255,.30), rgba(243,232,255,.12)), url('image/anh1.jpg')">
                    <span class="class-badge">9</span>
                    <span class="class-cover-title">LỊCH SỬ</span>
                </div>
                <div class="class-card-content">
                    <h3>Lịch sử 9</h3>
                    <p>Lịch sử Việt Nam hiện đại và những sự kiện thế kỷ XX.</p>
                    <span class="btn-link">Luyện thi ngay <span>→</span></span>
                </div>
            </a>
        </div>
    </section>

    <!-- KẾT QUẢ ĐỀ THI TỪ CSDL -->
    <?php if ($keyword !== '' || $grade !== ''): ?>
    <section class="exam-results" id="de-thi">
        <div class="section-header">
            <div>
                <h2><span class="section-icon">▤</span> Đề thi trắc nghiệm</h2>
                <p>Các đề thi Lịch sử phù hợp với khối lớp bạn chọn</p>
            </div>
            <a href="index.php#de-thi" class="section-more">Xóa bộ lọc <span>↗</span></a>
        </div>

        <div id="examList" class="exam-list">
            <?php if (empty($filtered_exams)): ?>
                <div class="exam-empty">
                    <span>📚</span>
                    <strong>Chưa tìm thấy đề thi phù hợp</strong>
                    <p>Hãy thử từ khóa khác hoặc chọn khối lớp khác.</p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_exams as $index => $exam): ?>
                    <article class="exam-card<?= $index >= $initial_limit ? ' exam-hidden' : '' ?>">
                        <div class="exam-card-top">
                            <span class="exam-tag">Lớp <?= htmlspecialchars((string)$exam['grade'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="exam-subject">Lịch sử</span>
                        </div>
                        <h3><?= htmlspecialchars($exam['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="exam-meta">
                            <span>👤 GV: <?= htmlspecialchars($exam['teacher_name'] ?? 'Chưa cập nhật', ENT_QUOTES, 'UTF-8') ?></span>
                            <span>◷ <?= htmlspecialchars((string)$exam['duration'], ENT_QUOTES, 'UTF-8') ?> phút</span>
                            <span>▤ <?= htmlspecialchars((string)$exam['total_questions'], ENT_QUOTES, 'UTF-8') ?> câu hỏi</span>
                        </div>
                        <a href="intro_exam.php?exam_id=<?= urlencode((string)$exam['exam_id']) ?>" class="slider-btn btn-start-exam">Vào làm bài <span>→</span></a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_filtered > $initial_limit): ?>
            <div class="exam-toggle-wrap">
                <button type="button" id="toggleExamsBtn" class="btn-toggle-exams" data-total="<?= $total_filtered ?>">
                    Hiển thị tất cả (<?= $total_filtered ?> đề) <span>↓</span>
                </button>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- ĐIỂM NỔI BẬT CỦA WEBSITE -->
    <section class="features" id="huong-dan">
        <div class="feature-list">
            <div class="feature-item">
                <div class="feature-icon">◎</div>
                <div class="feature-info">
                    <h4>Hệ thống đề thi đa dạng</h4>
                    <p>Bám sát chương trình học Lịch sử lớp 6–9.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon feature-icon-green">◷</div>
                <div class="feature-info">
                    <h4>Làm bài với thời gian thực</h4>
                    <p>Rèn luyện kỹ năng và quản lý thời gian hiệu quả.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon feature-icon-orange">▥</div>
                <div class="feature-info">
                    <h4>Tự động chấm điểm</h4>
                    <p>Nhận kết quả ngay sau khi hoàn thành bài thi.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon feature-icon-purple">★</div>
                <div class="feature-info">
                    <h4>Theo dõi tiến độ học tập</h4>
                    <p>Xem thống kê kết quả và cải thiện điểm yếu.</p>
                </div>
            </div>
        </div>
    </section>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Xử lý nút Hiển thị tất cả / Thu gọn đề thi
    const toggleBtn = document.getElementById("toggleExamsBtn");
    if (toggleBtn) {
        let isExpanded = false;
        const totalExams = toggleBtn.getAttribute("data-total");
        const examCards = document.querySelectorAll("#examList .exam-card");

        toggleBtn.addEventListener("click", function () {
            isExpanded = !isExpanded;
            examCards.forEach((card, index) => {
                if (index >= 6) {
                    card.classList.toggle("exam-hidden", !isExpanded);
                }
            });

            if (isExpanded) {
                toggleBtn.innerHTML = 'Thu gọn <span>↑</span>';
            } else {
                toggleBtn.innerHTML = `Hiển thị tất cả (${totalExams} đề) <span>↓</span>`;
                document.getElementById("de-thi")?.scrollIntoView({ behavior: "smooth" });
            }
        });
    }

    // 2. Xử lý Slider Banner
    let currentSlide = 0;
    const slides = document.querySelectorAll(".slide");
    const dots = document.querySelectorAll(".dot");
    let slideTimer;

    if (!slides.length) return;

    function showSlide(index) {
        currentSlide = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle("active", i === currentSlide));
        dots.forEach((dot, i) => dot.classList.toggle("active", i === currentSlide));
    }

    function restartTimer() {
        clearInterval(slideTimer);
        slideTimer = setInterval(() => showSlide(currentSlide + 1), 5000);
    }

    window.changeSlide = function (direction) {
        showSlide(currentSlide + direction);
        restartTimer();
    };

    window.showSlide = function (index) {
        showSlide(index);
        restartTimer();
    };

    restartTimer();
});
</script>

<?php include $footerPath; ?>