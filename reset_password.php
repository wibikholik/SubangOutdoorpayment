<?php
session_start();
include 'route/koneksi.php';

$message = '';
$message_type = '';

if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
    header("Location: verifikasi_otp.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "Konfirmasi password tidak cocok. Silakan coba lagi.";
        $message_type = 'error';
    } elseif (strlen($password) < 8) {
        $message = "Password baru harus terdiri dari minimal 8 karakter.";
        $message_type = 'error';
    } else {
        $email = $_SESSION['reset_email'];

        $plainPassword = $password;

        $user_data = null;
        $tables = ['admin', 'owner', 'penyewa'];
        foreach ($tables as $table) {
            $stmt_check = $koneksi->prepare("SELECT email FROM $table WHERE email = ?");
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            if ($result->num_rows > 0) {
                $user_data = ['table' => $table];
                break;
            }
        }

        if ($user_data) {
            $target_table = $user_data['table'];
            
            $stmt_update = $koneksi->prepare("UPDATE $target_table SET password = ? WHERE email = ?");
            $stmt_update->bind_param("ss", $plainPassword, $email); 


            if ($stmt_update->execute()) {
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['success_message'] = "Password berhasil direset. Silakan login dengan password baru Anda.";
                header("Location: login.php");
                exit;
            } else {
                $message = "Gagal memperbarui password di database.";
                $message_type = 'error';
            }
        } else {
            $message = "Email tidak ditemukan di sistem kami.";
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Subang Outdoor</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            display: flex;
            align-items: center;
        }
        input[type="password"], input[type="text"] {
            width: 100%;
            padding: 12px 40px 12px 14px;
            border: 1.5px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #888;
            user-select: none;
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
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Atur Password Baru</h2>
        
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="reset_password.php" novalidate>
            <div class="form-group">
                <label for="password">Password Baru</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" placeholder="Minimal 8 karakter" required minlength="8">
                    <span class="toggle-password">👁️</span>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password Baru</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Ketik ulang password baru" required>
                    <span class="toggle-password">👁️</span>
                </div>
            </div>
            <button type="submit">Reset Password</button>
        </form>
    </div>
    
    <script>
        document.querySelectorAll('.toggle-password').forEach(item => {
            item.addEventListener('click', function () {
                const passwordInput = this.previousElementSibling;
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.textContent = type === 'password' ? '👁️' : '🙈';
            });
        });
    </script>
</body>
</html>