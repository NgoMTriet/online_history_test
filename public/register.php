<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Đăng ký - TestSuViet.com</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;

            background: linear-gradient(
                135deg,
                #eaf3ff,
                #f5f8ff
            );

            min-height: 100vh;

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 20px;
        }

        .register-container {

            width: 100%;

            max-width: 460px;

            background: white;

            padding: 40px;

            border-radius: 16px;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .logo {

            text-align: center;

            font-size: 24px;

            font-weight: bold;

            color: #087ff5;

            margin-bottom: 10px;
        }

        .register-title {

            text-align: center;

            font-size: 28px;

            margin-bottom: 30px;

            color: #222;
        }

        .form-group {

            margin-bottom: 18px;
        }

        .form-group label {

            display: block;

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 8px;

            color: #333;
        }

        .form-group input,
        .form-group select {

            width: 100%;

            padding: 13px 14px;

            border: 1px solid #d1d5db;

            border-radius: 8px;

            font-size: 15px;

            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus {

            border-color: #087ff5;

            box-shadow:
                0 0 0 3px
                rgba(8, 127, 245, 0.1);
        }

        .register-button {

            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 8px;

            background: #087ff5;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            margin-top: 5px;
        }

        .register-button:hover {

            background: #066fd4;
        }

        .divider {

            display: flex;

            align-items: center;

            gap: 12px;

            margin: 25px 0;
        }

        .divider::before,
        .divider::after {

            content: "";

            flex: 1;

            height: 1px;

            background: #ddd;
        }

        .divider span {

            color: #888;

            font-size: 13px;
        }

        .login-text {

            text-align: center;

            font-size: 14px;

            color: #555;
        }

        .login-text a {

            color: #087ff5;

            font-weight: bold;

            text-decoration: none;
        }

        .login-text a:hover {

            text-decoration: underline;
        }

        .back-home {

            display: block;

            text-align: center;

            margin-top: 25px;

            color: #777;

            font-size: 14px;

            text-decoration: none;
        }

        .back-home:hover {

            color: #087ff5;
        }

    </style>

</head>

<body>

<div class="register-container">

    <div class="logo">
        TestSuViet.com
    </div>

    <h1 class="register-title">
        Đăng ký tài khoản
    </h1>


    <!-- FORM ĐĂNG KÝ -->

    <form
        action="../api/auth.php?action=register"
        method="POST"
    >


        <!-- USERNAME -->

        <div class="form-group">

            <label>
                Tên đăng nhập
            </label>

            <input
                type="text"
                name="username"
                placeholder="Nhập tên đăng nhập"
                required
            >

        </div>


        <!-- EMAIL -->

        <div class="form-group">

            <label>
                Email
            </label>

            <input
                type="email"
                name="email"
                placeholder="Nhập email"
                required
            >

        </div>


        <!-- HỌ TÊN -->

        <div class="form-group">

            <label>
                Họ và tên
            </label>

            <input
                type="text"
                name="full_name"
                placeholder="Nhập họ và tên"
                required
            >

        </div>


        <!-- MẬT KHẨU -->

        <div class="form-group">

            <label>
                Mật khẩu
            </label>

            <input
                type="password"
                name="password"
                placeholder="Nhập mật khẩu"
                required
            >

        </div>


        <!-- XÁC NHẬN MẬT KHẨU -->

        <div class="form-group">

            <label>
                Xác nhận mật khẩu
            </label>

            <input
                type="password"
                name="confirm_password"
                placeholder="Nhập lại mật khẩu"
                required
            >

        </div>


        <!-- LOẠI TÀI KHOẢN -->

        <div class="form-group">

            <label>
                Loại tài khoản
            </label>

            <select name="role">

                <option value="student">
                    Học viên
                </option>

                <option value="teacher">
                    Giáo viên
                </option>

            </select>

        </div>


        <!-- NÚT ĐĂNG KÝ -->

        <button
            type="submit"
            class="register-button"
        >
            Đăng ký
        </button>

    </form>


    <!-- PHÂN CÁCH -->

    <div class="divider">

        <span>hoặc</span>

    </div>


    <!-- ĐĂNG NHẬP -->

    <div class="login-text">

        Đã có tài khoản?

        <a href="login.php">
            Đăng nhập
        </a>

    </div>


    <!-- TRANG CHỦ -->

    <a
        href="index.php"
        class="back-home"
    >
        ← Quay lại trang chủ
    </a>

</div>

</body>

</html>
