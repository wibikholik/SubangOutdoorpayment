<?php
session_start();
include '../../route/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_transaksi'])) {
    $id_transaksi = (int) $_POST['id_transaksi'];

    // Cek status transaksi
    $stmt = $koneksi->prepare("SELECT status FROM transaksi WHERE id_transaksi = ? AND id_penyewa = ?");
    $stmt->bind_param("ii", $id_transaksi, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaksi = $result->fetch_assoc();
    $stmt->close();

    if (!$transaksi) {
        die('Transaksi tidak ditemukan atau bukan milik Anda.');
    }

    $status_lama = strtolower($transaksi['status']);

    // Hanya bisa batal jika belum bayar / menunggu
    if (in_array($status_lama, ['belumbayar', 'menunggu pembayaran', 'menunggu konfirmasi pembayaran'])) {

        // Kembalikan stok (jika sudah dikurangi sebelumnya)
        $stmt_items = $koneksi->prepare("SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?");
        $stmt_items->bind_param("i", $id_transaksi);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        while ($row = $result_items->fetch_assoc()) {
            $id_barang = $row['id_barang'];
            $jumlah = $row['jumlah_barang'];

            $update_stok = $koneksi->prepare("UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
            $update_stok->bind_param("ii", $jumlah, $id_barang);
            $update_stok->execute();
            $update_stok->close();
        }
        $stmt_items->close();

        // Update status transaksi
        $stmt_update = $koneksi->prepare("UPDATE transaksi SET status = 'Batal' WHERE id_transaksi = ?");
        $stmt_update->bind_param("i", $id_transaksi);
        $stmt_update->execute();
        $stmt_update->close();

        // Hapus pembayaran jika ada
        $stmt_delete = $koneksi->prepare("DELETE FROM pembayaran WHERE id_transaksi = ?");
        $stmt_delete->bind_param("i", $id_transaksi);
        $stmt_delete->execute();
        $stmt_delete->close();

        header("Location: transaksi.php?alert=batal_success");
        exit;
    } else {
        die("Transaksi tidak dapat dibatalkan karena statusnya sudah \"$status_lama\".");
    }
} else {
    die('Permintaan tidak valid.');
}
