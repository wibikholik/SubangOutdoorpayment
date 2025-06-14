<?php
include '../route/koneksi.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Cari email di semua tabel
    $tables = ['admin' => 'email', 'owner' => 'email', 'penyewa' => 'email'];
    $found = false;

    foreach ($tables as $table => $emailField) {
        $cek = mysqli_query($koneksi, "SELECT * FROM $table WHERE $emailField = '$email'");
        if (mysqli_num_rows($cek) > 0) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_time'] = time();

            // Kirim email
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
               $mail->Subject = 'Kode OTP Reset Password - Subang Outdoor';
                $mail->isHTML(true);
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
