<?php
session_start();
$message = '';

// Cek apakah pengguna datang dari halaman lupa password, jika tidak, tendang.
if (!isset($_SESSION['reset_email'])) {
    header('Location: lupa_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = $_POST['otp'];

    if (
        isset($_SESSION['reset_otp']) &&
        $_SESSION['reset_otp'] == $input_otp &&
        time() - $_SESSION['reset_time'] < 300 // OTP valid selama 5 menit (300 detik)
    ) {
        // Jika OTP benar dan belum kedaluwarsa
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit;
    } else {
        // Cek kondisi error spesifik
        if (!isset($_SESSION['reset_otp']) || time() - $_SESSION['reset_time'] >= 300) {
            $message = "Kode OTP sudah kedaluwarsa. Silakan minta lagi.";
        } else {
            $message = "Kode OTP yang Anda masukkan salah.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi OTP - Subang Outdoor</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Ganti 'assets/img/bekgrun.jpg' jika path berbeda */
            background-image: url('assets/img/bekgrun.jpg'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px 35px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            width: 400px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        /* Style untuk pesan error */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            text-align: left;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #ccc;
            border-radius: 8px;
            font-size: 18px; /* Ukuran font lebih besar untuk OTP */
            box-sizing: border-box;
            text-align: center; /* OTP biasanya di tengah */
            letter-spacing: 5px; /* Memberi jarak antar angka OTP */
        }
        
        input[type="text"]:focus {
            border-color: #007BFF;
            outline: none;
        }

        button {
            background-color: #007BFF; /* Warna biru untuk konsistensi */
            color: white;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-link {
            margin-top: 20px;
            font-size: 14px;
        }

        .back-link a {
            color: #007BFF;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Verifikasi OTP</h2>
        <p style="color: #666; margin-top: -15px; margin-bottom: 20px; font-size: 15px;">
            Kami telah mengirimkan kode ke email Anda: <br><strong><?= htmlspecialchars($_SESSION['reset_email'] ?? 'email tidak ditemukan') ?></strong>
        </p>

        <?php if ($message): ?>
            <div class="error-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form action="verifikasi_otp.php" method="POST" novalidate>
            <div class="form-group">
                <label for="otp">Masukkan Kode OTP</label>
                <input type="text" name="otp" id="otp" maxlength="6" required placeholder="______" autofocus oninput="this.value = this.value.toUpperCase()">
            </div>
            <button type="submit">Verifikasi</button>
        </form>
        <p class="back-link"><a href="lupa_password.php">kembali ke halaman lupa password</a></p>
    </div>
</body>
</html>