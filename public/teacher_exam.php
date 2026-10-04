<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="page-container">

    <div class="page-header">

        <div>
            <h1>Tạo đề thi</h1>

            <p>
                Chọn câu hỏi và thiết lập cấu hình đề thi.
            </p>
        </div>

        <button class="btn-primary">
            ✓ Tạo đề thi
        </button>

    </div>


    <!-- =========================
         TAB
    ========================== -->

    <div class="tabs">

        <button class="tab active">
            Chọn câu hỏi
        </button>

        <button class="tab">
            Cài đặt ma trận đề
        </button>

    </div>


    <!-- =========================
         CHỌN CÂU HỎI
    ========================== -->

    <section class="form-card">

        <div class="section-header">

            <div>
                <h2>Chọn câu hỏi</h2>

                <p>
                    Tích chọn các câu hỏi muốn đưa vào đề.
                </p>
            </div>

            <input
                type="text"
                class="search-input"
                placeholder="🔍 Tìm câu hỏi..."
            >

        </div>


        <div class="question-list">


            <label class="question-item">

                <input type="checkbox">

                <div class="question-content">

                    <strong>
                        Câu 1.
                    </strong>

                    Chiến thắng Điện Biên Phủ diễn ra vào năm nào?

                    <div class="question-info">

                        <span class="badge badge-blue">
                            Lớp 9
                        </span>

                        <span class="badge badge-green">
                            Nhận biết
                        </span>

                    </div>

                </div>

            </label>


            <label class="question-item">

                <input type="checkbox">

                <div class="question-content">

                    <strong>
                        Câu 2.
                    </strong>

                    Ai là người đọc Tuyên ngôn Độc lập?

                    <div class="question-info">

                        <span class="badge badge-blue">
                            Lớp 9
                        </span>

                        <span class="badge badge-yellow">
                            Thông hiểu
                        </span>

                    </div>

                </div>

            </label>


            <label class="question-item">

                <input type="checkbox">

                <div class="question-content">

                    <strong>
                        Câu 3.
                    </strong>

                    Cách mạng tháng Tám diễn ra vào năm nào?

                    <div class="question-info">

                        <span class="badge badge-blue">
                            Lớp 8
                        </span>

                        <span class="badge badge-red">
                            Vận dụng
                        </span>

                    </div>

                </div>

            </label>


        </div>


        <div class="selected-count">

            Đã chọn:
            <strong>0</strong>
            câu hỏi

        </div>

    </section>


    <!-- =========================
         MA TRẬN ĐỀ
    ========================== -->

    <section class="form-card">

        <h2>Cài đặt ma trận đề</h2>

        <div class="settings-grid">


            <div class="form-group">

                <label>Tên đề thi</label>

                <input
                    type="text"
                    placeholder="Ví dụ: Kiểm tra Lịch sử 9"
                >

            </div>


            <div class="form-group">

                <label>Khối lớp</label>

                <select>

                    <option>Lớp 6</option>
                    <option>Lớp 7</option>
                    <option>Lớp 8</option>
                    <option>Lớp 9</option>

                </select>

            </div>


            <div class="form-group">

                <label>Số câu hỏi</label>

                <input
                    type="number"
                    value="20"
                    min="1"
                >

            </div>


            <div class="form-group">

                <label>Thời gian làm bài</label>

                <input
                    type="number"
                    value="30"
                    min="1"
                >

                <small>Phút</small>

            </div>

        </div>


        <h3 class="matrix-title">
            Ma trận mức độ
        </h3>


        <div class="matrix">

            <div>
                <label>Nhận biết</label>
                <input type="number" value="10">
            </div>

            <div>
                <label>Thông hiểu</label>
                <input type="number" value="6">
            </div>

            <div>
                <label>Vận dụng</label>
                <input type="number" value="3">
            </div>

            <div>
                <label>Vận dụng cao</label>
                <input type="number" value="1">
            </div>

        </div>


        <button class="btn-primary">
            Tạo đề thi
        </button>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>