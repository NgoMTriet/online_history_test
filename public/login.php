<?php
session_start();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng nhập - TestSuViet.com</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eaf3ff, #f5f8ff);
            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 20px;
        }

        /* Khung đăng nhập */

        .login-container {
            width: 100%;
            max-width: 420px;

            background: white;

            padding: 40px;

            border-radius: 16px;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.1);
        }

        /* Logo */

        .logo {
            text-align: center;

            font-size: 24px;
            font-weight: bold;

            color: #087ff5;

            margin-bottom: 10px;
        }

        /* Tiêu đề */

        .login-title {
            text-align: center;

            font-size: 28px;

            margin-bottom: 30px;

            color: #222;
        }

        /* Label */

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

        /* Input */

        .form-group input {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #d1d5db;

            border-radius: 8px;

            font-size: 15px;

            outline: none;

            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: #087ff5;

            box-shadow: 0 0 0 3px rgba(8, 127, 245, 0.1);
        }

        /* Quên mật khẩu */

        .forgot-password {
            text-align: right;

            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #087ff5;

            text-decoration: none;

            font-size: 14px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        /* Nút đăng nhập */

        .login-button {
            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 8px;

            background: #087ff5;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.2s;
        }

        .login-button:hover {
            background: #066fd4;
        }

        /* Đường phân cách */

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

        /* Đăng ký */

        .register-text {
            text-align: center;

            font-size: 14px;

            color: #555;
        }

        .register-text a {
            color: #087ff5;

            font-weight: bold;

            text-decoration: none;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        /* Quay lại */

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

    <div class="login-container">

        <div class="logo">
            TestSuViet.com
        </div>

        <h1 class="login-title">
            Đăng nhập
        </h1>


        <!-- FORM ĐĂNG NHẬP -->

        <form action="../api/auth.php?action=login" method="POST">

            <div class="form-group">

                <label>
                    Username hoặc Email
                </label>

                <input
                    type="text"
                    name="username"
                    placeholder="Nhập username hoặc email"
                    required
                >

            </div>


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


            <div class="forgot-password">

                <a href="#">
                    Quên mật khẩu?
                </a>

            </div>


            <button
                type="submit"
                class="login-button"
            >
                Đăng nhập
            </button>

        </form>


        <!-- ĐƯỜNG PHÂN CÁCH -->

        <div class="divider">

            <span>hoặc</span>

        </div>


        <!-- ĐĂNG KÝ -->

        <div class="register-text">

            Chưa có tài khoản?

            <a href="register.php">
                Đăng ký
            </a>

        </div>


        <!-- QUAY LẠI TRANG CHỦ -->

        <a
            href="index.php"
            class="back-home"
        >
            ← Quay lại trang chủ
        </a>

    </div>

</body>

</html>
