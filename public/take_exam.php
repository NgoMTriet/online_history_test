<?php
require_once __DIR__ . '/../api/be_take_exam.php';

if ($gate_state === 'not_started'):
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chưa đến giờ làm bài</title>
</head>
<body>
    <div class="card">
        <h2>⏳ Chưa đến thời gian làm bài!</h2>
        <p>
            Đề thi
            <strong><?= htmlspecialchars($exam['title'] ?? 'Bài thi', ENT_QUOTES, 'UTF-8') ?></strong>
            sẽ mở lúc
            <strong><?= htmlspecialchars($formattedStartTime, ENT_QUOTES, 'UTF-8') ?></strong>.
        </p>
        <a href="index.php" class="btn">Quay lại trang chủ</a>
    </div>
</body>
</html>
<?php
exit;
endif;

if ($gate_state === 'max_attempts'):
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hết lượt làm bài</title>
</head>
<body>
    <div class="card">
        <h2>🚫 Bạn đã hết lượt làm bài!</h2>
        <p>
            Đề thi <strong><?= $safeTitle ?></strong>
            cho phép làm tối đa <strong><?= $max_attempts ?></strong> lần.
            <br>
            Bạn đã thực hiện
            <strong><?= $user_attempts ?>/<?= $max_attempts ?></strong> lượt.
        </p>
        <a href="index.php" class="btn">Quay lại trang chủ</a>
    </div>
</body>
</html>
<?php
exit;
endif;

