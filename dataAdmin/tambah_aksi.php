<?php
include '../route/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php");
    exit;
}

// Ambil data dari form, trim untuk bersihkan spasi
$username    = trim($_POST['username']);
$nama_admin  = trim($_POST['nama_admin']);
$alamat      = trim($_POST['alamat']);
$no_hp       = trim($_POST['no_hp']);
$email       = trim($_POST['email']);
$password    = $_POST['password'];

// Validasi server-side
if (!preg_match('/^\d+$/', $no_hp)) {
    die("No HP hanya boleh angka.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Email tidak valid.");
}

if (strlen($password) < 8) {
    die("Password harus lebih dari 8 karakter.");
}

// Prepared statement untuk insert
$stmt = mysqli_prepare($koneksi, "INSERT INTO admin (username, nama_admin, alamat, no_hp, email, password) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    die("Gagal prepare statement: " . mysqli_error($koneksi));
}

mysqli_stmt_bind_param($stmt, "ssssss", $username, $nama_admin, $alamat, $no_hp, $email, $password);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: admin.php?pesan=input");
    exit;
} else {
    echo "Gagal menambahkan data: " . mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
}
?>
