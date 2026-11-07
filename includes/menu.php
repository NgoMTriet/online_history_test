<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$display_name =$_SESSION['full_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'Người dùng';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
/* ==========================================================================
   Thanh điều hướng chung (TestSuViet - Định dạng chuẩn theo profile.css)
   ========================================================================== */
.st-topbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: var(--pf-surface, #ffffff);
  border-bottom: 1px solid var(--pf-line, #e3e6ee);
  width: 100%;
}

.st-topbar-inner {
  max-width: 1280px; /* Đã kéo dài rộng ra 1280px đồng bộ toàn trang */
  margin: 0 auto;
  padding: 12px 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.st-left {
  display: flex;
  align-items: center;
  gap: 32px;
  flex-wrap: wrap;
}

.st-brand {
  font-family: var(--pf-serif, "Noto Serif", Georgia, serif);
  font-weight: 700;
  font-size: 1.3rem;
  color: var(--pf-indigo, #232f6b);
  text-decoration: none;
  letter-spacing: 0.2px;
}

.st-nav {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.st-nav a {
  position: relative;
  padding: 8px 14px;
  border-radius: var(--pf-radius-sm, 9px);
  color: var(--pf-muted, #5f6678);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.95rem;
  transition: all 0.15s ease;
}

.st-nav a:hover {
  background: var(--pf-bg, #f4f5f9);
  color: var(--pf-ink, #1c2233);
}

.st-nav a.active {
  color: var(--pf-indigo, #232f6b);
  background: var(--pf-indigo-soft, #e9ecf8);
  font-weight: 600;
}

.st-user {
  display: flex;
  align-items: center;
  gap: 16px;
  color: var(--pf-muted, #5f6678);
  font-size: 0.95rem;
}

.st-user strong {
  color: var(--pf-ink, #1c2233);
}

.st-user a {
  color: var(--pf-seal, #b3261e);
  font-weight: 600;
  text-decoration: none;
}

.st-user a:hover {
  text-decoration: underline;
}

@media (max-width: 760px) {
  .st-topbar-inner {
    padding: 10px 16px;
  }
}
</style>

<header class="st-topbar">
    <div class="st-topbar-inner">
        <div class="st-left">
            <a class="st-brand" href="index.php">SuViet</a>

            <nav class="st-nav" aria-label="Menu chính">
                <a href="index.php" class="<?= ($current_page === 'index.php') ? 'active' : '' ?>">Trang chủ</a>
                <a href="history.php" class="<?= ($current_page === 'history.php') ? 'active' : '' ?>">Lịch sử làm bài</a>
                <a href="about.php" class="<?= ($current_page === 'about.php') ? 'active' : '' ?>">Giới thiệu</a>
                <a href="profile.php" class="<?= ($current_page === 'profile.php') ? 'active' : '' ?>">Hồ sơ cá nhân</a>
            </nav>
        </div>

        <div class="st-user">
            <span><strong><?= htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') ?></strong></span>
            <a href="../api/auth.php?action=logout">Đăng xuất</a>
        </div>
    </div>
</header>