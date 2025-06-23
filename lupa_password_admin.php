<?php
session_start();
include 'route/koneksi.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $query = "SELECT * FROM admin WHERE email = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $otp = rand(100000, 999999);
        $_SESSION['reset_email_admin'] = $email;
        $_SESSION['reset_otp_admin'] = $otp;
        $_SESSION['reset_time_admin'] = time();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'subangoutdoortes@gmail.com';  
            $mail->Password   = 'sbsn ajtg fgox otra';          
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
            $mail->addAddress($email);
            $mail->Subject = 'Kode OTP Reset Password Admin';
            $mail->Body = "Kode OTP Anda adalah: $otp. Berlaku 5 menit.";

            $mail->send();

            header("Location: verifikasi_otp_admin.php");
            exit;
        } catch (Exception $e) {
            $message = 'Gagal mengirim OTP. ' . $mail->ErrorInfo;
        }
    } else {
        $message = "Email admin tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Lupa Password Admin</title>
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
        }
        h2 {
            text-align: center;
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
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
            color: red;
        }
        .back-link {
            text-align: center;
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
        <h2>Lupa Password Admin</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Masukkan Email Admin" required />
            <button type="submit">Kirim OTP</button>
        </form>
        <div class="back-link">
            <a href="login.php">&larr; Kembali ke Login</a>
        </div>
    </div>
</body>
</html>
