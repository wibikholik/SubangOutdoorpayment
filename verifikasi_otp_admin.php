<?php
session_start();

$message = '';

if (!isset($_SESSION['reset_otp_admin'], $_SESSION['reset_email_admin'], $_SESSION['reset_time_admin'])) {
    header("Location: lupa_password_admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = $_POST['otp'] ?? '';

    $valid_time = 300; // 5 menit dalam detik
    $now = time();

    if ($now - $_SESSION['reset_time_admin'] > $valid_time) {
        $message = "Kode OTP sudah kadaluarsa. Silakan coba ulangi proses lupa password.";
        session_unset();
        session_destroy();
    } elseif ($input_otp == $_SESSION['reset_otp_admin']) {
        $_SESSION['otp_verified_admin'] = true;
        header("Location: reset_password_admin.php");
        exit;
    } else {
        $message = "Kode OTP salah. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Verifikasi OTP Admin</title>
    <style>
        /* style sama seperti sebelumnya */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('assets/img/bekgrun.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
            width: 400px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
        }
        input, button {
            width: 100%;
            padding: 10px 14px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            font-weight: bold;
            color: red;
            margin-bottom: 15px;
        }
        .back-link {
            margin-top: 10px;
        }
        .back-link a {
            color: #007bff;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verifikasi OTP Admin</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="otp" placeholder="Masukkan kode OTP" required autofocus maxlength="6" />
            <button type="submit">Verifikasi OTP</button>
        </form>
        <div class="back-link">
            <a href="lupa_password_admin.php">&larr; Kirim ulang OTP</a>
        </div>
    </div>
</body>
</html>
