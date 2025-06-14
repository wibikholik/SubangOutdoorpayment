<?php
session_start();
include '../route/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_transaksi'])) {
    $id_transaksi = $_POST['id_transaksi'];

    mysqli_begin_transaction($koneksi);

    try {
        // Hapus dari tabel terkait
        mysqli_query($koneksi, "DELETE FROM pembayaran WHERE id_transaksi = '$id_transaksi'");
        mysqli_query($koneksi, "DELETE FROM pengembalian WHERE id_transaksi = '$id_transaksi'");
        mysqli_query($koneksi, "DELETE FROM detail_transaksi WHERE id_transaksi = '$id_transaksi'");
        mysqli_query($koneksi, "DELETE FROM transaksi WHERE id_transaksi = '$id_transaksi'");

        mysqli_commit($koneksi);
        header('Location: transaksi.php?success=1');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        header('Location: transaksi.php?error=1');
        exit;
    }
} else {
    header('Location: transaksi.php?error=1');
    exit;
}
?>
