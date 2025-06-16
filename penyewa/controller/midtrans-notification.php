<?php
// midtrans-notification.php

require '../../vendor/autoload.php';
include '../../route/koneksi.php';

use Midtrans\Config;
use Midtrans\Notification;

// Set konfigurasi Midtrans (gunakan server key sandbox / production yang sesuai)
Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
Config::$isProduction = false;  // true jika sudah di production
Config::$isSanitized = true;
Config::$is3ds = true;

// Terima notifikasi JSON dari Midtrans
$notif = new Notification();

// Ambil data notifikasi
$order_id = $notif->order_id;               // Contoh: TRX-12345-167xxx
$transaction_status = $notif->transaction_status;
$fraud_status = $notif->fraud_status;

// Cari transaksi berdasarkan order_id (asumsikan order_id unik di DB)
$sql = "SELECT * FROM transaksi WHERE order_id = ?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "s", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transaksi = mysqli_fetch_assoc($result);

if (!$transaksi) {
    http_response_code(404);
    echo json_encode(['error' => 'Transaksi tidak ditemukan']);
    exit;
}

$id_transaksi = $transaksi['id_transaksi'];

// Tentukan status baru transaksi berdasarkan status Midtrans
$status_baru = $transaksi['status']; // default pakai status lama dulu

if ($transaction_status == 'capture') {
    if ($fraud_status == 'challenge') {
        $status_baru = 'menunggu konfirmasi pembayaran'; // status challenge, perlu dicek manual
    } else if ($fraud_status == 'accept') {
        $status_baru = 'dikonfirmasi'; // pembayaran sukses
    }
} else if ($transaction_status == 'settlement') {
    $status_baru = 'dikonfirmasi'; // pembayaran settled sukses
} else if ($transaction_status == 'pending') {
    $status_baru = 'belumbayar'; // belum dibayar
} else if ($transaction_status == 'deny') {
    $status_baru = 'ditolak pembayaran'; // pembayaran ditolak
} else if ($transaction_status == 'expire') {
    $status_baru = 'batal'; // pembayaran expired
} else if ($transaction_status == 'cancel') {
    $status_baru = 'batal'; // pembayaran dibatalkan
}

// Update status transaksi jika berubah
if ($status_baru !== $transaksi['status']) {
    $sqlUpdate = "UPDATE transaksi SET status = ? WHERE id_transaksi = ?";
    $stmtUpdate = mysqli_prepare($koneksi, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "si", $status_baru, $id_transaksi);
    mysqli_stmt_execute($stmtUpdate);
}

// Kirim response sukses ke Midtrans
http_response_code(200);
echo json_encode(['result' => 'success']);
