<?php
session_start();

// Batasi akses hanya untuk admin dan owner
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

include '../route/koneksi.php';

// Cek apakah id_penyewa ada
if (isset($_GET['id_penyewa'])) {
    $id_penyewa = mysqli_real_escape_string($koneksi, $_GET['id_penyewa']);

    // Blok penyewa (ubah status jadi 'diblokir')
    $query = "UPDATE penyewa SET status = 'diblokir' WHERE id_penyewa = '$id_penyewa'";
    if (mysqli_query($koneksi, $query)) {
        header("Location: penyewa.php?pesan=blokir");
        exit;
    } else {
        echo "Gagal memblokir penyewa: " . mysqli_error($koneksi);
    }
} else {
    echo "ID penyewa tidak ditemukan.";
}
?>
