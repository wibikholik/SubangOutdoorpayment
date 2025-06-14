<?php
include '../../route/koneksi.php';
require '../../vendor/autoload.php';

use Midtrans\Notification;
use Midtrans\Config;

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Konfigurasi Midtrans
Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
Config::$isProduction = false;
Config::$isSanitized = true;
Config::$is3ds = true;

$logFile = __DIR__ . '/notif_log.txt';
file_put_contents($logFile, "\n=== Notifikasi Masuk: " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);

try {
    if (!isset($koneksi) || !$koneksi) {
        throw new Exception("Koneksi database gagal: " . (mysqli_connect_error() ?: 'tidak tersedia'));
    }

    // Tangkap data mentah JSON
    $raw_data = file_get_contents("php://input");
    file_put_contents($logFile, "RAW: $raw_data\n", FILE_APPEND);

    // Cek dulu JSON valid dan ada order_id
    $parsed = json_decode($raw_data, true);
    if (!$parsed || !isset($parsed['order_id'])) {
        throw new Exception("Payload JSON tidak valid atau order_id kosong.");
    }

    // Proses notifikasi Midtrans
    $notif = new Notification();

    // Ambil data utama
    $transaction = $notif->transaction_status ?? '';
    $type        = $notif->payment_type ?? '';
    $order_id    = $notif->order_id ?? '';
    $fraud       = $notif->fraud_status ?? '';

    $status_baru = '';

    // === HANDLE PENGEMBALIAN ===
    if (preg_match('/^(PENGEMBALIAN|DENDA)-(\d+)$/', $order_id, $matches)) {
        $id_pengembalian = (int)$matches[2];

        switch ($transaction) {
            case 'capture':
                $status_baru = ($type === 'credit_card' && $fraud === 'challenge') 
                    ? 'Menunggu Konfirmasi Pembayaran Denda' 
                    : 'Dikonfirmasi Pembayaran Denda';
                break;
            case 'settlement':
                $status_baru = 'Selesai Dikembalikan';
                break;
            case 'pending':
                $status_baru = 'Belum Bayar Denda';
                break;
            case 'deny':
                $status_baru = 'Ditolak Pembayaran Denda';
                break;
            case 'cancel':
            case 'expire':
                $status_baru = 'Batal Pengembalian';
                break;
            default:
                $status_baru = 'Belum Bayar Denda';
        }

        $stmt = $koneksi->prepare("UPDATE pengembalian SET status_pengembalian = ? WHERE id_pengembalian = ?");
        if (!$stmt) throw new Exception("Prepare statement gagal: " . $koneksi->error);
        $stmt->bind_param("si", $status_baru, $id_pengembalian);
        $stmt->execute();
        $stmt->close();
        file_put_contents($logFile, "Status pengembalian diupdate: $status_baru (ID: $id_pengembalian)\n", FILE_APPEND);

        if (in_array($status_baru, ['Dikonfirmasi Pembayaran Denda', 'Selesai Dikembalikan'])) {
            $stmt = $koneksi->prepare("UPDATE pengembalian SET status_pembayaran = 'Sudah Dibayar' WHERE id_pengembalian = ?");
            if (!$stmt) throw new Exception("Prepare statement gagal: " . $koneksi->error);
            $stmt->bind_param("i", $id_pengembalian);
            $stmt->execute();
            $stmt->close();
            file_put_contents($logFile, "Status pembayaran denda diupdate jadi 'Sudah Dibayar'\n", FILE_APPEND);
        }

        if ($status_baru === 'Selesai Dikembalikan') {
            $stmt = $koneksi->prepare("SELECT id_barang, jumlah, id_transaksi FROM pengembalian WHERE id_pengembalian = ?");
            if (!$stmt) throw new Exception("Prepare statement gagal: " . $koneksi->error);
            $stmt->bind_param("i", $id_pengembalian);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            if ($data) {
                $id_barang = $data['id_barang'];
                $jumlah = $data['jumlah'];
                $id_transaksi = $data['id_transaksi'];

                $stmt = $koneksi->prepare("UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
                if (!$stmt) throw new Exception("Prepare statement gagal: " . $koneksi->error);
                $stmt->bind_param("ii", $jumlah, $id_barang);
                $stmt->execute();
                $stmt->close();
                file_put_contents($logFile, "Stok barang ID $id_barang ditambah $jumlah\n", FILE_APPEND);

                $stmt = $koneksi->prepare("UPDATE transaksi SET status = 'Selesai Dikembalikan' WHERE id_transaksi = ?");
                if (!$stmt) throw new Exception("Prepare statement gagal: " . $koneksi->error);
                $stmt->bind_param("i", $id_transaksi);
                $stmt->execute();
                $stmt->close();
                file_put_contents($logFile, "Status transaksi ID $id_transaksi diupdate ke 'Selesai Dikembalikan'\n", FILE_APPEND);
            }
        }
    } 
    // === HANDLE TRANSAKSI BIASA ===
    elseif (preg_match('/TRX-(\d+)/', $order_id, $matches)) {
        $id_transaksi = (int)$matches[1];

        switch ($transaction) {
            case 'capture':
                $status_baru = ($type === 'credit_card' && $fraud === 'challenge') 
                    ? 'Menunggu Konfirmasi Pembayaran' 
                    : 'Dikonfirmasi (Silahkan Ambil Barang)';
                break;
            case 'settlement':
                $status_baru = 'Dikonfirmasi (Silahkan Ambil Barang)';
                break;
            case 'pending':
                $status_baru = 'Belum Bayar';
                break;
            case 'deny':
                $status_baru = 'Ditolak Pembayaran';
                break;
            case 'cancel':
            case 'expire':
                $status_baru = 'Batal';
                break;
            default:
                $status_baru = 'Belum Bayar';
        }

        $stmt = $koneksi->prepare("UPDATE transaksi SET status = ? WHERE id_transaksi = ?");
        if (!$stmt) throw new Exception("Prepare statement gagal: " . $koneksi->error);
        $stmt->bind_param("si", $status_baru, $id_transaksi);
        $stmt->execute();
        $stmt->close();
        file_put_contents($logFile, "Status transaksi ID $id_transaksi diupdate ke '$status_baru'\n", FILE_APPEND);

        if ($status_baru === 'Dikonfirmasi (Silahkan Ambil Barang)') {
            $stmt = $koneksi->prepare("SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?");
            if (!$stmt) throw new Exception("Prepare statement gagal: " . $koneksi->error);
            $stmt->bind_param("i", $id_transaksi);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $id_barang = $row['id_barang'];
                $jumlah = $row['jumlah_barang'];

                $stmt2 = $koneksi->prepare("UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
                if (!$stmt2) throw new Exception("Prepare statement gagal: " . $koneksi->error);
                $stmt2->bind_param("ii", $jumlah, $id_barang);
                $stmt2->execute();
                $stmt2->close();
                file_put_contents($logFile, "Stok barang ID $id_barang dikurangi $jumlah\n", FILE_APPEND);
            }
            $stmt->close();
        }
    } else {
        // Jika order_id tidak cocok pola, log saja
        file_put_contents($logFile, "Order ID tidak dikenali: $order_id\n", FILE_APPEND);
    }

    http_response_code(200);
    echo json_encode(['result' => 'OK']);
} catch (Exception $e) {
    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['result' => 'ERROR', 'message' => $e->getMessage()]);
}
