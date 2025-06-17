<?php
include '../route/koneksi.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $tables = ['admin' => 'email', 'owner' => 'email', 'penyewa' => 'email'];
    $found = false;

    foreach ($tables as $table => $emailField) {
        $cek = mysqli_query($koneksi, "SELECT * FROM $table WHERE $emailField = '$email'");
        if (mysqli_num_rows($cek) > 0) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_time'] = time();

            $mail = new PHPMailer(true);
            try {
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
                $mail->isHTML(false); // Nonaktifkan HTML

                // Isi email versi plain text yang profesional
                $mail->Body = 
"Yth. Pengguna Subang Outdoor,

Kami menerima permintaan reset password untuk akun Anda.

Berikut adalah kode OTP Anda:
$otp

Kode ini hanya berlaku selama 5 menit dan mohon untuk tidak dibagikan kepada siapa pun demi alasan keamanan.

Jika Anda tidak merasa melakukan permintaan ini, harap abaikan email ini.

Hormat kami,
Tim Subang Outdoor";

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
