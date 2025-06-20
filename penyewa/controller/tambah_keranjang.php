<?php
session_start();
include '../../route/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}
$id_penyewa = $_SESSION['user_id'];

if (isset($_POST['id_barang']) && isset($_POST['jumlah'])) {
    $id_barang = (int)$_POST['id_barang'];
    $jumlah = (int)$_POST['jumlah'];

    if ($jumlah <= 0) {
        echo "<script>alert('Jumlah harus lebih dari 0.'); window.history.back();</script>";
        exit;
    }

    // Cek apakah barang sudah ada di keranjang
    $cekQuery = mysqli_prepare($koneksi, "SELECT id, jumlah FROM carts WHERE id_penyewa = ? AND id_barang = ?");
    mysqli_stmt_bind_param($cekQuery, "ii", $id_penyewa, $id_barang);
    mysqli_stmt_execute($cekQuery);
    $result = mysqli_stmt_get_result($cekQuery);

    if ($row = mysqli_fetch_assoc($result)) {
        // Update jumlah
        $jumlah_baru = $row['jumlah'] + $jumlah;
        $updateQuery = mysqli_prepare($koneksi, "UPDATE carts SET jumlah = ? WHERE id = ?");
        mysqli_stmt_bind_param($updateQuery, "ii", $jumlah_baru, $row['id']);
        mysqli_stmt_execute($updateQuery);
    } else {
        // Insert baru
        $insertQuery = mysqli_prepare($koneksi, "INSERT INTO carts (id_penyewa, id_barang, jumlah) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($insertQuery, "iii", $id_penyewa, $id_barang, $jumlah);
        mysqli_stmt_execute($insertQuery);
    }

    echo "<script>alert('Barang berhasil ditambahkan ke keranjang.'); window.location.href='../page/keranjang.php';</script>";
    exit;
} else {
    echo "<script>alert('Data tidak lengkap.'); window.history.back();</script>";
    exit;
}
?>
