<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
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

        .register-container {
            background: #fff;
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

        label {
            display: block;
            text-align: left;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
        }

        input, textarea {
            width: 100%;
            padding: 10px 14px;
            margin-bottom: 15px;
            border: 1.5px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .password-container {
            position: relative;
        }

        .show-password {
            position: absolute;
            right: 10px;
            top: 11px;
            cursor: pointer;
            font-size: 14px;
            color: #007BFF;
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

        .login-link {
            margin-top: 15px;
            font-size: 14px;
        }

        .login-link a {
            color: #007BFF;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Registrasi Penyewa</h2>
        <form action="proses_register.php" method="POST" novalidate onsubmit="return validateForm()">
            <label>Nama Penyewa</label>
            <input type="text" name="nama_penyewa" required>

            <label>Alamat</label>
            <textarea name="alamat" required></textarea>

            <label>No HP</label>
<input type="number" name="no_hp" id="no_hp" required 
       min="1"
       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
       title="Masukkan angka tanpa huruf">


            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <div class="password-container">
                <input type="password" id="password" name="password" required minlength="8"
                       title="Password minimal 8 karakter">
                <span class="show-password" onclick="togglePassword()">👁️</span>
            </div>

            <button type="submit" name="register">Daftar</button>
        </form>
        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById("password");
            passwordField.type = passwordField.type === "password" ? "text" : "password";
        }

        function validateForm() {
            const noHp = document.querySelector('input[name="no_hp"]').value;
            const password = document.getElementById("password").value;

            if (!/^[0-9]+$/.test(noHp)) {
                alert("Nomor HP hanya boleh berisi angka.");
                return false;
            }

            if (password.length < 8) {
                alert("Password harus lebih dari 7 karakter (minimal 8 karakter).");
                return false;
            }

            return true;
        }
    </script>
</body>
</html>
