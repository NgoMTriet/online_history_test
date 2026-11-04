<?php
// 🛠️ TỰ ĐỘNG XÁC ĐỊNH ĐƯỜNG DẪN TỆP HỆ THỐNG (CHUẨN HOÁ PATH)
$configPath = file_exists(__DIR__ . '/config/database.php')
    ? __DIR__ . '/config/database.php'
    : __DIR__ . '/../config/database.php';

$headerPath = file_exists(__DIR__ . '/includes/header.php')
    ? __DIR__ . '/includes/header.php'
    : __DIR__ . '/../includes/header.php';

$footerPath = file_exists(__DIR__ . '/includes/footer.php')
    ? __DIR__ . '/includes/footer.php'
    : __DIR__ . '/../includes/footer.php';

// Nạp file cấu hình Cơ sở dữ liệu
require_once $configPath;

// Nạp header và gắn file CSS riêng của trang chủ vào <head>

ob_start();
include $headerPath;
$headerHtml = ob_get_clean();

// Loại bỏ liên kết style.css cũ để không xung đột CSS
$headerHtml = preg_replace(
    '~<link\b(?=[^>]*\bhref\s*=\s*["\'][^"\']*style\.css(?:\?[^"\']*)?["\'])[^>]*>\s*~i',
    '',
    $headerHtml
);

$styleTag = '<link rel="stylesheet" href="assets/css/index.css?v=1">' . "\n";
if (stripos($headerHtml, '</head>') !== false) {
    $headerHtml = preg_replace_callback('~</head>~i', function () use ($styleTag) {
        return $styleTag . '</head>';
    }, $headerHtml, 1);
} else {
    $headerHtml .= $styleTag;
}

echo $headerHtml;

$isTeacher = (
    isset($_SESSION['user_id'], $_SESSION['role'])
    && strtoupper($_SESSION['role']) === 'TEACHER'
);
?>

<main class="container">

    <!-- BANNER GIỚI THIỆU -->
    <section class="hero">
        <div class="container">
            <div class="slider">

                <div class="slide active">
                    <img src="image/anh1.jpg" alt="Lịch sử Việt Nam" onerror="this.src='../image/anh1.jpg'">
                    <div class="slide-content">
                        <span class="subtitle">Khám phá quá khứ</span>
                        <h2>LỊCH SỬ VIỆT NAM</h2>
                        <p>Luyện tập trắc nghiệm, nắm vững kiến thức lịch sử Việt Nam từ lớp 6 đến lớp 9.</p>
                        <div class="hero-buttons">
                            <a href="#danh-muc" class="btn-hero-primary">▶ Bắt đầu thi ngay <span>›</span></a>
                            <a href="#huong-dan" class="btn-hero-outline">▣ Khám phá đề thi</a>
                        </div>
                    </div>
                    <div class="quote-box">
                        <p>“Dân ta phải biết sử ta<br>Cho tường gốc tích nước nhà Việt Nam”</p>
                        <span>— Hồ Chí Minh —</span>
                    </div>
                </div>

                <div class="slide">
                    <img src="image/anh2.jpg" alt="Di tích lịch sử Việt Nam" onerror="this.src='../image/anh2.jpg'">
                    <div class="slide-content">
                        <span class="subtitle">Mỗi bài học là một hành trình</span>
                        <h2>KHÁM PHÁ LỊCH SỬ</h2>
                        <p>Ôn tập kiến thức lịch sử THCS một cách dễ dàng, trực quan và thú vị.</p>
                        <div class="hero-buttons">
                            <a href="#danh-muc" class="btn-hero-primary">▶ Bắt đầu thi ngay <span>›</span></a>
                            <a href="#huong-dan" class="btn-hero-outline">▣ Khám phá đề thi</a>
                        </div>
                    </div>
                </div>

                <div class="slide">
                    <img src="image/anh3.jpg" alt="Tư liệu lịch sử Việt Nam" onerror="this.src='../image/anh3.jpg'">
                    <div class="slide-content">
                        <span class="subtitle">Ôn luyện và bứt phá</span>
                        <h2>CHINH PHỤC KIẾN THỨC</h2>
                        <p>Củng cố kiến thức Lịch sử lớp 6, 7, 8 và 9 qua những bài kiểm tra trắc nghiệm.</p>
                        <div class="hero-buttons">
                            <a href="#danh-muc" class="btn-hero-primary">▶ Bắt đầu thi ngay <span>›</span></a>
                            <a href="#huong-dan" class="btn-hero-outline">▣ Khám phá đề thi</a>
                        </div>
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

    <?php
    // Đọc bộ lọc tìm kiếm
    $keyword = trim($_GET['keyword'] ?? '');
    $grade = trim($_GET['grade'] ?? '');

    // Truy vấn dữ liệu từ CSDL
// TRUY VẤN MỚI
// Truy vấn dữ liệu từ CSDL lấy kèm tên Giáo viên
$sql = "SELECT e.exam_id, e.title, e.grade, e.duration, e.total_questions, u.full_name AS teacher_name 
        FROM exam e 
        LEFT JOIN users u ON e.user_id = u.user_id 
        WHERE 1=1";
$params = [];

if (!empty($keyword)) {
    $sql .= " AND e.title LIKE :keyword";
    $params['keyword'] = '%' . $keyword . '%';
}

if (!empty($grade)) {
    $sql .= " AND e.grade = :grade";
    $params['grade'] = (int)$grade;
}

    $sql .= " ORDER BY exam_id DESC";

    $stmt_exams = $conn->prepare($sql);
    $stmt_exams->execute($params);
    $filtered_exams = $stmt_exams->fetchAll();
    ?>

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
                    <option value="6" <?= $grade == '6' ? 'selected' : '' ?>>Lớp 6</option>
                    <option value="7" <?= $grade == '7' ? 'selected' : '' ?>>Lớp 7</option>
                    <option value="8" <?= $grade == '8' ? 'selected' : '' ?>>Lớp 8</option>
                    <option value="9" <?= $grade == '9' ? 'selected' : '' ?>>Lớp 9</option>
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

    <!-- CÁC ĐỀ THI BẮT ĐƯỢC TỪ CƠ SỞ DỮ LIỆU -->
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
                <?php foreach ($filtered_exams as $exam): ?>
                    <article class="exam-card">
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