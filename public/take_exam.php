<?php
// ⏱️ Thiết lập múi giờ Việt Nam để lưu start_time & end_time vào Database chuẩn giờ +7
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/teacher_layout.php';

// Chỉ để giao diện hiển thị đúng tên người dùng ở thanh trên (không thay đổi logic)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| XỬ LÝ TẠO ĐỀ THI
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'create_exam'
) {
    $title = trim($_POST['title'] ?? '');
    $grade = (int)($_POST['grade'] ?? 9);
    $duration = (int)($_POST['duration'] ?? 15);
    $maxAttempts = (int)($_POST['max_attempts'] ?? 0); // 0 = Không giới hạn
    $examPassword = trim($_POST['exam_password'] ?? ''); // Mật khẩu bài thi
    $mode = $_POST['create_mode'] ?? 'manual';

    // ⏱️ Xử lý thời gian mở đề thi (start_time)
    $startTimeRaw = trim($_POST['start_time'] ?? '');
    $startTime = null;
    if ($startTimeRaw !== '') {
        $timestampStart = strtotime($startTimeRaw);
        if ($timestampStart !== false) {
            $startTime = date('Y-m-d H:i:s', $timestampStart);
        }
    }

    // 🛑 Xử lý thời gian đóng đề thi (end_time)
    $endTimeRaw = trim($_POST['end_time'] ?? '');
    $endTime = null;
    if ($endTimeRaw !== '') {
        $timestampEnd = strtotime($endTimeRaw);
        if ($timestampEnd !== false) {
            $endTime = date('Y-m-d H:i:s', $timestampEnd);
        }
    }

    // Validation kiểm tra hợp lệ
    if ($title === '') {
        $message = 'Vui lòng nhập tên đề thi.';
        $messageType = 'danger';
    } elseif (!in_array($grade, [6, 7, 8, 9], true)) {
        $message = 'Khối lớp không hợp lệ.';
        $messageType = 'danger';
    } elseif ($duration < 1 || $duration > 300) {
        $message = 'Thời gian làm bài phải từ 1 đến 300 phút.';
        $messageType = 'danger';
    } elseif ($maxAttempts < 0) {
        $message = 'Số lần làm bài không hợp lệ.';
        $messageType = 'danger';
    } elseif ($startTime !== null && $endTime !== null && strtotime($endTime) <= strtotime($startTime)) {
        $message = 'Thời gian đóng đề thi phải sau thời gian mở đề thi.';
        $messageType = 'danger';
    } elseif (!in_array($mode, ['manual', 'auto'], true)) {
        $message = 'Phương thức chọn câu hỏi không hợp lệ.';
        $messageType = 'danger';
    } else {
        try {
            $selectedQuestionIds = [];

            if ($mode === 'manual') {
                $rawIds = $_POST['question_ids'] ?? [];

                if (!is_array($rawIds)) {
                    $rawIds = [];
                }

                $selectedQuestionIds = array_values(
                    array_unique(
                        array_filter(
                            array_map('intval', $rawIds),
                            fn($id) => $id > 0
                        )
                    )
                );

                if (count($selectedQuestionIds) > 0) {
                    $placeholders = implode(
                        ',',
                        array_fill(0, count($selectedQuestionIds), '?')
                    );

                    $stmtCheck = $conn->prepare(
                        "SELECT q.question_id
                         FROM questions q
                         JOIN topics t ON q.topic_id = t.topic_id
                         WHERE t.grade = ?
                         AND q.question_id IN ($placeholders)"
                    );

                    $stmtCheck->execute(
                        array_merge([$grade], $selectedQuestionIds)
                    );

                    $validIds = array_map(
                        'intval',
                        $stmtCheck->fetchAll(PDO::FETCH_COLUMN)
                    );

                    $selectedQuestionIds = array_values(
                        array_intersect($selectedQuestionIds, $validIds)
                    );
                }
            } else {
                // Chế độ Bốc ngẫu nhiên theo Chủ đề & 3 Mức độ
                $topicId = (int)($_POST['auto_topic_id'] ?? 0);
                $countEasy = max(0, (int)($_POST['count_easy'] ?? 0));
                $countMedium = max(0, (int)($_POST['count_medium'] ?? 0));
                $countHard = max(0, (int)($_POST['count_hard'] ?? 0));

                if ($countEasy + $countMedium + $countHard === 0) {
                    throw new Exception('Vui lòng nhập số lượng cho ít nhất 1 mức độ câu hỏi.');
                }

                $levelsMap = [
                    'EASY' => $countEasy,
                    'MEDIUM' => $countMedium,
                    'HARD' => $countHard
                ];

                foreach ($levelsMap as $level => $count) {
                    if ($count <= 0) continue;

                    $sql = "SELECT q.question_id
                            FROM questions q
                            JOIN topics t ON q.topic_id = t.topic_id
                            WHERE t.grade = :grade AND q.difficulty = :level";

                    if ($topicId > 0) {
                        $sql .= " AND q.topic_id = :topic_id";
                    }

                    $sql .= " ORDER BY RAND() LIMIT :limit";

                    $stmtRandom = $conn->prepare($sql);
                    $stmtRandom->bindValue(':grade', $grade, PDO::PARAM_INT);
                    $stmtRandom->bindValue(':level', $level, PDO::PARAM_STR);
                    $stmtRandom->bindValue(':limit', $count, PDO::PARAM_INT);

                    if ($topicId > 0) {
                        $stmtRandom->bindValue(':topic_id', $topicId, PDO::PARAM_INT);
                    }

                    $stmtRandom->execute();
                    $fetchedIds = array_map('intval', $stmtRandom->fetchAll(PDO::FETCH_COLUMN));
                    
                    $selectedQuestionIds = array_merge($selectedQuestionIds, $fetchedIds);
                }
            }

            $totalQuestions = count($selectedQuestionIds);

            if ($totalQuestions === 0) {
                throw new Exception(
                    'Không tìm thấy câu hỏi phù hợp với cấu hình. Vui lòng kiểm tra lại Ngân hàng câu hỏi.'
                );
            }

            $conn->beginTransaction();

            // Lưu thông tin đề thi (có lưu start_time, end_time và exam_password)
            $stmtExam = $conn->prepare(
                "INSERT INTO exam
                 (title, grade, duration, start_time, end_time, max_attempts, exam_password, total_questions, is_randomized)
                 VALUES (:title, :grade, :duration, :start_time, :end_time, :max_attempts, :exam_password, :total_questions, :is_randomized)"
            );

            $stmtExam->execute([
                'title' => $title,
                'grade' => $grade,
                'duration' => $duration,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'max_attempts' => $maxAttempts,
                'exam_password' => $examPassword !== '' ? $examPassword : null,
                'total_questions' => $totalQuestions,
                'is_randomized' => ($mode === 'auto') ? 1 : 0
            ]);

            $examId = (int)$conn->lastInsertId();

            // Lưu danh sách câu hỏi
            $stmtLink = $conn->prepare(
                "INSERT INTO exam_questions (exam_id, question_id)
                 VALUES (:exam_id, :question_id)"
            );

            foreach ($selectedQuestionIds as $questionId) {
                $stmtLink->execute([
                    'exam_id' => $examId,
                    'question_id' => $questionId
                ]);
            }

            $conn->commit();

            $message = "Tạo đề thi thành công! Đề thi gồm {$totalQuestions} câu hỏi.";
            $messageType = 'success';

        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

/*
|--------------------------------------------------------------------------
| LẤY NGÂN HÀNG CÂU HỎI VÀ TẤT CẢ CHỦ ĐỀ
|--------------------------------------------------------------------------
*/
$stmtTopics = $conn->query("SELECT topic_id, topic_name, grade FROM topics ORDER BY grade ASC, topic_name ASC");
$allTopics = $stmtTopics->fetchAll(PDO::FETCH_ASSOC);

$stmtQuestions = $conn->query(
    "SELECT q.question_id, q.content, q.difficulty, t.grade, t.topic_name, t.topic_id
     FROM questions q
     JOIN topics t ON q.topic_id = t.topic_id
     ORDER BY t.grade ASC, q.question_id DESC"
);

$questionsBank = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);
$questionCounts = [6 => 0, 7 => 0, 8 => 0, 9 => 0];

// Mảng rút gọn gửi qua JavaScript để tính toán số lượng khả dụng
$jsQuestionsData = [];

foreach ($questionsBank as $question) {
    $g = (int)$question['grade'];
    if (isset($questionCounts[$g])) {
        $questionCounts[$g]++;
    }

    $jsQuestionsData[] = [
        'g' => (int)$question['grade'],
        't' => (int)$question['topic_id'],
        'd' => strtoupper($question['difficulty'] ?? 'EASY')
    ];
}

teacher_layout_start([
    'title'   => 'Tạo đề thi | TestSuViet',
    'heading' => 'Tạo đề thi',
    'active'  => 'exam',
    'home'    => '../index.php',
]);

$inputCls = 'w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none';
$labelCls = 'block text-xs font-semibold text-slate-700 mb-1';
$cardCls  = 'bg-white p-6 rounded-2xl border border-slate-100 shadow-sm';
?>

<div class="<?= $cardCls ?>">
    <h2 class="text-lg font-bold text-slate-800">📝 Tạo đề thi mới</h2>
    <p class="text-xs text-slate-500 mt-0.5">Thiết lập đề thi, thời gian mở/đóng đề, lượt làm, mật khẩu bảo vệ và cấu trúc câu hỏi.</p>
</div>

<?php if ($message !== ''): ?>
    <div class="flex items-center gap-2.5 px-4 py-3 rounded-2xl text-sm font-semibold border <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
        <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check text-emerald-500' : 'fa-triangle-exclamation text-rose-500' ?>"></i>
        <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<form method="POST" id="examForm" class="space-y-6">
    <input type="hidden" name="action" value="create_exam">

    <!-- PHẦN 1: THÔNG TIN CHUNG -->
    <section class="<?= $cardCls ?> space-y-4">
        <h3 class="font-bold text-slate-800 text-sm border-b border-slate-100 pb-3">1. Thông tin chung</h3>

        <div>
            <label for="title" class="<?= $labelCls ?>">Tên đề thi</label>
            <input type="text" id="title" name="title" placeholder="Ví dụ: Đề thi Ôn tập Chương 1 - Lịch sử 9" required class="<?= $inputCls ?>">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label for="gradeSelect" class="<?= $labelCls ?>">Khối lớp</label>
                <select name="grade" id="gradeSelect" class="<?= $inputCls ?>">
                    <?php for ($g = 6; $g <= 9; $g++): ?>
                        <option value="<?= $g ?>" <?= $g === 9 ? 'selected' : '' ?>>
                            Lớp <?= $g ?> — <?= $questionCounts[$g] ?> câu
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label for="duration" class="<?= $labelCls ?>">Thời gian làm bài (phút)</label>
                <input type="number" id="duration" name="duration" value="15" min="1" max="300" required class="<?= $inputCls ?>">
            </div>

            <div>
                <label for="maxAttempts" class="<?= $labelCls ?>">Số lần làm tối đa</label>
                <select name="max_attempts" id="maxAttempts" class="<?= $inputCls ?>">
                    <option value="0">Không giới hạn</option>
                    <option value="1">1 lần</option>
                    <option value="2">2 lần</option>
                    <option value="3">3 lần</option>
                    <option value="5">5 lần</option>
                </select>
            </div>

            <div>
                <label for="startTime" class="<?= $labelCls ?>">📅 Thời gian mở đề thi</label>
                <input type="datetime-local" id="startTime" name="start_time" class="<?= $inputCls ?>">
                <small class="text-[11px] text-slate-400 block mt-1">Để trống nếu muốn mở đề ngay lập tức.</small>
            </div>

            <div>
                <label for="endTime" class="<?= $labelCls ?>">🛑 Thời gian đóng đề thi</label>
                <input type="datetime-local" id="endTime" name="end_time" class="<?= $inputCls ?>">
                <small class="text-[11px] text-slate-400 block mt-1">Quá thời gian này học sinh không thể vào thi.</small>
            </div>

            <div>
                <label for="examPassword" class="<?= $labelCls ?>">🔑 Mật khẩu đề thi</label>
                <input type="text" id="examPassword" name="exam_password" placeholder="Để trống nếu tự do" maxlength="50" class="<?= $inputCls ?>">
            </div>
        </div>
    </section>

    <!-- PHẦN 2: PHƯƠNG THỨC CHỌN CÂU HỎI -->
    <section class="<?= $cardCls ?> space-y-4">
        <h3 class="font-bold text-slate-800 text-sm border-b border-slate-100 pb-3">2. Phương thức chọn câu hỏi</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="flex items-start gap-3 p-4 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer has-[:checked]:bg-brand-50 has-[:checked]:border-brand-600">
                <input type="radio" id="modeManual" name="create_mode" value="manual" checked class="mt-1 accent-teal-600">
                <label for="modeManual" class="cursor-pointer">
                    <strong class="block text-sm text-slate-800"><i class="fa-solid fa-list-check mr-1"></i> Chọn thủ công</strong>
                    <small class="text-xs text-slate-500">Tự chọn từng câu hỏi đưa vào đề thi.</small>
                </label>
            </div>

            <div class="flex items-start gap-3 p-4 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer has-[:checked]:bg-brand-50 has-[:checked]:border-brand-600">
                <input type="radio" id="modeAuto" name="create_mode" value="auto" class="mt-1 accent-teal-600">
                <label for="modeAuto" class="cursor-pointer">
                    <strong class="block text-sm text-slate-800"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Bốc câu hỏi ngẫu nhiên</strong>
                    <small class="text-xs text-slate-500">Bốc theo chủ đề và cấu hình 3 mức độ.</small>
                </label>
            </div>
        </div>

        <!-- 1. CHỌN THỦ CÔNG -->
        <div id="manualSection">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="px-3 py-1.5 bg-brand-100 text-brand-700 rounded-lg text-xs font-bold">Đã chọn <span id="selectedCount">0</span> câu</span>
                <input type="text" id="questionSearch" placeholder="🔎 Tìm nội dung câu hỏi..." class="<?= $inputCls ?> max-w-xs">
            </div>

            <div class="max-h-96 overflow-y-auto p-2 border border-slate-200 rounded-xl bg-slate-50 space-y-2">
                <?php foreach ($questionsBank as $q): ?>
                    <?php
                        $diffClass = 'bg-emerald-100 text-emerald-700';
                        $diffText = 'Nhận biết';
                        $diffUpper = strtoupper($q['difficulty'] ?? 'EASY');
                        if ($diffUpper === 'MEDIUM') { $diffClass = 'bg-amber-100 text-amber-700'; $diffText = 'Thông hiểu'; }
                        if ($diffUpper === 'HARD') { $diffClass = 'bg-rose-100 text-rose-700'; $diffText = 'Vận dụng'; }
                    ?>
                    <div class="question-row flex items-start gap-3 p-3 bg-white border border-slate-200/80 rounded-xl" data-grade="<?= (int)$q['grade'] ?>" data-search="<?= htmlspecialchars(mb_strtolower($q['content'] . ' ' . $q['topic_name'], 'UTF-8')) ?>">
                        <input type="checkbox" name="question_ids[]" value="<?= (int)$q['question_id'] ?>" id="q_<?= (int)$q['question_id'] ?>" class="question-checkbox mt-1 w-4 h-4 accent-teal-600">
                        <label for="q_<?= (int)$q['question_id'] ?>" class="text-xs text-slate-800 cursor-pointer flex-1">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold mr-1 <?= $diffClass ?>"><?= $diffText ?></span>
                            <strong class="text-brand-700">[<?= htmlspecialchars($q['topic_name']) ?>]</strong>
                            <?= htmlspecialchars($q['content']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 2. BỐC NGẪU NHIÊN CHỦ ĐỀ & 3 MỨC ĐỘ -->
        <div id="autoSection" hidden>
            <div class="p-5 bg-brand-50/60 border border-brand-100 rounded-2xl space-y-4">
                <div>
                    <label for="autoTopic" class="<?= $labelCls ?>">Chọn Chủ đề bài học</label>
                    <select name="auto_topic_id" id="autoTopic" disabled class="<?= $inputCls ?>">
                        <option value="0">-- Tất cả các chủ đề (Toàn bộ chương trình lớp) --</option>
                        <?php foreach ($allTopics as $t): ?>
                            <option value="<?= (int)$t['topic_id'] ?>" data-grade="<?= (int)$t['grade'] ?>">
                                Lớp <?= (int)$t['grade'] ?> - <?= htmlspecialchars($t['topic_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <p class="text-xs font-semibold text-slate-700">Cấu hình số lượng câu hỏi theo 3 mức độ:</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                        <label class="block text-xs font-bold text-emerald-800 mb-1">
                            🟢 Nhận biết (EASY)
                            <span class="ml-1 px-2 py-0.5 rounded-full bg-white border border-emerald-200 text-[10px] font-semibold" id="availEasy">Hiện có: 0</span>
                        </label>
                        <input type="number" class="auto-input w-full border border-emerald-200 rounded-lg px-3 py-1.5 text-sm font-bold text-emerald-900 focus:outline-none" id="countEasy" name="count_easy" value="5" min="0" max="50" disabled>
                    </div>
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-100">
                        <label class="block text-xs font-bold text-amber-800 mb-1">
                            🟡 Thông hiểu (MEDIUM)
                            <span class="ml-1 px-2 py-0.5 rounded-full bg-white border border-amber-200 text-[10px] font-semibold" id="availMedium">Hiện có: 0</span>
                        </label>
                        <input type="number" class="auto-input w-full border border-amber-200 rounded-lg px-3 py-1.5 text-sm font-bold text-amber-900 focus:outline-none" id="countMedium" name="count_medium" value="3" min="0" max="50" disabled>
                    </div>
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-100">
                        <label class="block text-xs font-bold text-rose-800 mb-1">
                            🔴 Vận dụng (HARD)
                            <span class="ml-1 px-2 py-0.5 rounded-full bg-white border border-rose-200 text-[10px] font-semibold" id="availHard">Hiện có: 0</span>
                        </label>
                        <input type="number" class="auto-input w-full border border-rose-200 rounded-lg px-3 py-1.5 text-sm font-bold text-rose-900 focus:outline-none" id="countHard" name="count_hard" value="2" min="0" max="50" disabled>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl shadow-md transition">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i> Lưu đề thi
            </button>
        </div>
    </section>
</form>

<script>
    // Dữ liệu ngân hàng câu hỏi dùng để đếm tự động
    const questionsData = <?= json_encode($jsQuestionsData, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    const gradeSelect = document.getElementById('gradeSelect');
    const modeManual = document.getElementById('modeManual');
    const modeAuto = document.getElementById('modeAuto');
    const manualSection = document.getElementById('manualSection');
    const autoSection = document.getElementById('autoSection');
    const autoTopic = document.getElementById('autoTopic');
    const autoInputs = document.querySelectorAll('.auto-input');
    const questionSearch = document.getElementById('questionSearch');
    const selectedCount = document.getElementById('selectedCount');

    // Các phần tử thời gian
    const startTimeInput = document.getElementById('startTime');
    const endTimeInput = document.getElementById('endTime');

    // Thẻ hiển thị số lượng khả dụng
    const availEasy = document.getElementById('availEasy');
    const availMedium = document.getElementById('availMedium');
    const availHard = document.getElementById('availHard');

    const countEasyInput = document.getElementById('countEasy');
    const countMediumInput = document.getElementById('countMedium');
    const countHardInput = document.getElementById('countHard');

    // Cập nhật đếm số câu hiện có theo Khối lớp & Chủ đề đang chọn
    function updateAvailableCounts() {
        const selectedGrade = parseInt(gradeSelect.value, 10);
        const selectedTopicId = parseInt(autoTopic.value, 10);

        let easyCount = 0;
        let mediumCount = 0;
        let hardCount = 0;

        questionsData.forEach(q => {
            if (q.g === selectedGrade && (selectedTopicId === 0 || q.t === selectedTopicId)) {
                if (q.d === 'EASY') easyCount++;
                else if (q.d === 'MEDIUM') mediumCount++;
                else if (q.d === 'HARD') hardCount++;
            }
        });

        availEasy.textContent = `Hiện có: ${easyCount}`;
        availMedium.textContent = `Hiện có: ${mediumCount}`;
        availHard.textContent = `Hiện có: ${hardCount}`;

        // Cập nhật thuộc tính max cho ô nhập để hạn chế bốc quá số câu hiện có
        countEasyInput.max = easyCount;
        countMediumInput.max = mediumCount;
        countHardInput.max = hardCount;
    }

    function toggleMode() {
        const isManual = modeManual.checked;
        manualSection.hidden = !isManual;
        autoSection.hidden = isManual;

        document.querySelectorAll('.question-checkbox').forEach(cb => cb.disabled = !isManual);
        autoTopic.disabled = isManual;
        autoInputs.forEach(input => input.disabled = isManual);

        updateQuestionList();
        updateTopicOptions();
        updateSelectedCount();
        updateAvailableCounts();
    }

    function updateQuestionList() {
        const grade = gradeSelect.value;
        const kw = questionSearch.value.trim().toLowerCase();

        document.querySelectorAll('.question-row').forEach(row => {
            const sameGrade = row.dataset.grade === grade;
            const matchSearch = row.dataset.search.includes(kw);
            row.hidden = !(sameGrade && matchSearch);
        });
    }

    function updateTopicOptions() {
        const grade = gradeSelect.value;
        Array.from(autoTopic.options).forEach(opt => {
            if (opt.value === "0") return;
            opt.hidden = opt.dataset.grade !== grade;
        });
        autoTopic.value = "0";
    }

    function updateSelectedCount() {
        const count = document.querySelectorAll('.question-checkbox:checked:not(:disabled)').length;
        selectedCount.textContent = count;
    }

    gradeSelect.addEventListener('change', () => {
        updateQuestionList();
        updateTopicOptions();
        updateAvailableCounts();
    });

    autoTopic.addEventListener('change', updateAvailableCounts);

    questionSearch.addEventListener('input', updateQuestionList);
    modeManual.addEventListener('change', toggleMode);
    modeAuto.addEventListener('change', toggleMode);

    document.querySelectorAll('.question-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    document.getElementById('examForm').addEventListener('submit', function(e) {
        // Kiểm tra chọn câu hỏi ở chế độ thủ công
        if (modeManual.checked && selectedCount.textContent === '0') {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất 1 câu hỏi.');
            return;
        }

        // Kiểm tra Logic thời gian mở/đóng đề thi
        if (startTimeInput.value && endTimeInput.value) {
            const startVal = new Date(startTimeInput.value);
            const endVal = new Date(endTimeInput.value);

            if (endVal <= startVal) {
                e.preventDefault();
                alert('⚠️ Thời gian đóng đề thi phải sau thời gian mở đề thi!');
                endTimeInput.focus();
            }
        }
    });

    toggleMode();
</script>

<?php teacher_layout_end(); ?>