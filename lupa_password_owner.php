<?php
session_start();
include 'route/koneksi.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Cek email hanya di tabel owner
    $cek = mysqli_query($koneksi, "SELECT * FROM owner WHERE email = '$email'");

    if (mysqli_num_rows($cek) > 0) {
        $otp = rand(100000, 999999);
        $_SESSION['reset_email_owner'] = $email;
        $_SESSION['reset_otp_owner'] = $otp;
        $_SESSION['reset_time_owner'] = time();

        // Kirim email OTP
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'subangoutdoortes@gmail.com';  // Ganti email pengirim
            $mail->Password   = 'sbsn ajtg fgox otra';          // Ganti App Password email
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
            $mail->addAddress($email);
            $mail->Subject = 'Kode OTP Reset Password Owner';
            $mail->Body = "
            Yth. Pengguna Subang Outdoor,

            Kami menerima permintaan reset password untuk akun Owner Anda.

            Kode OTP Anda: $otp

            Kode ini berlaku 5 menit. Jangan bagikan kode ini kepada siapapun.

            Jika bukan Anda, abaikan email ini.

            Hormat kami,
            Tim Subang Outdoor";

            $mail->send();
            header("Location: verifikasi_otp_owner.php");
            exit;
        } catch (Exception $e) {
            $message = 'Gagal mengirim OTP. ' . $mail->ErrorInfo;
        }
    } else {
        $message = "Email tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Lupa Password Owner</title>
    <style>
        /* gaya sederhana seperti sebelumnya */
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
        <h2>Lupa Password Owner</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Masukkan Email Anda" required />
            <button type="submit">Kirim OTP</button>
        </form>
        <div class="back-link">
            <a href="login.php">&larr; Kembali ke Login</a>
        </div>
    </div>
</body>
</html>
