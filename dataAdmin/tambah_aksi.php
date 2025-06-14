<?php
include '../route/koneksi.php';

// Ambil data dari form
$username    = $_POST['username'];
$nama_admin  = $_POST['nama_admin'];
$alamat      = $_POST['alamat'];
$no_hp       = $_POST['no_hp'];
$email       = $_POST['email'];
$password    = $_POST['password'];

// Query insert tanpa hash (password langsung disimpan)
$query = "INSERT INTO admin (username, nama_admin, alamat, no_hp, email, password) 
          VALUES ('$username', '$nama_admin', '$alamat', '$no_hp', '$email', '$password')";

if (mysqli_query($koneksi, $query)) {
    header("Location: admin.php?pesan=input");
    exit;
} else {
    echo "Gagal menambahkan data: " . mysqli_error($koneksi);
}
?>
