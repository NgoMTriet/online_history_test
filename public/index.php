<?php require_once __DIR__ . '/../config/database.php'; ?>

<?php
require_once __DIR__ . '/../api/be_index.php';
echo $headerHtml;
?>

<!-- HERO SLIDER TRÀN VIỀN -->
<section class="hero">
    <div class="slider">

        <!-- Slide 1 -->
        <div class="slide active">
            <img src="image/anh1.png" alt="Lịch sử Việt Nam" onerror="this.src='../image/anh1.png'">
            <div class="slide-content">
                <span class="subtitle">Học mỗi ngày</span>
                <h2>LUYỆN THI TRẮC NGHIỆM</h2>
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

        <!-- Slide 2 -->
        <div class="slide">
            <img src="image/anh2.png" alt="Di tích lịch sử Việt Nam" onerror="this.src='../image/anh2.png'">
            <div class="slide-content">
                <span class="subtitle">Mỗi bài học là một hành trình</span>
                <h2>KHÁM PHÁ KIẾN THỨC</h2>
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

        <!-- Slide 3 -->
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

        <!-- Nút chuyển -->
        <button type="button" class="slider-prev" onclick="changeSlide(-1)" aria-label="Slide trước">&#10094;</button>
        <button type="button" class="slider-next" onclick="changeSlide(1)" aria-label="Slide tiếp theo">&#10095;</button>

        <!-- Dấu chấm -->
        <div class="slider-dots">
            <span class="dot active" onclick="showSlide(0)"></span>
            <span class="dot" onclick="showSlide(1)"></span>
            <span class="dot" onclick="showSlide(2)"></span>
        </div>

    </div>
</section>

<main class="container">

    <!-- TÌM KIẾM VÀ LỌC ĐỀ THI -->
    <section class="exam-search" aria-label="Tìm kiếm đề thi">
        <form class="exam-search-form" action="index.php#de-thi" method="GET">
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

            <div class="exam-grade-filter">
                <span class="filter-icon" aria-hidden="true">📚</span>
                <select name="subject" id="subject" aria-label="Chọn môn học">
                    <option value="">Tất cả môn học</option>
                    <?php foreach ($subject_names as $subjectName): ?>
                        <option value="<?= htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8') ?>" <?= $subject === $subjectName ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-search-submit"><span>⌕</span> Tìm kiếm</button>
        </form>
    </section>

    <!-- DANH MỤC LỚP 6-9 -->
    <section class="classes" id="danh-muc">
        <div class="section-header">
            <div>
                <h2><span class="section-icon">▣</span> Danh mục đề thi</h2>
                <p>Chọn lớp và môn học để bắt đầu luyện tập</p>
            </div>
            <a href="#de-thi" class="section-more">Xem đề thi <span>→</span></a>
        </div>

        <div class="class-list">
            <?php
            $gradeTints  = [6 => '224,242,254', 7 => '255,237,213', 8 => '220,252,231', 9 => '243,232,255'];
            $coverImages = ['anh1.jpg', 'anh2.jpg', 'anh3.jpg'];
            ?>
            <?php if (empty($subject_cards)): ?>
                <div class="exam-empty">
                    <span>📚</span>
                    <strong>Chưa có môn học nào</strong>
                    <p>Giáo viên hãy tạo môn học trong trang Ngân hàng câu hỏi.</p>
                </div>
            <?php else: ?>
                <?php foreach ($subject_cards as $i => $card): ?>
                    <?php
                        $g    = (int)$card['grade'];
                        $tint = $gradeTints[$g] ?? '224,242,254';
                        $img  = $coverImages[$i % count($coverImages)];
                        $name = htmlspecialchars($card['subject_name'], ENT_QUOTES, 'UTF-8');
                        $href = 'index.php?grade=' . $g . '&subject=' . urlencode($card['subject_name']) . '#de-thi';
                    ?>
                    <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="class-card grade-<?= $g ?>">
                        <div class="class-card-cover" style="background-image: linear-gradient(90deg, rgba(<?= $tint ?>,.30), rgba(<?= $tint ?>,.12)), url('image/<?= $img ?>')">
                            <span class="class-badge"><?= $g ?></span>
                            <span class="class-cover-title"><?= htmlspecialchars(mb_strtoupper($card['subject_name'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="class-card-content">
                            <h3><?= $name ?> <?= $g ?></h3>
                            <p>Luyện thi trắc nghiệm môn <?= $name ?> lớp <?= $g ?> · <?= (int)$card['exam_count'] ?> đề thi.</p>
                            <span class="btn-link">Luyện thi ngay <span>→</span></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- KẾT QUẢ ĐỀ THI TỪ CSDL -->
    <?php if ($keyword !== '' || $grade !== '' || $subject !== ''): ?>
    <section class="exam-results" id="de-thi">
        <div class="section-header">
            <div>
                <h2><span class="section-icon">▤</span> Đề thi trắc nghiệm</h2>
                <p>Các đề thi phù hợp với bộ lọc bạn chọn</p>
            </div>
            <a href="index.php" class="section-more">Xóa bộ lọc <span>↗</span></a>
        </div>

        <div id="examList" class="exam-list">
            <?php if (empty($filtered_exams)): ?>
                <div class="exam-empty">
                    <span>🔍</span>
                    <strong>Không tìm thấy đề thi phù hợp</strong>
                    <p>Hãy thử thay đổi từ khóa, khối lớp hoặc môn học khác.</p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_exams as $index => $exam): ?>
                    <article class="exam-card<?= $index >= $initial_limit ? ' exam-hidden' : '' ?>">
                        <div class="exam-card-top">
                            <span class="exam-tag">Lớp <?= htmlspecialchars((string)$exam['grade'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="exam-subject"><?= htmlspecialchars($exam['subject_name'] ?? 'Chưa phân môn', ENT_QUOTES, 'UTF-8') ?></span>
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
                <button
                    type="button"
                    id="toggleExamsBtn"
                    class="btn-toggle-exams"
                    data-total="<?= (int)$total_filtered ?>"
                    data-limit="<?= (int)$initial_limit ?>"
                >
                    Hiển thị tất cả (<?= (int)$total_filtered ?> đề) <span>↓</span>
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
                    <p>Bám sát chương trình học THCS nhiều môn.</p>
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
        const initialLimit = parseInt(toggleBtn.getAttribute("data-limit") || "6", 10);
        const examCards = document.querySelectorAll("#examList .exam-card");

        toggleBtn.addEventListener("click", function () {
            isExpanded = !isExpanded;
            examCards.forEach((card, index) => {
                if (index >= initialLimit) {
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

    if (slides.length === 0) return;

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

    window.changeSlide = function (direction) {
        showSlide(currentSlide + direction);
    };

    window.showSlide = function (index) {
        showSlide(index);
    };

    setInterval(() => {
        window.changeSlide(1);
    }, 5000);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