if ($gate_state === 'password_required'):
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yêu cầu mật khẩu bài thi</title>
</head>
<body>
    <div class="card">
        <h2>🔒 Đề thi có mật khẩu</h2>
        <p>
            Nhập mật khẩu để làm bài
            <strong><?= htmlspecialchars($exam['title'] ?? 'Bài thi', ENT_QUOTES, 'UTF-8') ?></strong>.
        </p>

        <?php if ($password_error !== ''): ?>
            <div class="error-msg">
                <?= htmlspecialchars($password_error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input
                type="password"
                name="input_password"
                class="form-input"
                placeholder="Nhập mật khẩu đề thi"
                required
                autofocus
            >
            <button
                type="submit"
                name="submit_exam_password"
                class="btn-submit"
            >
                Xác nhận làm bài
            </button>
        </form>

        <p><a href="index.php">Quay lại trang chủ</a></p>
    </div>
</body>
</html>
<?php
exit;
endif;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($exam['title'] ?? 'Làm bài thi', ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="/../assets/css/take_exam.css">
</head>
<body class="fs-locked">

<div id="fs-gate" class="show" role="dialog" aria-modal="true" aria-labelledby="fs-gate-title">
    <div class="fs-gate-card">
        <h2 id="fs-gate-title">Bài thi yêu cầu toàn màn hình</h2>
        <p>
            Bấm nút bên dưới để vào chế độ toàn màn hình và bắt đầu làm bài.
            Việc thoát toàn màn hình hoặc chuyển sang tab/cửa sổ khác sẽ được
            hệ thống ghi lại và giáo viên có thể xem.
        </p>
        <p id="fs-gate-warn" class="fs-gate-warn" role="alert"></p>
        <button type="button" id="fs-start-btn">Vào toàn màn hình &amp; làm bài</button>
    </div>
</div>

<header>
    <div class="exam-title">
        <?= htmlspecialchars($exam['title'] ?? 'Bài thi', ENT_QUOTES, 'UTF-8') ?>
    </div>

    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <div class="timer-box">
            ⏱️ Thời gian còn lại:
            <span id="timer-display">00:00</span>
        </div>

        <a href="index.php" id="btn-exit" class="btn-exit-header">
            <span aria-hidden="true">↩</span> Thoát
        </a>
    </div>
</header>

<div class="container">
<form id="exam-form" method="POST" action="result.php">

    <input type="hidden" name="action" value="submit_exam">
    <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
    <input type="hidden" name="completion_time" id="completion_time_input" value="0">
    <input type="hidden" name="tab_switches" id="tab_switches_input" value="0">

    <div class="exam-layout">

        <!-- Khu vực hiển thị câu hỏi -->
        <main class="exam-main">

            <?php foreach ($questions_for_client as $index => $q): ?>

            <section class="question-card" id="question-<?= $index ?>">

                <div class="question-title">
                    Câu <?= $index + 1 ?>:
                    <?= htmlspecialchars($q['question'], ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div class="options-list">

                    <?php foreach ($q['options'] as $option): ?>

                    <label class="option-item">
                        <input
                            type="radio"
                            name="answers[<?= (int) $q['id'] ?>]"
                            value="<?= (int) $option['id'] ?>"
                            style="margin-right:10px"
                        >
                        <strong>
                            <?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?>.
                        </strong>
                        &nbsp;
                        <?= htmlspecialchars($option['content'], ENT_QUOTES, 'UTF-8') ?>
                    </label>

                    <?php endforeach; ?>

                </div>

            </section>

            <?php endforeach; ?>

        </main>

        <!-- Thanh danh sách câu hỏi -->
        <aside class="question-sidebar">

            <div class="question-navigation">

                <h3>Danh sách câu hỏi</h3>

                <div class="question-grid">

                    <?php foreach ($questions_for_client as $index => $q): ?>

                    <button
                        type="button"
                        class="question-number"
                        data-question="<?= $index ?>"
                        aria-label="Đi đến câu <?= $index + 1 ?>"
                    >
                        <?= $index + 1 ?>
                    </button>

                    <?php endforeach; ?>

                </div>

                <div class="question-progress" id="question-progress">
                    Đã làm 0/<?= count($questions_for_client) ?> câu
                </div>

                <!-- Chú thích trạng thái câu hỏi -->
                <div class="question-legend">
                    <span>
                        <i class="legend-box" style="background:#fee2e2"></i>
                        Chưa làm
                    </span>
                    <span>
                        <i class="legend-box" style="background:#dcfce7"></i>
                        Đã làm
                    </span>
                    <span>
                        <i class="legend-box" style="background:#dbeafe"></i>
                        Đang xem
                    </span>
                </div>

                <!-- Nút nộp bài nằm trong thanh danh sách -->
                <div class="submit-exam-container">
                    <button type="button" id="submit-exam-btn" class="submit-exam-btn">
                        Nộp bài
                    </button>
                </div>

            </div>

        </aside>

    </div>

</form>
</div>

<!-- Bảng xác nhận thoát bài thi -->
<div class="exit-modal" id="exitModal" role="dialog" aria-modal="true" aria-labelledby="exitModalTitle" aria-hidden="true">
    <div class="exit-modal-content">
        <div class="exit-modal-icon" aria-hidden="true">⚠️</div>
        <h2 id="exitModalTitle">Xác nhận thoát bài thi</h2>
        <p>Bạn có chắc chắn muốn thoát khỏi bài thi không?</p>
        <p class="exit-warning">
            Lượt làm bài này sẽ được tính và bài làm chưa nộp sẽ không được chấm điểm.
        </p>
        <div class="exit-modal-actions">
            <button type="button" class="btn-stay" id="btnStay">Ở lại làm bài</button>
            <button type="button" class="btn-confirm-exit" id="btnConfirmExit">Thoát bài thi</button>
        </div>
    </div>
</div>

<!-- Bảng xác nhận nộp bài -->
<div class="submit-modal" id="submitModal" role="dialog" aria-modal="true" aria-labelledby="submitModalTitle" aria-hidden="true">
    <div class="submit-modal-content">
        <div class="submit-modal-icon">✓</div>
        <h2 id="submitModalTitle">Xác nhận nộp bài</h2>
        <p>Bạn có chắc chắn muốn nộp bài thi không?</p>
        <p>Sau khi nộp bài, bạn sẽ không thể thay đổi câu trả lời.</p>
        <div class="submit-modal-actions">
            <button type="button" class="btn-cancel-submit" id="btnCancelSubmit">Tiếp tục làm bài</button>
            <button type="button" class="btn-confirm-submit" id="btnConfirmSubmit">Xác nhận nộp bài</button>
        </div>
    </div>
</div>

<!-- Bảng thông báo câu chưa hoàn thành -->
<div id="unansweredModal" class="submit-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="submit-modal-content">
        <div class="submit-modal-icon" style="background:#fef3c7;color:#d97706;">!</div>
        <h2>Bạn chưa hoàn thành bài thi!</h2>
        <p id="unansweredMessage">Bạn vẫn còn câu hỏi chưa được trả lời.</p>
        <div class="submit-modal-actions">
            <button type="button" id="btnCloseUnanswered" class="btn-cancel-submit">Tiếp tục làm bài</button>
        </div>
    </div>
</div>

<script>
let totalSeconds = <?= (int) $remaining_seconds ?>;
const answersStorageKey = 'exam_answers_<?= (int) $attempt_id ?>';

const timerDisplay = document.getElementById('timer-display');
const examForm = document.getElementById('exam-form');
const completionTimeInput = document.getElementById('completion_time_input');

const btnExit = document.getElementById('btn-exit');
const exitModal = document.getElementById('exitModal');
const btnStay = document.getElementById('btnStay');
const btnConfirmExit = document.getElementById('btnConfirmExit');

const submitBtn = document.getElementById('submit-exam-btn');
const submitModal = document.getElementById('submitModal');
const btnCancelSubmit = document.getElementById('btnCancelSubmit');
const btnConfirmSubmit = document.getElementById('btnConfirmSubmit');

const questionButtons = document.querySelectorAll('.question-number');
const questionCards = document.querySelectorAll('.question-card');
const questionProgress = document.getElementById('question-progress');

const startTime = Date.now();
let currentQuestion = 0;
let isSubmitting = false;
let timerInterval = null;

window.addEventListener('pageshow', function(event) {
    const navigation = performance.getEntriesByType('navigation')[0];
    if (event.persisted || (navigation && navigation.type === 'back_forward')) {
        window.location.reload();
    }
});

function calculateTimeSpent() {
    completionTimeInput.value = Math.round((Date.now() - startTime) / 1000);
}

function getUnansweredQuestions() {
    const unanswered = [];
    questionCards.forEach((card, index) => {
        const selected = card.querySelector('input[type="radio"]:checked');
        if (!selected) {
            unanswered.push(index);
        }
    });
    return unanswered;
}

function updateQuestionNavigation() {
    let answeredCount = 0;

    questionButtons.forEach((button, index) => {
        const card = questionCards[index];
        const selected = card ? card.querySelector('input[type="radio"]:checked') : null;

        if (selected) {
            answeredCount++;
        }

        button.classList.toggle('answered', !!selected);
        button.classList.toggle('current', index === currentQuestion);
        button.setAttribute('aria-label', `Câu ${index + 1}: ${selected ? 'đã làm' : 'chưa làm'}`);
    });

    questionProgress.textContent = `Đã làm ${answeredCount}/${questionCards.length} câu`;
}

questionButtons.forEach((button, index) => {
    button.addEventListener('click', function() {
        currentQuestion = index;
        questionCards[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
        updateQuestionNavigation();
    });
});

document.querySelectorAll('#exam-form input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const card = radio.closest('.question-card');
        if (card) {
            card.classList.remove('missing-answer');
        }
        updateQuestionNavigation();
        saveAnswers();
    });
});

function saveAnswers() {
    const saved = {};
    document.querySelectorAll('#exam-form input[type="radio"]:checked').forEach(radio => {
        saved[radio.name] = radio.value;
    });
    try {
        localStorage.setItem(answersStorageKey, JSON.stringify(saved));
    } catch (error) {}
}

function clearSavedAnswers() {
    try {
        localStorage.removeItem(answersStorageKey);
    } catch (error) {}
}

(function restoreAnswers() {
    try {
        const saved = JSON.parse(localStorage.getItem(answersStorageKey) || '{}');
        Object.keys(saved).forEach(name => {
            const radio = document.querySelector(
                '#exam-form input[type="radio"][name="' +
                CSS.escape(name) + '"][value="' +
                CSS.escape(String(saved[name])) + '"]'
            );
            if (radio) {
                radio.checked = true;
            }
        });
    } catch (error) {}
    updateQuestionNavigation();
})();

function validateAllAnswers() {
    const unanswered = getUnansweredQuestions();
    if (unanswered.length === 0) {
        return true;
    }

    const questionNumbers = unanswered.map(index => index + 1);
    document.getElementById('unansweredMessage').textContent =
        `Bạn vẫn còn ${unanswered.length} câu hỏi chưa được trả lời: ` +
        `Câu ${questionNumbers.join(', ')}. ` +
        `Vui lòng hoàn thành các câu hỏi còn thiếu trước khi nộp bài.`;

    document.getElementById('unansweredModal').classList.add('show');
    unanswered.forEach(index => {
        questionButtons[index].classList.add('unanswered-warning');
    });

    return false;
}

function closeSubmitModal() {
    submitModal.classList.remove('show');
    submitModal.setAttribute('aria-hidden', 'true');
}

function openSubmitModal() {
    submitModal.classList.add('show');
    submitModal.setAttribute('aria-hidden', 'false');
    btnCancelSubmit.focus();
}

function submitExam(forceSubmit = false) {
    if (isSubmitting) {
        return;
    }

    if (!forceSubmit && !validateAllAnswers()) {
        return;
    }

    isSubmitting = true;
    calculateTimeSpent();

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang nộp bài...';
    }

    if (btnConfirmSubmit) {
        btnConfirmSubmit.disabled = true;
        btnConfirmSubmit.textContent = 'Đang nộp bài...';
    }

    fetch('result.php', {
        method: 'POST',
        body: new FormData(examForm)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Máy chủ trả về lỗi HTTP ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.redirect_url) {
            clearSavedAnswers();
            window.location.replace(data.redirect_url);
        } else {
            alert(data.message || 'Có lỗi xảy ra khi nộp bài.');
            isSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.textContent = 'Nộp bài';
            btnConfirmSubmit.disabled = false;
            btnConfirmSubmit.textContent = 'Xác nhận nộp bài';
        }
    })
    .catch(error => {
        console.error(error);
        alert('Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại và thử nộp bài.');
        isSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.textContent = 'Nộp bài';
        btnConfirmSubmit.disabled = false;
        btnConfirmSubmit.textContent = 'Xác nhận nộp bài';
    });
}

