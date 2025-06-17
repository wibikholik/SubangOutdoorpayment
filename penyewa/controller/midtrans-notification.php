<?php
require '../../vendor/autoload.php';
include '../../route/koneksi.php';

use Midtrans\Config;
use Midtrans\Notification;

// Konfigurasi Midtrans
Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
Config::$isProduction = false;
Config::$isSanitized = true;
Config::$is3ds = true;

$notif = new Notification();

$order_id = $notif->order_id;
$transaction_status = $notif->transaction_status;
$fraud_status = $notif->fraud_status;

// === CEK apakah order_id ini dari transaksi biasa ===
$sql = "SELECT * FROM transaksi WHERE order_id = ?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "s", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transaksi = mysqli_fetch_assoc($result);

if ($transaksi) {
    // ===== PROSES TRANSAKSI BOOKING BIASA =====
    $id_transaksi = $transaksi['id_transaksi'];
    $status_lama = $transaksi['status'];
    $tanggal_order = strtotime($transaksi['tanggal_sewa']);
    $now = time();
    $status_baru = $status_lama;

    switch ($transaction_status) {
        case 'capture':
            $status_baru = ($fraud_status === 'challenge') ? 'menunggu konfirmasi pembayaran' : 'dikonfirmasi';
            break;
        case 'settlement':
            $status_baru = 'dikonfirmasi pembayaran silahkan ambilbarang';
            break;
        case 'pending':
            if ($status_lama === 'belumbayar' && ($now - $tanggal_order) > 3600) {
                $status_baru = 'batal';
            } else {
                $status_baru = 'belumbayar';
            }
            break;
        case 'deny':
            $status_baru = 'ditolak pembayaran';
            break;
        case 'expire':
        case 'cancel':
            $status_baru = 'batal';
            break;
    }

    if ($status_baru !== $status_lama) {
        $sqlUpdate = "UPDATE transaksi SET status = ? WHERE id_transaksi = ?";
        $stmtUpdate = mysqli_prepare($koneksi, $sqlUpdate);
        mysqli_stmt_bind_param($stmtUpdate, "si", $status_baru, $id_transaksi);
        mysqli_stmt_execute($stmtUpdate);
    }

    // Kurangi stok jika berhasil dikonfirmasi
    if ($status_lama !== 'dikonfirmasi pembayaran silahkan ambilbarang' && $status_baru === 'dikonfirmasi pembayaran silahkan ambilbarang') {
        $sqlDetail = "SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?";
        $stmtDetail = mysqli_prepare($koneksi, $sqlDetail);
        mysqli_stmt_bind_param($stmtDetail, "i", $id_transaksi);
        mysqli_stmt_execute($stmtDetail);
        $resultDetail = mysqli_stmt_get_result($stmtDetail);

        while ($row = mysqli_fetch_assoc($resultDetail)) {
            $id_barang = $row['id_barang'];
            $jumlah = $row['jumlah_barang'];

            $sqlUpdateStok = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
            $stmtStok = mysqli_prepare($koneksi, $sqlUpdateStok);
            mysqli_stmt_bind_param($stmtStok, "ii", $jumlah, $id_barang);
            mysqli_stmt_execute($stmtStok);
        }
    }

} else {
    // === CEK apakah order_id ini dari pengembalian (bisa dari order_id atau snap_token) ===

    // Cek berdasarkan order_id dulu
    $sqlPengembalianOrder = "SELECT * FROM pengembalian WHERE order_id = ?";
    $stmtPengembalianOrder = mysqli_prepare($koneksi, $sqlPengembalianOrder);
    mysqli_stmt_bind_param($stmtPengembalianOrder, "s", $order_id);
    mysqli_stmt_execute($stmtPengembalianOrder);
    $resultPengembalianOrder = mysqli_stmt_get_result($stmtPengembalianOrder);
    $pengembalian = mysqli_fetch_assoc($resultPengembalianOrder);

    // Jika tidak ketemu, coba cek berdasarkan snap_token
    if (!$pengembalian) {
        $sqlPengembalianSnap = "SELECT * FROM pengembalian WHERE snap_token = ?";
        $stmtPengembalianSnap = mysqli_prepare($koneksi, $sqlPengembalianSnap);
        mysqli_stmt_bind_param($stmtPengembalianSnap, "s", $order_id);
        mysqli_stmt_execute($stmtPengembalianSnap);
        $resultPengembalianSnap = mysqli_stmt_get_result($stmtPengembalianSnap);
        $pengembalian = mysqli_fetch_assoc($resultPengembalianSnap);
    }

    if ($pengembalian) {
        $id_pengembalian = $pengembalian['id_pengembalian'];
        $id_transaksi = $pengembalian['id_transaksi'];

        $status_lama = $pengembalian['status_pengembalian'] ?? '';

        if ($transaction_status === 'settlement') {
            // Update status pengembalian jadi Diterima
            $sqlUpdatePengembalian = "UPDATE pengembalian SET status_pengembalian = 'Diterima' WHERE id_pengembalian = ?";
            $stmtUpdatePengembalian = mysqli_prepare($koneksi, $sqlUpdatePengembalian);
            mysqli_stmt_bind_param($stmtUpdatePengembalian, "i", $id_pengembalian);
            mysqli_stmt_execute($stmtUpdatePengembalian);

            // Update status transaksi jadi selesai dikembalikan
            $status_baru = 'Selesai Dikembalikan';
            $sqlUpdateTransaksi = "UPDATE transaksi SET status = ? WHERE id_transaksi = ?";
            $stmtUpdateTransaksi = mysqli_prepare($koneksi, $sqlUpdateTransaksi);
            mysqli_stmt_bind_param($stmtUpdateTransaksi, "si", $status_baru, $id_transaksi);
            mysqli_stmt_execute($stmtUpdateTransaksi);

            // Jika status berubah, tambah stok barang kembali
            if ($status_lama !== $status_baru) {
                $sqlDetail = "SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?";
                $stmtDetail = mysqli_prepare($koneksi, $sqlDetail);
                mysqli_stmt_bind_param($stmtDetail, "i", $id_transaksi);
                mysqli_stmt_execute($stmtDetail);
                $resultDetail = mysqli_stmt_get_result($stmtDetail);

                while ($row = mysqli_fetch_assoc($resultDetail)) {
                    $id_barang = $row['id_barang'];
                    $jumlah = $row['jumlah_barang'];

                    $sqlUpdateStok = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
                    $stmtStok = mysqli_prepare($koneksi, $sqlUpdateStok);
                    mysqli_stmt_bind_param($stmtStok, "ii", $jumlah, $id_barang);
                    mysqli_stmt_execute($stmtStok);
                }
            }
        } elseif ($transaction_status === 'pending') {
            // Bisa tambahkan logic jika ingin set pengembalian jadi pending
        } elseif ($transaction_status === 'deny' || $transaction_status === 'cancel' || $transaction_status === 'expire') {
            // Update status pengembalian jadi ditolak atau batal sesuai kebutuhan
            $newStatus = ($transaction_status === 'deny') ? 'Ditolak Pengembalian' : 'Batal Pengembalian';
            $sqlUpdatePengembalian = "UPDATE pengembalian SET status_pengembalian = ? WHERE id_pengembalian = ?";
            $stmtUpdatePengembalian = mysqli_prepare($koneksi, $sqlUpdatePengembalian);
            mysqli_stmt_bind_param($stmtUpdatePengembalian, "si", $newStatus, $id_pengembalian);
            mysqli_stmt_execute($stmtUpdatePengembalian);
        }
    } else {
        // Order ID tidak ditemukan di transaksi atau pengembalian
        http_response_code(404);
        echo json_encode(['error' => 'Order ID tidak ditemukan.']);
        exit;
    }
}

http_response_code(200);
echo json_encode(['result' => 'success']);
