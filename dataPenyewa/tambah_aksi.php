<?php
session_start();
include '../route/koneksi.php';

// Cek session
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

$nama_penyewa = $_POST['namapenyewa'];
$alamat       = $_POST['alamat'];
$no_hp        = $_POST['no_hp'];
$email        = $_POST['email'];
$password     = $_POST['password'];

// Cek apakah email atau no_hp sudah ada
$cek_query = "SELECT * FROM penyewa WHERE email = ? OR no_hp = ?";
$cek_stmt = mysqli_prepare($koneksi, $cek_query);
mysqli_stmt_bind_param($cek_stmt, "ss", $email, $no_hp);
mysqli_stmt_execute($cek_stmt);
$hasil = mysqli_stmt_get_result($cek_stmt);

if (mysqli_num_rows($hasil) > 0) {
    // Redirect dengan pesan error
    header("Location: tambah_Penyewa.php?pesan=duplikat");
    exit;
}

mysqli_stmt_close($cek_stmt);

// Lanjut insert jika tidak duplikat
$query = "INSERT INTO penyewa (nama_penyewa, alamat, no_hp, email, password) VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "sssss", $nama_penyewa, $alamat, $no_hp, $email, $password);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    header("Location: penyewa.php?pesan=input");
} else {
    echo "Gagal memasukkan data.";
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
?>
