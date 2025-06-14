<?php
include '../route/koneksi.php';

$id_admin   = $_POST['id_admin'];
$username   = $_POST['username'];
$nama_admin = $_POST['nama_admin'];
$alamat     = $_POST['alamat'];
$no_hp      = $_POST['no_hp'];
$email      = $_POST['email'];
$password   = $_POST['password'];

// Prepare statement
$stmt = mysqli_prepare($koneksi, "UPDATE admin SET username=?, nama_admin=?, alamat=?, no_hp=?, email=?, password=? WHERE id_admin=?");
mysqli_stmt_bind_param($stmt, "ssssssi", $username, $nama_admin, $alamat, $no_hp, $email, $password, $id_admin);

if (mysqli_stmt_execute($stmt)) {
    header("Location: admin.php?pesan=update");
    exit;
} else {
    echo "Gagal update data: " . mysqli_error($koneksi);
}

mysqli_stmt_close($stmt);
?>
