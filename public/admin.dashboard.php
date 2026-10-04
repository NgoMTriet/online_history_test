<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="page-container">

    <div class="page-header">

        <div>

            <h1>Dashboard Admin</h1>

            <p>
                Quản lý tài khoản và hệ thống.
            </p>

        </div>

    </div>


    <!-- =========================
         THỐNG KÊ
    ========================== -->

    <section class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon">
                👥
            </div>

            <div>

                <p>Tổng tài khoản</p>

                <h2>125</h2>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⏳
            </div>

            <div>

                <p>Chờ duyệt</p>

                <h2>12</h2>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🔒
            </div>

            <div>

                <p>Tài khoản bị khóa</p>

                <h2>5</h2>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                📝
            </div>

            <div>

                <p>Tổng đề thi</p>

                <h2>48</h2>

            </div>

        </div>


    </section>


    <!-- =========================
         DANH SÁCH TÀI KHOẢN
    ========================== -->

    <section class="table-card">

        <div class="table-header">

            <div>

                <h2>Quản lý tài khoản</h2>

                <p>
                    Danh sách tài khoản trong hệ thống.
                </p>

            </div>


            <input
                type="text"
                class="search-input"
                placeholder="🔍 Tìm tài khoản..."
            >

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Họ tên</th>

                        <th>Email</th>

                        <th>Vai trò</th>

                        <th>Trạng thái</th>

                        <th>Thao tác</th>

                    </tr>

                </thead>


                <tbody>


                    <tr>

                        <td>1</td>

                        <td>
                            Nguyễn Văn A
                        </td>

                        <td>
                            nguyenvana@gmail.com
                        </td>

                        <td>

                            <span class="badge badge-blue">
                                Teacher
                            </span>

                        </td>

                        <td>

                            <span class="badge badge-green">
                                Hoạt động
                            </span>

                        </td>

                        <td class="actions">

                            <button class="btn-small btn-success">
                                Duyệt
                            </button>

                            <button class="btn-small btn-warning">
                                Khóa
                            </button>

                            <button class="btn-small btn-secondary">
                                Đổi quyền
                            </button>

                        </td>

                    </tr>


                    <tr>

                        <td>2</td>

                        <td>
                            Trần Thị B
                        </td>

                        <td>
                            tranthib@gmail.com
                        </td>

                        <td>

                            <span class="badge badge-purple">
                                Student
                            </span>

                        </td>

                        <td>

                            <span class="badge badge-yellow">
                                Chờ duyệt
                            </span>

                        </td>

                        <td class="actions">

                            <button class="btn-small btn-success">
                                Duyệt
                            </button>

                            <button class="btn-small btn-warning">
                                Khóa
                            </button>

                            <button class="btn-small btn-secondary">
                                Đổi quyền
                            </button>

                        </td>

                    </tr>


                    <tr>

                        <td>3</td>

                        <td>
                            Lê Văn C
                        </td>

                        <td>
                            levanc@gmail.com
                        </td>

                        <td>

                            <span class="badge badge-blue">
                                Teacher
                            </span>

                        </td>

                        <td>

                            <span class="badge badge-red">
                                Bị khóa
                            </span>

                        </td>

                        <td class="actions">

                            <button class="btn-small btn-success">
                                Mở khóa
                            </button>

                            <button class="btn-small btn-secondary">
                                Đổi quyền
                            </button>

                        </td>

                    </tr>


                </tbody>

            </table>

        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>