submitBtn.addEventListener('click', function() {
    if (isSubmitting) {
        return;
    }
    if (!validateAllAnswers()) {
        return;
    }
    openSubmitModal();
});

const unansweredModal = document.getElementById('unansweredModal');
const btnCloseUnanswered = document.getElementById('btnCloseUnanswered');

btnCloseUnanswered.addEventListener('click', function(event) {
    event.preventDefault();
    unansweredModal.classList.remove('show');
    unansweredModal.setAttribute('aria-hidden', 'true');
    questionButtons.forEach(button => {
        button.classList.remove('unanswered-warning');
    });
    isSubmitting = false;
});

btnCancelSubmit.addEventListener('click', function() {
    closeSubmitModal();
    submitBtn.focus();
});

btnConfirmSubmit.addEventListener('click', function() {
    if (isSubmitting) {
        return;
    }
    closeSubmitModal();
    submitExam(false);
});

submitModal.addEventListener('click', function(event) {
    if (event.target === submitModal) {
        closeSubmitModal();
        submitBtn.focus();
    }
});

btnExit.addEventListener('click', function(event) {
    event.preventDefault();
    if (isSubmitting) {
        return;
    }
    exitModal.classList.add('show');
    exitModal.setAttribute('aria-hidden', 'false');
    btnStay.focus();
});

