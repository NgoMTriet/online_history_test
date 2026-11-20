<?php require_once __DIR__ . '/../config/database.php'; ?>

<?php
require_once __DIR__ . '/../api/be_index.php';
echo $headerHtml;

$examTypeBadges = [
    'REGULAR'   => ['text' => ' Đề luyện tập', 'bg' => '#dcfce7', 'color' => '#166534'],
    'MIDTERM_1' => ['text' => ' Giữa kì 1',    'bg' => '#dbeafe', 'color' => '#1e40af'],
    'FINAL_1'   => ['text' => ' Cuối kì 1',    'bg' => '#fee2e2', 'color' => '#991b1b'],
    'MIDTERM_2' => ['text' => ' Giữa kì 2',    'bg' => '#fef3c7', 'color' => '#92400e'],
    'FINAL_2'   => ['text' => ' Cuối kì 2',    'bg' => '#f3e8ff', 'color' => '#6b21a8'],
];

// Lấy danh sách tên môn học và khối lớp trực tiếp từ bảng subjects trong CSDL
$subject_names    = [];
$subjectGradesMap = [];
$available_grades = [];

if (isset($conn) && $conn instanceof PDO) {
    $stmtSubjects = $conn->query(
        "SELECT DISTINCT TRIM(subject_name) AS subject_name, grade
         FROM subjects
         WHERE subject_name IS NOT NULL AND TRIM(subject_name) <> ''
         ORDER BY grade ASC, subject_name ASC"
    );
    $subjectRows = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subjectRows as $row) {
        $sName  = (string)$row['subject_name'];
        $sGrade = (int)$row['grade'];

        if (!in_array($sName, $subject_names, true)) {
            $subject_names[] = $sName;
        }
        if ($sGrade > 0 && !in_array($sGrade, $available_grades, true)) {
            $available_grades[] = $sGrade;
        }
        if ($sGrade > 0) {
            $subjectGradesMap[$sName][] = $sGrade;
        }
    }

    sort($subject_names);
    sort($available_grades, SORT_NUMERIC);

    if (!isset($subject_cards) || !is_array($subject_cards)) {
        $subject_cards = $conn->query(
            "SELECT TRIM(s.subject_name) AS subject_name, s.grade, COUNT(e.exam_id) AS exam_count
             FROM subjects s
             LEFT JOIN exam e ON e.subject_id = s.subject_id
             WHERE s.subject_name IS NOT NULL AND TRIM(s.subject_name) <> ''
             GROUP BY s.subject_id, s.subject_name, s.grade
             ORDER BY s.grade ASC, s.subject_name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (empty($available_grades)) {
    $available_grades = [6, 7, 8, 9];
}

// Giới hạn số thẻ khối-môn hiển thị ban đầu ở Danh mục đề thi
$catalog_initial_limit = 4;
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
        <div class="slide">
            <img src="image/anh4.png" alt="Địa lí" onerror="this.src='../image/anh4.png'">
            <div class="slide-content">
                <span class="subtitle">Tìm hiểu bản đồ</span>
                <h2>KHÁM PHÁ THẾ GIỚI</h2>
                <div class="hero-buttons">
                    <a href="#danh-muc" class="btn-hero-primary">▶ Bắt đầu thi</a>
                    <a href="#huong-dan" class="btn-hero-outline">Khám phá đề thi</a>
                </div>
            </div>
            <div class="quote-box">
                <p>"Trái Đất không thuộc về chúng ta, <br>chúng ta thuộc về Trái Đất."</p>
                <span>— Tù trưởng Seattle —</span>
            </div>
        </div>
        <div class="slide">
            <img src="image/anh5.png" alt="Giáo dục công dân" onerror="this.src='../image/anh5.png'">
            <div class="slide-content">
                <span class="subtitle">Tìm hiểu và học tập</span>
                <h2>QUY ĐỊNH,PHÁP LUẬT, ĐẠO ĐỨC</h2>
                <div class="hero-buttons">
                    <a href="#danh-muc" class="btn-hero-primary">▶ Bắt đầu thi</a>
                    <a href="#huong-dan" class="btn-hero-outline">Khám phá đề thi</a>
                </div>
            </div>
            <div class="quote-box">
                <p>"Có tài mà không có đức là người vô dụng, <br>có đức mà không có tài thì làm việc gì cũng khó."</p>
                <span>— Chủ tịch Hồ Chí Minh —</span>
            </div>
        </div>
        <div class="slide">
            <img src="image/anh6.png" alt="Tin học" onerror="this.src='../image/anh6.png'">
            <div class="slide-content">
                <span class="subtitle">Lập trình, thuật toán</span>
                <h2>LÀM CHỦ THẾ GIỚI SỐ</h2>
                <div class="hero-buttons">
                    <a href="#danh-muc" class="btn-hero-primary">▶ Bắt đầu thi</a>
                    <a href="#huong-dan" class="btn-hero-outline">Khám phá đề thi</a>
                </div>
            </div>
            <div class="quote-box">
                <p>"Trí tuệ nhân tạo là điện năng mới của nhân loại."</p>
                <span>— Andrew Ng —</span>
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
            <span class="dot" onclick="showSlide(3)"></span>
            <span class="dot" onclick="showSlide(4)"></span>
            <span class="dot" onclick="showSlide(5)"></span>
        </div>

    </div>
</section>

<main class="container">

    <!-- TÌM KIẾM VÀ LỌC ĐỀ THI THEO LOGIC: KHỐI -> TÊN MÔN -> LOẠI ĐỀ -->
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
                    <?php foreach ($available_grades as $gOpt): ?>
                        <option value="<?= (int)$gOpt ?>" <?= $grade === (string)$gOpt ? 'selected' : '' ?>>
                            Khối <?= (int)$gOpt ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="exam-grade-filter">
                <span class="filter-icon" aria-hidden="true">📚</span>
                <select name="subject" id="subject" aria-label="Chọn môn học">
                    <option value="">Tất cả môn học</option>
                    <?php foreach ($subject_names as $subjectName): ?>
                        <?php $gradesAttr = implode(',', array_unique($subjectGradesMap[$subjectName] ?? [])); ?>
                        <option
                            value="<?= htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8') ?>"
                            data-grades="<?= htmlspecialchars($gradesAttr, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $subject === $subjectName ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="exam-grade-filter">
                <span class="filter-icon" aria-hidden="true">🏆</span>
                <select name="exam_type" id="exam_type" aria-label="Chọn kỳ thi">
                    <option value="">Tất cả loại đề</option>
                    <option value="REGULAR" <?= $exam_type === 'REGULAR' ? 'selected' : '' ?>>Đề luyện tập</option>
                    <option value="MIDTERM_1" <?= $exam_type === 'MIDTERM_1' ? 'selected' : '' ?>>Giữa kì 1</option>
                    <option value="FINAL_1" <?= $exam_type === 'FINAL_1' ? 'selected' : '' ?>>Cuối kì 1</option>
                    <option value="MIDTERM_2" <?= $exam_type === 'MIDTERM_2' ? 'selected' : '' ?>>Giữa kì 2</option>
                    <option value="FINAL_2" <?= $exam_type === 'FINAL_2' ? 'selected' : '' ?>>Cuối kì 2</option>
                </select>
            </div>

            <button type="submit" class="btn-search-submit"><span>⌕</span> Tìm kiếm</button>
        </form>
    </section>

    <!-- DANH MỤC LỚP 6-9 -->
    <section class="classes" id="danh-muc">
        <div class="section-header" style="flex-wrap: wrap; gap: 12px;">
            <div>
                <h2><span class="section-icon">▣</span> Danh mục đề thi</h2>
                <p>Chọn khối lớp và môn học để bắt đầu luyện tập</p>
            </div>

            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <a href="<?= $grade !== '' ? 'index.php?grade=' . urlencode($grade) . '#de-thi' : '#de-thi' ?>" id="catalogViewExamsLink" class="section-more">Xem đề thi <span>→</span></a>
            </div>
        </div>

        <div class="exam-grade-filter exam-grade-filter-khoi">
            <span class="filter-icon" aria-hidden="true">▣</span>
            <select id="catalogGradeFilter" aria-label="Lọc danh mục theo khối">
                <option value="">Tất cả khối</option>
                <?php foreach ($available_grades as $gOpt): ?>
                    <option value="<?= (int)$gOpt ?>" <?= $grade === (string)$gOpt ? 'selected' : '' ?>>
                        Khối <?= (int)$gOpt ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="class-list" id="catalogClassList">
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
                    <a
                        href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                        class="class-card grade-<?= $g ?>"
                        data-grade="<?= $g ?>"
                        <?= $i >= $catalog_initial_limit ? 'style="display: none;"' : '' ?>
                    >
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

                <div class="exam-empty" id="catalogGradeEmpty" style="display: none; grid-column: 1 / -1;">
                    <span>📚</span>
                    <strong>Chưa có môn học nào thuộc khối này</strong>
                    <p>Vui lòng chọn khối lớp khác để xem danh mục môn học.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($subject_cards) > $catalog_initial_limit): ?>
            <div class="exam-toggle-wrap" id="catalogToggleWrap">
                <button
                    type="button"
                    id="toggleCatalogBtn"
                    class="btn-toggle-exams"
                    data-limit="<?= (int)$catalog_initial_limit ?>"
                >
                    Hiển thị tất cả (<?= count($subject_cards) ?> môn) <span>↓</span>
                </button>
            </div>
        <?php endif; ?>
    </section>

    <!-- KẾT QUẢ ĐỀ THI TỪ CSDL -->
    <?php if ($keyword !== '' || $grade !== '' || $subject !== '' || $exam_type !== ''): ?>
    <section class="exam-results" id="de-thi">
        <div class="section-header">
            <div>
                <h2><span class="section-icon">▤</span> Đề thi trắc nghiệm</h2>
                <p>Các đề thi phù hợp với bộ lọc bạn chọn</p>
            </div>
            <a href="index.php" class="section-more">Xóa bộ lọc <span>↗</span></a>
        </div>

        <!-- THANH LỌC NHANH THEO KỲ THI -->
        <?php
        $quickTabs = [
            ''          => 'Tất cả',
            'REGULAR'   => ' Đề luyện tập',
            'MIDTERM_1' => ' Giữa kì 1',
            'FINAL_1'   => ' Cuối kì 1',
            'MIDTERM_2' => ' Giữa kì 2',
            'FINAL_2'   => ' Cuối kì 2',
        ];
        ?>
        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
            <?php foreach ($quickTabs as $tabKey => $tabLabel): ?>
                <?php
                    $queryParams = [];
                    if ($keyword !== '') $queryParams['keyword'] = $keyword;
                    if ($grade !== '')   $queryParams['grade'] = $grade;
                    if ($subject !== '') $queryParams['subject'] = $subject;
                    if ($tabKey !== '')  $queryParams['exam_type'] = $tabKey;
                    $tabUrl = 'index.php' . (!empty($queryParams) ? '?' . http_build_query($queryParams) : '') . '#de-thi';
                    $isActiveTab = ($exam_type === $tabKey);
                ?>
                <a href="<?= htmlspecialchars($tabUrl, ENT_QUOTES, 'UTF-8') ?>" style="
                    padding: 7px 14px;
                    border-radius: 999px;
                    font-size: 13px;
                    font-weight: 600;
                    text-decoration: none;
                    border: 1px solid <?= $isActiveTab ? '#2563eb' : '#cbd5e1' ?>;
                    background-color: <?= $isActiveTab ? '#2563eb' : '#ffffff' ?>;
                    color: <?= $isActiveTab ? '#ffffff' : '#334155' ?>;
                ">
                    <?= $tabLabel ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div id="examList" class="exam-list">
            <?php if (empty($filtered_exams)): ?>
                <div class="exam-empty">
                    <span>🔍</span>
                    <strong>Không tìm thấy đề thi phù hợp</strong>
                    <p>Hãy thử thay đổi từ khóa, khối lớp, môn học hoặc kỳ thi khác.</p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_exams as $index => $exam): ?>
                    <?php
                        $typeKey = $exam['exam_type'] ?? 'REGULAR';
                        $badgeInfo = $examTypeBadges[$typeKey] ?? $examTypeBadges['REGULAR'];
                    ?>
                    <article class="exam-card<?= $index >= $initial_limit ? ' exam-hidden' : '' ?>">
                        <div class="exam-card-top" style="flex-wrap: wrap; gap: 6px;">
                            <span class="exam-tag">Lớp <?= htmlspecialchars((string)$exam['grade'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="exam-subject"><?= htmlspecialchars($exam['subject_name'] ?? 'Chưa phân môn', ENT_QUOTES, 'UTF-8') ?></span>
                            <span style="
                                background-color: <?= $badgeInfo['bg'] ?>;
                                color: <?= $badgeInfo['color'] ?>;
                                padding: 3px 10px;
                                border-radius: 999px;
                                font-size: 12px;
                                font-weight: 600;
                                margin-left: auto;
                            ">
                                <?= $badgeInfo['text'] ?>
                            </span>
                        </div>
                        <h3><?= htmlspecialchars($exam['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <?php if (!empty($exam['topic_names'])): ?>
                            <p class="exam-topic">📖 <?= htmlspecialchars($exam['topic_names'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <div class="exam-meta">
                            <span>👤 GV: <?= htmlspecialchars($exam['teacher_name'] ?? 'Chưa cập nhật', ENT_QUOTES, 'UTF-8') ?></span>
                            <span>◷ <?= htmlspecialchars((string)$exam['duration'], ENT_QUOTES, 'UTF-8') ?> phút</span>
                            <span>▤ <?= htmlspecialchars((string)$exam['total_questions'], ENT_QUOTES, 'UTF-8') ?> câu hỏi</span>
                            <?php if (array_key_exists('max_attempts', $exam)): ?>
                                <span>🔁 <?= (int)$exam['max_attempts'] === 0 ? 'Không giới hạn lượt' : (int)$exam['max_attempts'] . ' lượt' ?></span>
                            <?php endif; ?>
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
    // 0A. Lọc logic ở thanh tìm kiếm trên cùng: Khối -> Tên môn (lấy từ CSDL)
    const topGradeSelect = document.getElementById("grade");
    const topSubjectSelect = document.getElementById("subject");

    function syncSubjectOptionsByGrade() {
        if (!topGradeSelect || !topSubjectSelect) return;
        const selectedGrade = topGradeSelect.value;
        const options = topSubjectSelect.querySelectorAll("option");
        let isCurrentValid = false;

        options.forEach(opt => {
            if (opt.value === "") {
                opt.hidden = false;
                opt.disabled = false;
                if (topSubjectSelect.value === "") isCurrentValid = true;
                return;
            }
            const gradesAttr = opt.getAttribute("data-grades") || "";
            const allowedGrades = gradesAttr !== "" ? gradesAttr.split(",") : [];
            const isMatch = selectedGrade === "" ? true : allowedGrades.includes(selectedGrade);

            opt.hidden = !isMatch;
            opt.disabled = !isMatch;

            if (isMatch && opt.value === topSubjectSelect.value) {
                isCurrentValid = true;
            }
        });

        if (!isCurrentValid) {
            topSubjectSelect.value = "";
        }
    }

    if (topGradeSelect && topSubjectSelect) {
        topGradeSelect.addEventListener("change", syncSubjectOptionsByGrade);
        syncSubjectOptionsByGrade();
    }

    // 0B. Lọc danh mục đề thi theo khối lớp + Ẩn bớt / Hiển thị tất cả
    const catalogGradeFilter = document.getElementById("catalogGradeFilter");
    const catalogCards = Array.from(document.querySelectorAll("#catalogClassList .class-card"));
    const catalogEmpty = document.getElementById("catalogGradeEmpty");
    const catalogViewExamsLink = document.getElementById("catalogViewExamsLink");
    const toggleCatalogBtn = document.getElementById("toggleCatalogBtn");
    const catalogToggleWrap = document.getElementById("catalogToggleWrap");
    const catalogInitialLimit = toggleCatalogBtn
        ? parseInt(toggleCatalogBtn.getAttribute("data-limit") || "4", 10)
        : 4;
    let isCatalogExpanded = false;

    function updateCatalogDisplay() {
        const selectedGrade = catalogGradeFilter ? catalogGradeFilter.value : "";
        const matchedCards = [];

        catalogCards.forEach(card => {
            const cardGrade = card.getAttribute("data-grade") || "";
            const isMatch = selectedGrade === "" ? true : cardGrade === selectedGrade;
            if (isMatch) {
                matchedCards.push(card);
            } else {
                card.style.display = "none";
            }
        });

        matchedCards.forEach((card, index) => {
            const shouldHide = !isCatalogExpanded && index >= catalogInitialLimit;
            card.style.display = shouldHide ? "none" : "";
        });

        if (catalogEmpty) {
            catalogEmpty.style.display = (catalogCards.length > 0 && matchedCards.length === 0) ? "" : "none";
        }

        if (catalogViewExamsLink) {
            catalogViewExamsLink.href = selectedGrade !== ""
                ? `index.php?grade=${encodeURIComponent(selectedGrade)}#de-thi`
                : "#de-thi";
        }

        if (toggleCatalogBtn) {
            const hasMore = matchedCards.length > catalogInitialLimit;
            if (catalogToggleWrap) {
                catalogToggleWrap.style.display = hasMore ? "" : "none";
            }
            toggleCatalogBtn.style.display = hasMore ? "" : "none";

            if (hasMore) {
                toggleCatalogBtn.innerHTML = isCatalogExpanded
                    ? 'Thu gọn <span>↑</span>'
                    : `Hiển thị tất cả (${matchedCards.length} môn) <span>↓</span>`;
            }
        }
    }

    if (catalogGradeFilter) {
        catalogGradeFilter.addEventListener("change", function () {
            isCatalogExpanded = false;
            updateCatalogDisplay();
        });
    }

    if (toggleCatalogBtn) {
        toggleCatalogBtn.addEventListener("click", function () {
            isCatalogExpanded = !isCatalogExpanded;
            updateCatalogDisplay();
            if (!isCatalogExpanded) {
                document.getElementById("danh-muc")?.scrollIntoView({ behavior: "smooth" });
            }
        });
    }

    updateCatalogDisplay();

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