<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="page-container">

    <div class="page-header">
        <div>
            <h1>Ngân hàng câu hỏi</h1>
            <p>Quản lý và thêm câu hỏi trắc nghiệm Lịch sử.</p>
        </div>
    </div>

    <!-- =========================
         NHẬP CÂU HỎI
    ========================== -->

    <section class="form-card">

        <h2>Thêm câu hỏi</h2>

        <form action="#" method="POST">

            <div class="form-group">
                <label>Nội dung câu hỏi</label>

                <textarea
                    name="question"
                    rows="4"
                    placeholder="Nhập nội dung câu hỏi..."
                    required
                ></textarea>
            </div>


            <div class="answers-grid">

                <div class="form-group">
                    <label>Đáp án A</label>

                    <input
                        type="text"
                        name="answer_a"
                        placeholder="Nhập đáp án A"
                        required
                    >
                </div>


                <div class="form-group">
                    <label>Đáp án B</label>

                    <input
                        type="text"
                        name="answer_b"
                        placeholder="Nhập đáp án B"
                        required
                    >
                </div>


                <div class="form-group">
                    <label>Đáp án C</label>

                    <input
                        type="text"
                        name="answer_c"
                        placeholder="Nhập đáp án C"
                        required
                    >
                </div>


                <div class="form-group">
                    <label>Đáp án D</label>

                    <input
                        type="text"
                        name="answer_d"
                        placeholder="Nhập đáp án D"
                        required
                    >
                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>Đáp án đúng</label>

                    <select name="correct_answer">

                        <option value="">-- Chọn đáp án --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>

                    </select>

                </div>


                <div class="checkbox-group">

                    <input
                        type="checkbox"
                        id="is_fixed"
                        name="is_fixed"
                    >

                    <label for="is_fixed">
                        is_fixed
                    </label>

                </div>

            </div>


            <button type="submit" class="btn-primary">
                + Thêm câu hỏi
            </button>

        </form>

    </section>


    <!-- =========================
         IMPORT EXCEL
    ========================== -->

    <section class="form-card">

        <h2>Import câu hỏi từ Excel</h2>

        <p class="description">
            Bạn có thể tải file Excel mẫu, nhập dữ liệu rồi upload lại hệ thống.
        </p>


        <div class="excel-actions">

            <a href="#" class="btn-secondary">
                ↓ Tải file Excel mẫu
            </a>


            <form action="#" method="POST" enctype="multipart/form-data">

                <label class="file-upload">

                    <input
                        type="file"
                        name="excel_file"
                        accept=".xlsx,.xls"
                    >

                    <span>Chọn file Excel</span>

                </label>


                <button type="submit" class="btn-success">
                    ↑ Upload file Excel
                </button>

            </form>

        </div>

    </section>


    <!-- =========================
         DANH SÁCH CÂU HỎI
    ========================== -->

    <section class="table-card">

        <div class="table-header">

            <div>
                <h2>Danh sách câu hỏi</h2>
                <p>Danh sách câu hỏi trong ngân hàng.</p>
            </div>

            <input
                type="text"
                class="search-input"
                placeholder="🔍 Tìm câu hỏi..."
            >

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Câu hỏi</th>
                        <th>Đáp án đúng</th>
                        <th>Fixed</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>


                <tbody>

                    <tr>

                        <td>1</td>

                        <td>
                            Chiến thắng Điện Biên Phủ diễn ra năm nào?
                        </td>

                        <td>
                            <span class="badge badge-green">A</span>
                        </td>

                        <td>
                            <span class="badge badge-blue">
                                Có
                            </span>
                        </td>

                        <td>

                            <button class="btn-small btn-edit">
                                Sửa
                            </button>

                            <button class="btn-small btn-delete">
                                Xóa
                            </button>

                        </td>

                    </tr>


                    <tr>

                        <td>2</td>

                        <td>
                            Ai là người đọc Tuyên ngôn Độc lập?
                        </td>

                        <td>
                            <span class="badge badge-green">B</span>
                        </td>

                        <td>
                            <span class="badge badge-gray">
                                Không
                            </span>
                        </td>

                        <td>

                            <button class="btn-small btn-edit">
                                Sửa
                            </button>

                            <button class="btn-small btn-delete">
                                Xóa
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>