function closeExitModal() {
    exitModal.classList.remove('show');
    exitModal.setAttribute('aria-hidden', 'true');
    btnExit.focus();
}

btnStay.addEventListener('click', closeExitModal);

btnConfirmExit.addEventListener('click', function() {
    const exitData = new FormData();
    exitData.append('action', 'abandon_exam');
    exitData.append('exam_id', examForm.elements['exam_id'].value);
    exitData.append('tab_switches', tabSwitches);

    examLeaving = true;
    btnConfirmExit.disabled = true;

    fetch('result.php', {
        method: 'POST',
        body: exitData,
        keepalive: true
    })
    .catch(() => {})
    .finally(() => {
        clearSavedAnswers();
        window.location.href = btnExit.href;
    });
});

exitModal.addEventListener('click', function(event) {
    if (event.target === exitModal) {
        closeExitModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        if (submitModal.classList.contains('show')) {
            closeSubmitModal();
            submitBtn.focus();
        } else if (exitModal.classList.contains('show')) {
            closeExitModal();
        }
    }
});

// ===== Toàn màn hình bắt buộc + đếm số lần rời khỏi bài thi =====
const fsGate = document.getElementById('fs-gate');
const fsGateWarn = document.getElementById('fs-gate-warn');
const fsStartBtn = document.getElementById('fs-start-btn');
const tabSwitchesInput = document.getElementById('tab_switches_input');

