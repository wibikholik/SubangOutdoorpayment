<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

if (isset($_GET['id_metode'])) {
    $id_metode = intval($_GET['id_metode']);

    // Ambil nama file gambar dulu
    $stmt = mysqli_prepare($koneksi, "SELECT gambar_metode FROM metode_pembayaran WHERE id_metode = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_metode);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $gambar_metode);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($gambar_metode) {
        $file_path = "metode/gambar/" . $gambar_metode;
        if (file_exists($file_path)) {
            unlink($file_path); // hapus file gambarnya
        }
    }

    // Hapus data di database
    $stmt_del = mysqli_prepare($koneksi, "DELETE FROM metode_pembayaran WHERE id_metode = ?");
    mysqli_stmt_bind_param($stmt_del, "i", $id_metode);
    if (mysqli_stmt_execute($stmt_del)) {
        header("Location: metode.php?pesan=hapus");
        exit;
    } else {
        echo "Gagal menghapus data: " . mysqli_error($koneksi);
    }
} else {
    header("Location: metode.php");
    exit;
}
