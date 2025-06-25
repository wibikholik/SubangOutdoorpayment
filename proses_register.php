<?php
session_start();
include 'route/koneksi.php'; // Koneksi ke database menggunakan variabel $koneksi

// Mengecek apakah request adalah POST dan tombol register ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    // ====== AMBIL DAN BERSIHKAN INPUT USER ======
    $nama_penyewa = trim(mysqli_real_escape_string($koneksi, $_POST['nama_penyewa']));
    $alamat       = trim(mysqli_real_escape_string($koneksi, $_POST['alamat']));
    $no_hp        = trim(mysqli_real_escape_string($koneksi, $_POST['no_hp']));
    $email        = trim(mysqli_real_escape_string($koneksi, $_POST['email']));
    $password     = mysqli_real_escape_string($koneksi, $_POST['password']); // TANPA hash (lihat catatan)

    // ====== VALIDASI FORM TIDAK BOLEH KOSONG ======
    if (empty($nama_penyewa) || empty($alamat) || empty($no_hp) || empty($email) || empty($password)) {
        echo "<script>
                alert('Semua field harus diisi!');
                window.location.href = 'register.php';
              </script>";
        exit;
    }

    // ====== VALIDASI FORMAT EMAIL ======
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
                alert('Format email tidak valid.');
                window.location.href = 'register.php';
              </script>";
        exit;
    }

    // ====== CEK APAKAH EMAIL SUDAH TERDAFTAR ======
    $cek_email = mysqli_query($koneksi, "SELECT id_penyewa FROM penyewa WHERE email = '$email'");
    if (mysqli_num_rows($cek_email) > 0) {
        echo "<script>
                alert('Email sudah terdaftar. Silakan gunakan email lain.');
                window.location.href = 'register.php';
              </script>";
        exit;
    }

    // ====== SIMPAN DATA KE DATABASE TANPA HASH PASSWORD (TIDAK DISARANKAN) ======
    $query = "INSERT INTO penyewa (nama_penyewa, alamat, no_hp, email, password) 
              VALUES ('$nama_penyewa', '$alamat', '$no_hp', '$email', '$password')";

    if (mysqli_query($koneksi, $query)) {
        echo "<script>
                alert('Registrasi berhasil. Silakan login.');
                window.location.href = 'login.php';
              </script>";
        exit;
    } else {
        // Jika gagal simpan, tampilkan error dari MySQL
        echo "<script>
                alert('Gagal menyimpan data: " . mysqli_error($koneksi) . "');
                window.location.href = 'register.php';
              </script>";
        exit;
    }
} else {
    // Jika halaman diakses tanpa POST register, kembalikan ke halaman register
    header("Location: register.php");
    exit;
}
?>
