<?php
session_start();
include '../../route/koneksi.php';
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Midtrans\Config;
use Midtrans\Snap;

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Akses tidak diizinkan.'); window.location.href='../page/produk.php';</script>";
    exit;
}

$id_metode = $_POST['id_metode'] ?? null;
$selected_items = $_POST['items'] ?? [];
$tanggal_sewa = $_POST['tanggal_sewa'] ?? null;
$tanggal_kembali = $_POST['tanggal_kembali'] ?? null;

if (!$id_metode || empty($selected_items) || !$tanggal_sewa || !$tanggal_kembali) {
    echo "<script>alert('Data tidak lengkap.'); window.history.back();</script>";
    exit;
}

$ts_sewa = strtotime($tanggal_sewa);
$ts_kembali = strtotime($tanggal_kembali);
if (!$ts_sewa || !$ts_kembali || $ts_kembali <= $ts_sewa) {
    echo "<script>alert('Tanggal kembali harus lebih dari tanggal sewa.'); window.history.back();</script>";
    exit;
}
$lama_sewa = ceil(($ts_kembali - $ts_sewa) / (60 * 60 * 24));

$selected_ids = array_keys($selected_items);
$selected_ids_int = array_map('intval', $selected_ids);
$ids_placeholders = implode(',', array_fill(0, count($selected_ids_int), '?'));

$sql = "SELECT c.id AS cart_id, c.id_barang, c.jumlah, b.harga_sewa AS harga 
        FROM carts c
        JOIN barang b ON c.id_barang = b.id_barang
        WHERE c.id_penyewa = ? AND c.id IN ($ids_placeholders)";
$stmt = mysqli_prepare($koneksi, $sql);
$params = array_merge([$id_penyewa], $selected_ids_int);
$types = str_repeat('i', count($params));
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items_db = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items_db[$row['cart_id']] = $row;
}

if (count($items_db) !== count($selected_ids_int)) {
    echo "<script>alert('Data keranjang tidak valid.'); window.history.back();</script>";
    exit;
}

// Ambil tipe_metode dari database
$sqlMetode = "SELECT tipe_metode FROM metode_pembayaran WHERE id_metode = ?";
$stmtMetode = mysqli_prepare($koneksi, $sqlMetode);
mysqli_stmt_bind_param($stmtMetode, "i", $id_metode);
mysqli_stmt_execute($stmtMetode);
$resultMetode = mysqli_stmt_get_result($stmtMetode);
$metodeData = mysqli_fetch_assoc($resultMetode);
if (!$metodeData) {
    echo "<script>alert('Metode pembayaran tidak ditemukan.'); window.history.back();</script>";
    exit;
}
$tipe_metode = strtolower(trim($metodeData['tipe_metode']));

mysqli_begin_transaction($koneksi);

try {
    $total_harga = 0;
    foreach ($selected_ids_int as $cart_id) {
        $item = $items_db[$cart_id];
        $jumlah_pilih = (int)$selected_items[$cart_id];
        if ($jumlah_pilih <= 0 || $jumlah_pilih > $item['jumlah']) {
            throw new Exception("Jumlah barang tidak valid untuk cart ID: $cart_id");
        }
        $total_harga += $item['harga'] * $jumlah_pilih * $lama_sewa;
    }

    // Tentukan status transaksi berdasarkan tipe_metode
    $status_transaksi = $tipe_metode === 'langsung' ? 'menunggu konfirmasi pesanan' : 'belumbayar';

    $stmtTransaksi = mysqli_prepare($koneksi, "INSERT INTO transaksi (id_penyewa, total_harga_sewa, status, id_metode, tanggal_sewa, tanggal_kembali) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmtTransaksi, "idsiss", $id_penyewa, $total_harga, $status_transaksi, $id_metode, $tanggal_sewa, $tanggal_kembali);
    if (!mysqli_stmt_execute($stmtTransaksi)) {
        throw new Exception("Gagal menyimpan transaksi.");
    }
    $id_transaksi = mysqli_insert_id($koneksi);

    $stmtDetail = mysqli_prepare($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_barang, jumlah_barang, harga_satuan) VALUES (?, ?, ?, ?)");
    foreach ($selected_ids_int as $cart_id) {
        $item = $items_db[$cart_id];
        $id_barang = (int)$item['id_barang'];
        $jumlah = (int)$selected_items[$cart_id];
        $harga = (float)$item['harga'];
        mysqli_stmt_bind_param($stmtDetail, "iiid", $id_transaksi, $id_barang, $jumlah, $harga);
        if (!mysqli_stmt_execute($stmtDetail)) {
            throw new Exception("Gagal menyimpan detail transaksi.");
        }
    }

    $hapusSQL = "DELETE FROM carts WHERE id_penyewa = ? AND id IN ($ids_placeholders)";
    $hapusStmt = mysqli_prepare($koneksi, $hapusSQL);
    $hapusParams = array_merge([$id_penyewa], $selected_ids_int);
    $hapusTypes = str_repeat('i', count($hapusParams));
    mysqli_stmt_bind_param($hapusStmt, $hapusTypes, ...$hapusParams);
    if (!mysqli_stmt_execute($hapusStmt)) {
        throw new Exception("Gagal menghapus cart.");
    }

    if ($tipe_metode === 'langsung') {
        // Untuk metode langsung, commit dan redirect ke transaksi
        mysqli_commit($koneksi);
        header("Location: ../page/transaksi.php");
        exit;
    }

    // === MIDTRANS SNAP CONFIG (untuk metode online) ===
    Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
    Config::$isProduction = false;
    Config::$isSanitized = true;
    Config::$is3ds = true;

    $order_id = "TRX-" . $id_transaksi . "-" . time();

    // Simpan order_id ke database
    $stmtOrderId = mysqli_prepare($koneksi, "UPDATE transaksi SET order_id = ? WHERE id_transaksi = ?");
    mysqli_stmt_bind_param($stmtOrderId, "si", $order_id, $id_transaksi);
    if (!mysqli_stmt_execute($stmtOrderId)) {
        throw new Exception("Gagal menyimpan order_id.");
    }

    $transaction_details = [
        'order_id' => $order_id,
        'gross_amount' => $total_harga,
    ];

    $item_details = [];
    foreach ($selected_ids_int as $cart_id) {
        $item = $items_db[$cart_id];
        $jumlah = (int)$selected_items[$cart_id];
        $item_details[] = [
            'id' => (string)$cart_id,
            'price' => (float)$item['harga'],
            'quantity' => $jumlah * $lama_sewa,
            'name' => "Barang ID " . $item['id_barang'],
        ];
    }

    $params = [
        'transaction_details' => $transaction_details,
        'item_details' => $item_details,
        'customer_details' => [
            'id' => $id_penyewa,
        ],
    ];

    try {
        $snapToken = Snap::getSnapToken($params);
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo "<script>alert('Gagal generate Snap Token: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }

    // Simpan snap_token
    $stmtUpdate = mysqli_prepare($koneksi, "UPDATE transaksi SET snap_token = ? WHERE id_transaksi = ?");
    mysqli_stmt_bind_param($stmtUpdate, "si", $snapToken, $id_transaksi);
    mysqli_stmt_execute($stmtUpdate);

    mysqli_commit($koneksi);
    header("Location: ../page/pembayaran.php?id_transaksi=$id_transaksi&token=$snapToken");
    exit;

} catch (Exception $ex) {
    mysqli_rollback($koneksi);
    echo "<script>alert('Terjadi kesalahan: " . $ex->getMessage() . "'); window.history.back();</script>";
    exit;
}
?>
