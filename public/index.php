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

// Nạp header và chèn CSS trực tiếp vào <head>
$inlineCss = <<<'INLINE_CSS'
/* =========================================================
   TESTSUVIET.COM - GIAO DIỆN TRANG CHỦ
   ========================================================= */

@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,500;1,600&display=swap');

:root {
  --primary-blue: #2563eb;
  --primary-hover: #1d4ed8;
  --navy-dark: #0f2f5e;
  --navy-hero: #102b49;
  --accent-gold: #f5a623;
  --bg-main: #f7fbff;
  --text-main: #19385f;
  --text-muted: #637c9e;
  --border-color: #dce9f8;
  --grade-6: #2878e8;
  --grade-6-bg: #e5f0ff;
  --grade-7: #e87918;
  --grade-7-bg: #fff0df;
  --grade-8: #21966b;
  --grade-8-bg: #e4f5eb;
  --grade-9: #7939b6;
  --grade-9-bg: #f1e7fb;
  --shadow-sm: 0 3px 12px rgba(37, 99, 235, .055);
  --shadow-md: 0 8px 24px rgba(28, 74, 130, .09);
  --shadow-lg: 0 18px 42px rgba(8, 31, 58, .18);
}

* { margin: 0; padding: 0; box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
  font-family: 'Plus Jakarta Sans', Arial, sans-serif;
  background: var(--bg-main);
  color: var(--text-main);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}
a { color: inherit; text-decoration: none; transition: .25s ease; }
button, input, select { font: inherit; }
button, select { cursor: pointer; }
.container { width: 92%; max-width: 1430px; margin: 0 auto; }

/* =========================================================
   1. HEADER
   ========================================================= */
.header {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: rgba(255,255,255,.97);
  border-bottom: 1px solid #e7eef8;
  box-shadow: 0 3px 16px rgba(24, 61, 105, .045);
  backdrop-filter: blur(12px);
}
.header-container {
  width: 92%;
  max-width: 1430px;
  height: 78px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}
