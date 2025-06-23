<?php
session_start();
include 'route/koneksi.php';

$message = '';
$message_type = '';

if (!isset($_SESSION['otp_verified_owner']) || !$_SESSION['otp_verified_owner']) {
    header("Location: verifikasi_otp_owner.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $message = "Konfirmasi password tidak cocok.";
        $message_type = 'error';
    } elseif (strlen($password) < 8) {
        $message = "Password minimal 8 karakter.";
        $message_type = 'error';
    } else {
        $email = $_SESSION['reset_email_owner'] ?? '';

        if (empty($email)) {
            $message = "Email tidak ditemukan di session.";
            $message_type = 'error';
        } else {
            $stmt = $koneksi->prepare("UPDATE owner SET password = ? WHERE email = ?");

            // Kalau mau hash password:
            // $password_to_save = password_hash($password, PASSWORD_DEFAULT);
            $password_to_save = $password; // plain text

            $stmt->bind_param("ss", $password_to_save, $email);

            if ($stmt->execute()) {
                $stmt->close();

                session_unset();
                session_destroy();
                session_start();
                $_SESSION['success_message'] = "Password owner berhasil direset.";
                header("Location: login.php");
                exit;
            } else {
                $message = "Gagal update password: " . $stmt->error;
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Reset Password Owner</title>
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
            background: rgba(255,255,255,0.95);
            padding: 30px 35px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            width: 400px;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 25px;
        }
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid transparent;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .password-wrapper {
            position: relative;
            margin-bottom: 20px;
        }
        input[type="password"], input[type="text"] {
            width: 100%;
            padding: 12px 40px 12px 14px;
            border-radius: 8px;
            border: 1.5px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
            font-size: 18px;
            color: #666;
        }
        button {
            background-color: #28a745;
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
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Atur Password Baru Owner</h2>
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Password Baru (minimal 8 karakter)" required minlength="8" />
                <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
            </div>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Konfirmasi Password" required />
                <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
            </div>
            <button type="submit">Reset Password</button>
        </form>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
