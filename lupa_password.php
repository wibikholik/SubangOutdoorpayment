<?php
session_start();
include 'route/koneksi.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Cek email di semua tabel
    $tables = ['admin' => 'email', 'owner' => 'email', 'penyewa' => 'email'];
    $found = false;

    foreach ($tables as $table => $field) {
        $cek = mysqli_query($koneksi, "SELECT * FROM $table WHERE $field = '$email'");
        if (mysqli_num_rows($cek) > 0) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_time'] = time();

            // Kirim email OTP
            $mail = new PHPMailer(true);
            try {
            $mail = new PHPMailer(true);
            // Konfigurasi SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'subangoutdoortes@gmail.com';  // Ganti dengan email pengirim Anda
            $mail->Password   = 'sbsn ajtg fgox otra';          // Ganti dengan App Password email
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
                $mail->addAddress($email);
                $mail->Subject = 'Kode OTP Reset Password';
                $mail->Body = "
                    <p>Halo,</p>
                    <p>Kami menerima permintaan untuk mereset password akun Anda di <strong>Subang Outdoor</strong>.</p>
                    <p>Berikut adalah kode OTP Anda:</p>
                    <h2 style='color:#2c3e50;'>$otp</h2>
                    <p>Jangan bagikan kode ini kepada siapa pun. Kode ini hanya berlaku selama <strong>5 menit</strong>.</p>
                    <br>
                    <p>Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan email ini.</p>
                    <p>Terima kasih,<br>Subang Outdoor Team</p>
                ";
                $mail->send();
                header("Location: verifikasi_otp.php");
                exit;
            } catch (Exception $e) {
                $message = 'Gagal mengirim OTP. ' . $mail->ErrorInfo;
            }

            $found = true;
            break;
        }
    }

    if (!$found) {
        $message = "Email tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lupa Password</title>
    <style>
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
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        input {
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
            color: #fff;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
            color: red;
        }
        .back-link {
            text-align: center;
            margin-top: 10px;
        }
        .back-link a {
            text-decoration: none;
            color: #007bff;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Lupa Password</h2>
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Masukkan Email Anda" required>
            <button type=" submit">Kirim OTP</button>
        </form>
        <div class="back-link">
            <a href="login.php">&larr; Kembali ke Login</a>
        </div>
    </div>
</body>
</html>