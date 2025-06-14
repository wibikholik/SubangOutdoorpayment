<?php
session_start();
include '../route/koneksi.php';

// Cek role user
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

// Validasi ID kategori
if (!isset($_GET['id_kategori'])) {
    header("Location: kategori.php?pesan=gagal");
    exit;
}

$id = $_GET['id_kategori'];

// Cek apakah ID ada di database
$cek = mysqli_query($koneksi, "SELECT * FROM kategori WHERE id_kategori='$id'");
if (mysqli_num_rows($cek) === 0) {
    header("Location: kategori.php?pesan=gagal");
    exit;
}

// Lakukan penghapusan
$hapus = mysqli_query($koneksi, "DELETE FROM kategori WHERE id_kategori='$id'");
if ($hapus) {
    header("Location: kategori.php?pesan=hapus");
} else {
    header("Location: kategori.php?pesan=gagal");
}
exit;
?>
