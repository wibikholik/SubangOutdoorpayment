<?php
session_start();

$message = '';

if (!isset($_SESSION['reset_otp_owner'], $_SESSION['reset_time_owner'])) {
    header("Location: lupa_password_owner.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = $_POST['otp'] ?? '';

    // Cek waktu OTP 5 menit
    if ((time() - $_SESSION['reset_time_owner']) > 300) {
        $message = "Kode OTP sudah kadaluarsa.";
        session_unset();
        session_destroy();
    } elseif ($input_otp == $_SESSION['reset_otp_owner']) {
        $_SESSION['otp_verified_owner'] = true;
        header("Location: reset_password_owner.php");
        exit;
    } else {
        $message = "Kode OTP salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Verifikasi OTP Owner</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
            width: 400px;
            text-align: center;
        }
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            background-color: #007bff;
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            color: red;
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verifikasi OTP Owner</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="otp" placeholder="Masukkan Kode OTP" required maxlength="6" pattern="\d{6}" />
            <button type="submit">Verifikasi</button>
        </form>
    </div>
</body>
</html>
