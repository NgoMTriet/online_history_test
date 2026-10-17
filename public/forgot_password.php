
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$message = '';

if (isset($_GET['sent'])) {
    $message = 'Nếu email đã đăng ký, hệ thống sẽ gửi liên kết đặt lại mật khẩu.';
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] === 'email') {
        $message = 'Vui lòng nhập địa chỉ email hợp lệ.';
    } elseif ($_GET['error'] === 'mail') {
        $message = 'Không gửi được email. Vui lòng thử lại sau.';
    } elseif ($_GET['error'] === 'token') {
        $message = 'Liên kết không hợp lệ hoặc đã hết hạn.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - TestSuViet.com</title>

    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eef4ff, #f8fafc);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            width: 100%;
            max-width: 430px;
            padding: 35px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.10);
            box-sizing: border-box;
        }

        h2 {
            margin: 0 0 12px;
            color: #1e3a5f;
            font-size: 27px;
            text-align: center;
        }

        .description {
            margin-bottom: 25px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            text-align: center;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: bold;
        }

        input[type="email"] {
            width: 100%;
            padding: 13px 14px;
            margin-bottom: 18px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input[type="email"]:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        button[type="submit"] {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        button[type="submit"]:hover {
            background: #1d4ed8;
        }

        .message {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 14px;
            line-height: 1.5;
        }

        .back-link {
            display: block;
            margin-top: 22px;
            text-align: center;
            color: #2563eb;
            font-size: 14px;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                padding: 25px 20px;
            }

            h2 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>QUÊN MẬT KHẨU</h2>

        <?php if ($message !== ''): ?>
            <p class="message" role="status">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="../api/auth.php?action=forgot">
            <label for="email">Email đã đăng ký</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Nhập email đăng ký"
                required
            >

            <button type="submit">Gửi liên kết đặt lại mật khẩu</button>
        </form>

        <a href="login.php">← Quay lại đăng nhập</a>
    </div>
</body>
</html>
