<?php
session_start();
include '../../route/koneksi.php';
require '../../vendor/autoload.php';

use Midtrans\Config;
use Midtrans\Notification;

// Konfigurasi Midtrans
Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
Config::$isProduction = false;
Config::$isSanitized = true;
Config::$is3ds = true;

// Cek request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

// Ambil dan log input
$input = file_get_contents('php://input');
$data = json_decode($input, true);
file_put_contents('../../midtrans.log', date('Y-m-d H:i:s') . " - Webhook received: " . $input . PHP_EOL, FILE_APPEND);

// Validasi signature key
$signatureKeyHeader = $_SERVER['HTTP_X_MIDTRANS_SIGNATURE_KEY'] ?? '';
$orderId = $data['order_id'] ?? '';
$statusCode = $data['status_code'] ?? '';
$grossAmount = $data['gross_amount'] ?? '';
$expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . Config::$serverKey);

if ($signatureKeyHeader !== $expectedSignature) {
    http_response_code(403);
    file_put_contents('../../midtrans.log', date('Y-m-d H:i:s') . " - Invalid signature: expected=$expectedSignature, received=$signatureKeyHeader" . PHP_EOL, FILE_APPEND);
    exit("Invalid signature key");
}

try {
    $notification = new Notification();

    $transactionStatus = $notification->transaction_status ?? null;
    $fraudStatus = $notification->fraud_status ?? null;
    $orderId = $notification->order_id ?? null;

    if (!$orderId) {
        throw new Exception("order_id kosong atau tidak diterima dari Midtrans");
    }

    $parts = explode('-', $orderId);
    if (count($parts) < 2) {
        throw new Exception("Format order_id tidak valid: $orderId");
    }

    $id_transaksi = intval($parts[1]);
    if ($id_transaksi <= 0) {
        throw new Exception("id_transaksi hasil parsing tidak valid dari order_id: $orderId");
    }

    file_put_contents('../../midtrans.log', "Order ID: $orderId | Parsed id_transaksi: $id_transaksi" . PHP_EOL, FILE_APPEND);

    // Tentukan status baru berdasarkan status transaksi
    switch ($transactionStatus) {
        case 'capture':
            if ($fraudStatus === 'challenge') {
                $status_baru = 'pending';
            } elseif ($fraudStatus === 'accept' || $fraudStatus === null) {
                $status_baru = 'Dikonfirmasi (Silahkan Ambil Barang)';
            } else {
                $status_baru = 'pending';
            }
            break;
        case 'settlement':
            $status_baru = 'dikonfirmasi pembayaran silahkan ambilbarang)';
            break;
        case 'pending':
            $status_baru = 'pending';
            break;
        case 'deny':
        case 'cancel':
        case 'expire':
            $status_baru = 'gagal';
            break;
        default:
            $status_baru = 'pending';
    }

    // Update ke database
    $stmt = $koneksi->prepare("UPDATE transaksi SET status = ? WHERE id_transaksi = ?");
    if (!$stmt) {
        file_put_contents('../../midtrans.log', "Prepare failed: " . $koneksi->error . PHP_EOL, FILE_APPEND);
        throw new Exception("Prepare query gagal: " . $koneksi->error);
    }

    $stmt->bind_param("si", $status_baru, $id_transaksi);
    if (!$stmt->execute()) {
        file_put_contents('../../midtrans.log', "Execute failed: " . $stmt->error . PHP_EOL, FILE_APPEND);
        throw new Exception("Eksekusi update gagal: " . $stmt->error);
    }

    file_put_contents('../../midtrans.log', "Update sukses: status=$status_baru untuk id_transaksi=$id_transaksi" . PHP_EOL, FILE_APPEND);

    http_response_code(200);
    echo "OK";
    exit;

} catch (Exception $e) {
    file_put_contents('../../midtrans.log', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo "Error: " . $e->getMessage();
    exit;
}
