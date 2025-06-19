<?php
require '../../vendor/autoload.php';
include '../../route/koneksi.php';

use Midtrans\Config;
use Midtrans\Notification;

Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
Config::$isProduction = false;
Config::$isSanitized = true;
Config::$is3ds = true;

try {
    $notif = new Notification();

    $order_id = $notif->order_id;
    $transaction_status = $notif->transaction_status;
    $fraud_status = $notif->fraud_status;

    // Cek di tabel transaksi
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM transaksi WHERE order_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $transaksi = mysqli_fetch_assoc($result);

    if ($transaksi) {
        $id_transaksi = $transaksi['id_transaksi'];
        $status_lama = $transaksi['status'];
        $status_baru = $status_lama;

        switch ($transaction_status) {
            case 'capture':
                $status_baru = ($fraud_status === 'challenge') ? 'Menunggu Konfirmasi Pembayaran' : 'Dikonfirmasi';
                break;
            case 'settlement':
                $status_baru = 'Dikonfirmasi Pembayaran Silahkan AmbilBarang';
                break;
            case 'pending':
                $status_baru = 'Menunggu Pembayaran';
                break;
            case 'deny':
                $status_baru = 'Pembayaran Ditolak';
                break;
            case 'cancel':
                $status_baru = 'Dibatalkan';
                break;
            case 'expire':
                // Hapus pembayaran (jika ada)
                $stmtBayar = mysqli_prepare($koneksi, "DELETE FROM pembayaran WHERE id_transaksi = ?");
                mysqli_stmt_bind_param($stmtBayar, "i", $id_transaksi);
                mysqli_stmt_execute($stmtBayar);

                // Hapus detail transaksi
                $stmtDelDetail = mysqli_prepare($koneksi, "DELETE FROM detail_transaksi WHERE id_transaksi = ?");
                mysqli_stmt_bind_param($stmtDelDetail, "i", $id_transaksi);
                mysqli_stmt_execute($stmtDelDetail);

                // Hapus transaksi utama
                $stmtDelTrans = mysqli_prepare($koneksi, "DELETE FROM transaksi WHERE id_transaksi = ?");
                mysqli_stmt_bind_param($stmtDelTrans, "i", $id_transaksi);
                mysqli_stmt_execute($stmtDelTrans);

                http_response_code(200);
                echo json_encode(['result' => 'expired and deleted']);
                exit;
                break;
        }

        if ($status_baru !== $status_lama) {
            $stmtUpdate = mysqli_prepare($koneksi, "UPDATE transaksi SET status = ? WHERE id_transaksi = ?");
            mysqli_stmt_bind_param($stmtUpdate, "si", $status_baru, $id_transaksi);
            mysqli_stmt_execute($stmtUpdate);
        }

        // Jika status berubah menjadi "dikonfirmasi pembayaran silahkan ambilbarang", kurangi stok
        if ($status_baru === 'Dikonfirmasi Pembayaran Silahkan AmbilBarang' && $status_lama !== $status_baru) {
            $stmtDetail = mysqli_prepare($koneksi, "SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?");
            mysqli_stmt_bind_param($stmtDetail, "i", $id_transaksi);
            mysqli_stmt_execute($stmtDetail);
            $resultDetail = mysqli_stmt_get_result($stmtDetail);

            while ($row = mysqli_fetch_assoc($resultDetail)) {
                $stmtStok = mysqli_prepare($koneksi, "UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
                mysqli_stmt_bind_param($stmtStok, "ii", $row['jumlah_barang'], $row['id_barang']);
                mysqli_stmt_execute($stmtStok);
            }
        }
    }
    // Jika tidak ada di transaksi, cek di pengembalian
    else {
        $stmtPengembalian = mysqli_prepare($koneksi, "SELECT * FROM pengembalian WHERE order_id = ? OR snap_token = ?");
        mysqli_stmt_bind_param($stmtPengembalian, "ss", $order_id, $order_id);
        mysqli_stmt_execute($stmtPengembalian);
        $resultPengembalian = mysqli_stmt_get_result($stmtPengembalian);
        $pengembalian = mysqli_fetch_assoc($resultPengembalian);

        if ($pengembalian) {
            $id_pengembalian = $pengembalian['id_pengembalian'];
            $id_transaksi = $pengembalian['id_transaksi'];
            $status_lama = $pengembalian['status_pengembalian'] ?? '';

            if ($transaction_status === 'settlement') {
                $stmt = mysqli_prepare($koneksi, "UPDATE pengembalian SET status_pengembalian = 'Diterima' WHERE id_pengembalian = ?");
                mysqli_stmt_bind_param($stmt,  "i", $id_pengembalian);
                mysqli_stmt_execute($stmt);

                $stmt = mysqli_prepare($koneksi, "UPDATE transaksi SET status = 'Selesai Dikembalikan' WHERE id_transaksi = ?");
                mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
                mysqli_stmt_execute($stmt);

                // Tambah stok kembali
                $stmtDetail = mysqli_prepare($koneksi, "SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?");
                mysqli_stmt_bind_param($stmtDetail, "i", $id_transaksi);
                mysqli_stmt_execute($stmtDetail);
                $resultDetail = mysqli_stmt_get_result($stmtDetail);

                while ($row = mysqli_fetch_assoc($resultDetail)) {
                    $stmtStok = mysqli_prepare($koneksi, "UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
                    mysqli_stmt_bind_param($stmtStok, "ii", $row['jumlah_barang'], $row['id_barang']);
                    mysqli_stmt_execute($stmtStok);
                }
            } elseif (in_array($transaction_status, ['deny', 'expire', 'cancel'])) {
                $newStatus = ($transaction_status === 'deny') ? 'Ditolak Pengembalian' : 'Batal Pengembalian';
                $stmt = mysqli_prepare($koneksi, "UPDATE pengembalian SET status_pengembalian = ? WHERE id_pengembalian = ?");
                mysqli_stmt_bind_param($stmt, "si", $newStatus, $id_pengembalian);
                mysqli_stmt_execute($stmt);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Order ID tidak ditemukan di transaksi maupun pengembalian.']);
            exit;
        }
    }

    http_response_code(200);
    echo json_encode(['result' => 'success']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
