<?php
session_start();
include 'route/koneksi.php'; // koneksi pakai $koneksi

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nama_penyewa = trim(mysqli_real_escape_string($koneksi, $_POST['nama_penyewa']));
    $alamat       = trim(mysqli_real_escape_string($koneksi, $_POST['alamat']));
    $no_hp        = trim(mysqli_real_escape_string($koneksi, $_POST['no_hp']));
    $email        = trim(mysqli_real_escape_string($koneksi, $_POST['email']));
    $password     = mysqli_real_escape_string($koneksi, $_POST['password']); // tanpa hash

    // Validasi input
    if (empty($nama_penyewa) || empty($alamat) || empty($no_hp) || empty($email) || empty($password)) {
        echo "<script>
                alert('Semua field harus diisi!');
                window.location.href = 'register.php';
              </script>";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
                alert('Format email tidak valid.');
                window.location.href = 'register.php';
              </script>";
        exit;
    }

    // Cek email sudah ada atau belum
    $cek_email = mysqli_query($koneksi, "SELECT id_penyewa FROM penyewa WHERE email = '$email'");
    if (mysqli_num_rows($cek_email) > 0) {
        echo "<script>
                alert('Email sudah terdaftar. Silakan gunakan email lain.');
                window.location.href = 'register.php';
              </script>";
        exit;
    }

    // Simpan data tanpa mengenkripsi password
    $query = "INSERT INTO penyewa (nama_penyewa, alamat, no_hp, email, password) 
              VALUES ('$nama_penyewa', '$alamat', '$no_hp', '$email', '$password')";

    if (mysqli_query($koneksi, $query)) {
        echo "<script>
                alert('Registrasi berhasil. Silakan login.');
                window.location.href = 'login.php';
              </script>";
        exit;
    } else {
        echo "<script>
                alert('Gagal menyimpan data: " . mysqli_error($koneksi) . "');
                window.location.href = 'register.php';
              </script>";
        exit;
    }
} else {
    header("Location: register.php");
    exit;
}
?>