<?php
session_start();

// Tampilkan pesan logout atau error jika ada
$message = '';
if (isset($_GET['message'])) {
    if ($_GET['message'] === 'logout') {
        $message = "Anda berhasil logout.";
    } elseif ($_GET['message'] === 'error') {
        $message = "Email atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
        }
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
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px 35px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 400px;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 25px;
        }
        .message {
            margin-bottom: 15px;
            font-weight: bold;
        }
        label {
            display: block;
            text-align: left;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
        }

        /* --- PERUBAHAN CSS DIMULAI DI SINI --- */

        /* Aturan input umum, margin-bottom dihapus */
        input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }

        /* Wrapper baru untuk memposisikan ikon */
        .password-input-wrapper {
            position: relative;
            margin-bottom: 20px; /* Margin dipindahkan ke sini */
        }

        /* Input di dalam wrapper diberi padding kanan untuk ruang ikon */
        .password-input-wrapper input {
            padding-right: 45px;
        }

        /* Aturan CSS baru yang presisi untuk ikon mata */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #777;
        }

        /* --- AKHIR PERUBAHAN CSS --- */

        .forgot-password {
            text-align: right;
            font-size: 14px;
            margin-top: -10px;
            margin-bottom: 20px;
        }
        .forgot-password a {
            color: #007BFF;
            text-decoration: none;
            display: inline-block;
            padding: 5px;
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }
        .forgot-password a:hover {
            text-decoration: underline;
            background-color: #e9ecef;
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
        .register-link {
            margin-top: 15px;
            font-size: 14px;
        }
        .register-link a {
            color: #007BFF;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login Subang Outdoor</h2>
        <?php if ($message): ?>
            <div class="message" style="color: <?= $_GET['message'] === 'error' ? 'red' : 'green' ?>;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="prosesLogin.php">
            <div>
                <label for="username">Username</label>
                <input type="text" name="username" id="username" placeholder="Masukkan username" required style="margin-bottom: 20px;">
            </div>
            
            <div>
                <label for="password">Password</label>
                <div class="password-input-wrapper">
                    <input type="password" name="password" id="password" placeholder="Masukkan password" required>
                    <span id="togglePassword" class="toggle-password">👁️</span>
                </div>
            </div>

            <div class="forgot-password">
                <a href="lupa_password.php">Lupa password?</a>
            </div>
            <button type="submit" name="login">Login</button>
        </form>
        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    </script>
</body>
</html>