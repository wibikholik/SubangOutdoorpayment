<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

include '../route/koneksi.php';

if (isset($_GET['id_penyewa'])) {
    $id_penyewa = mysqli_real_escape_string($koneksi, $_GET['id_penyewa']);

    $query = "UPDATE penyewa SET status = 'aktif' WHERE id_penyewa = '$id_penyewa'";
    if (mysqli_query($koneksi, $query)) {
        header("Location: Penyewa.php?pesan=aktifkan");
        exit;
    } else {
        echo "Gagal mengaktifkan penyewa: " . mysqli_error($koneksi);
    }
} else {
    echo "ID penyewa tidak ditemukan.";
}
?>