.logo {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
  color: #1454a0;
  font-size: 25px;
  line-height: 1.15;
  font-weight: 800;
  letter-spacing: -.8px;
}
.logo span { color: #ef8b22; }
.logo-tagline {
  display: block;
  margin-top: 4px;
  color: #526b8b;
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0;
}
.nav { display: flex; align-items: center; gap: 30px; }
.nav a {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  color: #18385f;
  font-size: 14px;
  font-weight: 700;
  white-space: nowrap;
  padding: 26px 0 23px;
  border-bottom: 3px solid transparent;
}
.nav a:hover, .nav a.active {
  color: var(--primary-blue);
  border-bottom-color: var(--primary-blue);
}
.header-actions { display: flex; align-items: center; gap: 14px; }
.header-search { position: relative; }
.header-search input {
  width: 225px;
  height: 41px;
  padding: 9px 17px 9px 40px;
  color: #25466d;
  border: 1px solid #dce7f5;
  border-radius: 24px;
  background: #f5f8fc;
  outline: none;
  font-size: 13px;
  transition: .25s ease;
}
.header-search input:focus {
  width: 255px;
  border-color: #8bb6f7;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(37,99,235,.08);
}
.btn-login { padding: 9px 12px; color: #18385f; font-size: 14px; font-weight: 700; white-space: nowrap; }
.btn-login:hover { color: var(--primary-blue); }
.btn-register {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 24px;
  min-height: 40px;
  border-radius: 22px;
  background: linear-gradient(135deg, #287bf0, #1d63d4);
  color: #fff !important;
  font-size: 14px;
  font-weight: 700;
  box-shadow: 0 5px 14px rgba(37,99,235,.2);
}
.btn-register:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(37,99,235,.25); }
.menu-toggle { display: none; }

/* =========================================================
   2. HERO / SLIDER
   ========================================================= */
.hero {
  width: 100vw;
  margin-left: calc(50% - 50vw);
  padding: 0;
}
.hero > .container { width: 100%; max-width: none; }
.slider {
  position: relative;
  width: 100%;
  height: 326px;
  min-height: 326px;
  overflow: hidden;
  border-radius: 0;
  background: linear-gradient(105deg, #122b40 0%, #25445e 55%, #e4b47a 100%);
  box-shadow: var(--shadow-lg);
}
.slide {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity .75s ease, visibility .75s ease;
}
.slide.active { opacity: 1; visibility: visible; pointer-events: auto; z-index: 1; }
.slide::after {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  background: linear-gradient(90deg, rgba(11,31,48,.96) 0%, rgba(15,39,59,.88) 30%, rgba(17,42,61,.18) 64%, rgba(17,42,61,0) 100%);
}
.slide img {
  position: absolute;
  inset: 0 0 0 auto;
  width: 76%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  opacity: .98;
  mask-image: linear-gradient(to right, transparent 0%, rgba(0,0,0,.55) 20%, #000 43%);
  -webkit-mask-image: linear-gradient(to right, transparent 0%, rgba(0,0,0,.55) 20%, #000 43%);
}
.slide-content {
  position: relative;
  z-index: 2;
  width: 59%;
  max-width: 760px;
  padding: 35px 0 42px 10.2vw;
  color: #fff;
}
.slide-content .subtitle {
  display: block;
  margin-bottom: 4px;
  color: #ffd05d;
  font-family: 'Playfair Display', Georgia, serif;
  font-size: 25px;
  font-style: italic;
}
.slide-content h2 {
  margin: 0 0 10px;
  color: #fff;
  background: none;
  -webkit-text-fill-color: #fff;
  font-size: clamp(35px, 3.8vw, 58px);
  font-weight: 800;
  line-height: 1.12;
  letter-spacing: -.7px;
  text-transform: uppercase;
  text-shadow: 0 2px 12px rgba(0,0,0,.12);
}
.slide-content p {
  max-width: 530px;
  margin: 0 0 20px;
  color: #f1f6fc;
  font-size: 17px;
  line-height: 1.55;
}
.quote-box {
  position: absolute;
  top: 42px;
  right: 8.2%;
  z-index: 3;
  max-width: 340px;
  color: #172c43;
  text-align: center;
}
.quote-box p {
  color: #172c43;
  font-family: 'Playfair Display', Georgia, serif;
  font-size: 17px;
  font-style: italic;
  font-weight: 600;
  line-height: 1.55;
}
.quote-box span { display: block; margin-top: 8px; color: #344c66; font-size: 13px; font-style: italic; }
.hero-buttons { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; }
.btn-hero-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 13px 24px;
  min-height: 52px;
  border-radius: 28px;
  color: #17365c;
  background: linear-gradient(135deg, #ffd45e, #ffc12e);
  box-shadow: 0 8px 19px rgba(233,164,28,.23);
  font-size: 15px;
  font-weight: 800;
}
.btn-hero-primary span { font-size: 23px; line-height: 1; }
.btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 11px 22px rgba(233,164,28,.32); }
.btn-hero-outline {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 21px;
  min-height: 48px;
  border: 1px solid rgba(255,255,255,.7);
  border-radius: 26px;
  background: rgba(255,255,255,.08);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  backdrop-filter: blur(8px);
}
.btn-hero-outline:hover { background: rgba(255,255,255,.18); }
.slider-prev, .slider-next {
  position: absolute;
  top: 50%;
  z-index: 5;
  width: 43px;
  height: 43px;
  transform: translateY(-50%);
  border: 1px solid rgba(255,255,255,.28);
  border-radius: 50%;
  background: rgba(11,30,48,.36);
  color: #fff;
  font-size: 17px;
  cursor: pointer;
  backdrop-filter: blur(6px);
  transition: .2s ease;
}
.slider-prev { left: 22px; }
.slider-next { right: 22px; }
.slider-prev:hover, .slider-next:hover { background: var(--primary-blue); }
.slider-dots {
  position: absolute;
  bottom: 14px;
  left: 50%;
  z-index: 6;
  display: flex;
  gap: 8px;
  transform: translateX(-50%);
}
.dot { width: 9px; height: 9px; border-radius: 20px; background: rgba(255,255,255,.58); cursor: pointer; transition: .25s ease; }
.dot.active { width: 25px; background: #ffc642; }

/* =========================================================
   3. SEARCH BAR
   ========================================================= */
.exam-search {
  position: relative;
  z-index: 10;
  margin: 22px 0 20px;
  padding: 14px;
  border: 1px solid #d9e9ff;
  border-radius: 17px;
  background: linear-gradient(115deg, #f0f7ff, #ffffff 55%, #f2f8ff);
  box-shadow: 0 5px 18px rgba(37,99,235,.055);
}
.exam-search-form { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.exam-search-input { position: relative; flex: 2 1 390px; min-width: 240px; }
.exam-search-input input {
  width: 100%;
  height: 47px;
  padding: 11px 17px 11px 48px;
  border: 1px solid #d8e5f5;
  border-radius: 11px;
  outline: none;
  background: #fff;
  color: var(--text-main);
  font-size: 14px;
  transition: .2s ease;
}
.exam-search-input input::placeholder { color: #8da1bb; }
.exam-search-input input:focus { border-color: #7eaff4; box-shadow: 0 0 0 3px rgba(37,99,235,.08); }
.search-icon {
  position: absolute;
  top: 5px;
  left: 15px;
  color: var(--primary-blue);
  font-size: 28px;
  line-height: 1.2;
}
.exam-grade-filter { position: relative; flex: 1 1 190px; min-width: 165px; }
.exam-grade-filter select {
  width: 100%;
  height: 47px;
  padding: 10px 35px 10px 42px;
  border: 1px solid #d8e5f5;
  border-radius: 11px;
  outline: none;
  appearance: none;
  background: #fff;
  color: #29496e;
  font-size: 13px;
}
.exam-grade-filter::after {
  content: '⌄';
  position: absolute;
  top: 8px;
  right: 14px;
  color: #29496e;
  font-size: 19px;
  pointer-events: none;
}
.filter-icon { position: absolute; top: 10px; left: 14px; color: #31547b; font-size: 17px; pointer-events: none; }
.exam-subject-label {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 47px;
  padding: 0 17px;
  border: 1px solid #d8e5f5;
  border-radius: 11px;
  background: #fff;
  color: #496582;
  font-size: 13px;
  white-space: nowrap;
}
.exam-subject-label strong { margin-left: 4px; color: #1e4778; }
.btn-search-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  min-height: 47px;
  padding: 10px 24px;
  border: 0;
  border-radius: 11px;
  background: linear-gradient(135deg, #2d7df1, #2167d7);
  color: #fff;
  box-shadow: 0 5px 12px rgba(37,99,235,.2);
  font-size: 14px;
  font-weight: 700;
  transition: .2s ease;
}
.btn-search-submit span { font-size: 22px; line-height: 1; }
.btn-search-submit:hover { transform: translateY(-1px); background: var(--primary-hover); }

/* =========================================================
   4. SECTION HEADING & GRADE CARDS
   ========================================================= */
.classes { padding: 0 0 22px; }
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin: 0 4px 15px;
}
.section-header h2 {
  display: flex;
  align-items: center;
  gap: 11px;
  color: #193f6d;
  font-size: 22px;
  line-height: 1.3;
  font-weight: 800;
}
.section-header p { margin-top: 2px; color: #627d9f; font-size: 13px; }
.section-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 31px;
  height: 31px;
  border-radius: 9px;
  background: #e8f1ff;
  color: #1556a1;
  font-size: 19px;
}
.section-more { color: var(--primary-blue); font-size: 13px; font-weight: 700; white-space: nowrap; }
.section-more span { margin-left: 5px; font-size: 18px; }
.section-more:hover { color: var(--primary-hover); }
.class-list {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 20px;
}
.class-card {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
  padding: 0;
  border: 1px solid var(--border-color);
  border-radius: 12px;
  background: #fff;
  box-shadow: var(--shadow-sm);
  transition: transform .25s ease, box-shadow .25s ease;
}
.class-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
.class-card.grade-6 { border-color: #c8ddff; background: linear-gradient(180deg, #f3f8ff, #fff); }
.class-card.grade-7 { border-color: #f3d7bd; background: linear-gradient(180deg, #fff8f0, #fff); }
.class-card.grade-8 { border-color: #c9e6d7; background: linear-gradient(180deg, #f2faf4, #fff); }
.class-card.grade-9 { border-color: #ddc9ed; background: linear-gradient(180deg, #faf5ff, #fff); }
.class-card-cover {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 113px;
  padding: 18px 22px;
  overflow: hidden;
  background-position: center 44%;
  background-size: cover;
}
.class-card-cover::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(255,255,255,.18), rgba(255,255,255,.04)); }
.class-card.grade-6 .class-card-cover::after { background-color: rgba(50,124,225,.13); }
.class-card.grade-7 .class-card-cover::after { background-color: rgba(226,125,35,.13); }
.class-card.grade-8 .class-card-cover::after { background-color: rgba(38,151,102,.14); }
.class-card.grade-9 .class-card-cover::after { background-color: rgba(132,77,178,.15); }
.class-badge {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 58px;
  height: 58px;
  margin: 0;
  border: 3px solid rgba(255,255,255,.78);
  border-radius: 50%;
  color: #fff;
  font-size: 29px;
  font-weight: 800;
  box-shadow: 0 4px 13px rgba(20,52,89,.16);
}
.class-card.grade-6 .class-badge { background: var(--grade-6); }
.class-card.grade-7 .class-badge { background: var(--grade-7); }
.class-card.grade-8 .class-badge { background: var(--grade-8); }
.class-card.grade-9 .class-badge { background: var(--grade-9); }
.class-cover-title {
  position: relative;
  z-index: 1;
  color: rgba(19,54,92,.7);
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 2px;
  text-shadow: 0 1px 2px rgba(255,255,255,.7);
}
.class-card-content { position: relative; z-index: 2; padding: 11px 20px 15px; }
.class-card h3 { margin-bottom: 3px; color: #173c68; font-size: 17px; font-weight: 800; }
.class-card p { min-height: 38px; margin-bottom: 9px; color: #617b9c; font-size: 12px; line-height: 1.5; }
.class-card .btn-link { display: inline-flex; align-items: center; gap: 9px; font-size: 12px; font-weight: 800; }
.class-card.grade-6 .btn-link { color: var(--grade-6); }
.class-card.grade-7 .btn-link { color: var(--grade-7); }
.class-card.grade-8 .btn-link { color: var(--grade-8); }
.class-card.grade-9 .btn-link { color: var(--grade-9); }
.class-card .btn-link span { font-size: 17px; transition: transform .2s ease; }
.class-card:hover .btn-link span { transform: translateX(4px); }

/* =========================================================
   5. EXAM RESULTS
   ========================================================= */
.exam-results { padding: 18px 0 30px; }
.exam-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 15px; }
.exam-card {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-width: 0;
  padding: 18px;
  border: 1px solid #e0eaf6;
  border-radius: 13px;
  background: #fff;
  box-shadow: var(--shadow-sm);
  transition: .22s ease;
}
.exam-card:hover { transform: translateY(-3px); border-color: #bfd7fa; box-shadow: var(--shadow-md); }
.exam-card-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
.exam-tag { display: inline-flex; width: fit-content; padding: 4px 10px; border-radius: 20px; background: #e9f2ff; color: #2163b6; font-size: 11px; font-weight: 800; }
.exam-subject { color: #7187a3; font-size: 11px; font-weight: 700; }
.exam-card h3 { min-height: 45px; margin-bottom: 12px; color: #1c3c63; font-size: 15px; font-weight: 800; line-height: 1.45; }
.exam-meta { display: flex; flex-wrap: wrap; gap: 13px; margin-bottom: 15px; color: #7185a0; font-size: 12px; }
.btn-start-exam, .slider-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  min-height: 39px;
  padding: 9px 13px;
  border-radius: 9px;
  background: #edf4ff;
  color: #2167c5;
  font-size: 13px;
  font-weight: 800;
  text-align: center;
  transition: .2s ease;
}
.btn-start-exam:hover, .slider-btn:hover { background: var(--primary-blue); color: #fff; }
.exam-empty {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30px 15px;
  border: 1px dashed #c9d9ec;
  border-radius: 13px;
  background: #fff;
  color: #617a99;
  text-align: center;
}
.exam-empty span { margin-bottom: 5px; font-size: 30px; }
.exam-empty strong { color: #264a73; font-size: 15px; }
.exam-empty p { margin-top: 3px; font-size: 12px; }

/* =========================================================
   6. FEATURES
   ========================================================= */
.features {
  margin: 4px 0 20px;
  padding: 18px 20px;
  border: 1px solid #d8e9ff;
  border-radius: 13px;
  background: linear-gradient(110deg, #f0f7ff, #fff 50%, #f2f8ff);
}
.feature-list { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; }
.feature-item {
  display: flex;
  align-items: center;
  gap: 13px;
  min-width: 0;
  padding: 2px 16px;
  border-right: 1px solid #d8e7f7;
}
.feature-item:last-child { border-right: 0; }
.feature-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 51px;
  width: 51px;
  height: 51px;
  border: 1px solid #2f83f5;
  border-radius: 50%;
  background: #fff;
  color: #2678e8;
  font-size: 29px;
  font-weight: 700;
}
.feature-icon-green { border-color: #21b786; color: #13a879; }
.feature-icon-orange { border-color: #f39a2d; color: #f18b1b; }
.feature-icon-purple { border-color: #a14ce0; color: #8b3cce; }
.feature-info h4 { margin-bottom: 3px; color: #1b4779; font-size: 13px; font-weight: 800; }
.feature-info p { color: #6c83a1; font-size: 11px; line-height: 1.55; }

/* =========================================================
   7. FOOTER
   ========================================================= */
footer {
  padding: 22px 0 16px;
  background: linear-gradient(110deg, #173653, #102943 70%, #173958);
  color: #dce8f5;
  font-size: 13px;
}
.footer-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding-bottom: 14px;
  margin-bottom: 12px;
  border-bottom: 1px solid rgba(255,255,255,.13);
}
.footer-brand .logo { color: #fff; font-size: 18px; }
.footer-brand .logo-tagline { color: #d5e0ed; }
.footer-quote { color: #e6edf7; font-family: 'Playfair Display', Georgia, serif; font-style: italic; text-align: center; }
.footer-bottom { color: #c3d2e3; font-size: 11px; text-align: center; }

/* =========================================================
   8. RESPONSIVE
   ========================================================= */
@media (max-width: 1100px) {
  .header-container { gap: 15px; }
  .nav { gap: 17px; }
  .header-search input { width: 175px; }
  .header-search input:focus { width: 205px; }
  .class-list { gap: 13px; }
  .class-card-content { padding-right: 15px; padding-left: 15px; }
  .feature-item { padding: 2px 8px; }
}

@media (max-width: 900px) {
  .header-container { height: 70px; }
  .logo { font-size: 21px; }
  .nav { gap: 12px; }
  .nav a { font-size: 12px; }
  .header-actions { gap: 7px; }
  .header-search { display: none; }
  .slide-content { width: 70%; padding-left: 8vw; }
  .slide-content h2 { font-size: 39px; }
  .quote-box { right: 5%; max-width: 250px; }
  .class-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .exam-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .feature-list { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 16px; }
  .feature-item:nth-child(2) { border-right: 0; }
}

@media (max-width: 650px) {
  .container { width: 92%; }
  .header-container { height: 65px; }
  .logo { font-size: 18px; gap: 6px; }
  .logo-tagline { font-size: 9px; }
  .nav { display: none; }
  .nav.show { position: absolute; top: 65px; left: 0; right: 0; display: flex; flex-direction: column; align-items: stretch; gap: 0; padding: 8px 5%; border-top: 1px solid #e7eef8; background: #fff; box-shadow: var(--shadow-md); }
  .nav.show a { padding: 12px 4px; border-bottom: 1px solid #eef3fa; }
  .menu-toggle { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border: 1px solid #dce8f7; border-radius: 9px; background: #f5f9ff; color: #1d5eae; font-size: 21px; }
  .header-actions { gap: 5px; }
  .btn-login { padding: 7px 8px; font-size: 12px; }
  .btn-register { min-height: 35px; padding: 7px 13px; font-size: 12px; }
  .slider { height: 360px; min-height: 360px; }
  .slide img { width: 100%; opacity: .48; mask-image: none; -webkit-mask-image: none; }
  .slide::after { background: linear-gradient(90deg, rgba(10,30,49,.91), rgba(10,30,49,.58)); }
  .slide-content { width: 100%; max-width: none; padding: 42px 9% 55px; }
  .slide-content .subtitle { font-size: 19px; }
  .slide-content h2 { font-size: 31px; }
  .slide-content p { max-width: 100%; font-size: 14px; }
  .quote-box { display: none; }
  .btn-hero-primary, .btn-hero-outline { min-height: 43px; padding: 9px 14px; font-size: 12px; }
  .slider-prev, .slider-next { width: 33px; height: 33px; font-size: 13px; }
  .slider-prev { left: 8px; }
  .slider-next { right: 8px; }
  .exam-search { margin: 14px 0 18px; padding: 11px; }
  .exam-search-form { flex-direction: column; align-items: stretch; }
  .exam-search-input, .exam-grade-filter { min-width: 100%; }
  .exam-subject-label { justify-content: flex-start; }
  .btn-search-submit { width: 100%; }
  .section-header h2 { font-size: 18px; }
  .section-header p { font-size: 11px; }
  .section-more { font-size: 11px; }
  .class-list, .exam-list { grid-template-columns: 1fr; }
  .class-card-cover { height: 105px; }
  .class-card p { min-height: 0; }
  .exam-results { padding-top: 12px; }
  .feature-list { grid-template-columns: 1fr; gap: 13px; }
  .feature-item { padding: 3px 0 12px; border-right: 0; border-bottom: 1px solid #d8e7f7; }
  .feature-item:last-child { border-bottom: 0; padding-bottom: 0; }
  .features { padding: 15px; }
  .footer-content { flex-direction: column; text-align: center; }
}

INLINE_CSS;

ob_start();
include $headerPath;
$headerHtml = ob_get_clean();

// Loại bỏ liên kết style.css cũ để không xung đột CSS
$headerHtml = preg_replace(
    '~<link\b(?=[^>]*\bhref\s*=\s*["\'][^"\']*style\.css(?:\?[^"\']*)?["\'])[^>]*>\s*~i',
    '',
    $headerHtml
);

$styleTag = "<style>\n" . $inlineCss . "\n</style>\n";
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
    $sql = "SELECT exam_id, title, grade, duration, total_questions FROM exam WHERE 1=1";
    $params = [];

    if (!empty($keyword)) {
        $sql .= " AND title LIKE :keyword";
        $params['keyword'] = '%' . $keyword . '%';
    }

    if (!empty($grade)) {
        $sql .= " AND grade = :grade";
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