const switchesStorageKey = 'exam_switches_<?= (int) $attempt_id ?>';
const fullscreenSupported = !!document.documentElement.requestFullscreen;

let tabSwitches = 0;
let lastLeaveAt = 0;
let examLeaving = false;

try {
    tabSwitches = parseInt(localStorage.getItem(switchesStorageKey) || '0', 10) || 0;
} catch (e) {}

tabSwitchesInput.value = tabSwitches;

function showFsGate() {
    if (tabSwitches > 0) {
        fsGateWarn.textContent =
            'Bạn đã rời khỏi chế độ làm bài ' + tabSwitches +
            ' lần. Mỗi lần đều được hệ thống ghi lại.';
        fsGateWarn.classList.add('show');
    }
    fsGate.classList.add('show');
    document.body.classList.add('fs-locked');
    fsStartBtn.focus();
}

function hideFsGate() {
    fsGate.classList.remove('show');
    document.body.classList.remove('fs-locked');
}

function recordLeave() {
    if (isSubmitting || examLeaving) {
        return;
    }
    const now = Date.now();
    if (now - lastLeaveAt < 1500) {
        return;
    }
    lastLeaveAt = now;
    tabSwitches++;
    tabSwitchesInput.value = tabSwitches;
    try {
        localStorage.setItem(switchesStorageKey, String(tabSwitches));
    } catch (e) {}
}

fsStartBtn.addEventListener('click', function() {
    document.documentElement.requestFullscreen()
        .then(hideFsGate)
        .catch(function() {
            alert('Không thể bật chế độ toàn màn hình. Vui lòng cho phép toàn màn hình trong trình duyệt.');
        });
});

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        recordLeave();
    } else if (fullscreenSupported && !document.fullscreenElement) {
        if (!isSubmitting && !examLeaving) {
            showFsGate();
        }
    }
});

document.addEventListener('fullscreenchange', function() {
    if (!document.fullscreenElement && !isSubmitting && !examLeaving) {
        recordLeave();
        showFsGate();
    }
});

if (!fullscreenSupported) {
    hideFsGate();
} else {
    showFsGate();
}

const examDeadline = Date.now() + (totalSeconds * 1000);
let timeExpiredHandled = false;

function updateTimer() {
    const remainingMilliseconds = Math.max(0, examDeadline - Date.now());
    totalSeconds = Math.ceil(remainingMilliseconds / 1000);

    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    timerDisplay.textContent =
        String(minutes).padStart(2, '0') + ':' +
        String(seconds).padStart(2, '0');

    if (totalSeconds <= 0 && !timeExpiredHandled) {
        timeExpiredHandled = true;
        clearInterval(timerInterval);
        alert('Đã hết giờ làm bài! Hệ thống sẽ tự động nộp bài với các đáp án hiện có.');
        closeSubmitModal();
        submitExam(true);
    }
}

updateQuestionNavigation();
updateTimer();
timerInterval = setInterval(updateTimer, 250);
</script>

</body>
</html>