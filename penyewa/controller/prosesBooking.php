<?php
session_start();
include '../../route/koneksi.php';
require '../../vendor/autoload.php';

use Midtrans\Config;
use Midtrans\Snap;

function refValues($arr){
    $refs = [];
    foreach($arr as $key => $value){
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Akses tidak diizinkan.'); window.location.href='../page/produk.php';</script>";
    exit;
}

// Ambil data form
$id_tipe = $_POST['id_tipe'] ?? null;
$selected_items = $_POST['items'] ?? []; // Format: [id_cart => jumlah_dipesan]
$tanggal_sewa = $_POST['tanggal_sewa'] ?? null;
$tanggal_kembali = $_POST['tanggal_kembali'] ?? null;

// Validasi input
if (!$id_tipe || empty($selected_items) || !$tanggal_sewa || !$tanggal_kembali) {
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
if (count($selected_ids) === 0) {
    echo "<script>alert('Tidak ada item yang dipilih.'); window.history.back();</script>";
    exit;
}

$selected_ids_int = array_map('intval', $selected_ids);
$placeholders = implode(',', array_fill(0, count($selected_ids_int), '?'));

// Ambil data barang di cart sesuai user dan id_cart yang dipilih
$sql = "SELECT c.id AS cart_id, c.id_barang, c.jumlah AS stok, b.harga_sewa AS harga
        FROM carts c
        JOIN barang b ON c.id_barang = b.id_barang
        WHERE c.id_penyewa = ? AND c.id IN ($placeholders)";
$stmt = mysqli_prepare($koneksi, $sql);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($koneksi));
}
$params = array_merge([$id_penyewa], $selected_ids_int);
$types = str_repeat('i', count($params));
mysqli_stmt_bind_param($stmt, $types, ...refValues($params));
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

// Ambil metode pembayaran untuk tipe yang dipilih
$sqlMetode = "SELECT id_metode FROM metode_pembayaran WHERE id_tipe = ?";
$stmtMetode = mysqli_prepare($koneksi, $sqlMetode);
mysqli_stmt_bind_param($stmtMetode, "i", $id_tipe);
mysqli_stmt_execute($stmtMetode);
$resMetode = mysqli_stmt_get_result($stmtMetode);

$metodeList = [];
while ($row = mysqli_fetch_assoc($resMetode)) {
    $metodeList[] = $row['id_metode'];
}
$metodeCount = count($metodeList);

// Set id_metode null jika banyak metode, agar tidak error FK
$id_metode = ($metodeCount === 1) ? $metodeList[0] : null;

// Ambil tipe metode pembayaran (nama_tipe)
$sqlTipe = "SELECT nama_tipe FROM tipe_metode WHERE id_tipe = ?";
$stmtTipe = mysqli_prepare($koneksi, $sqlTipe);
mysqli_stmt_bind_param($stmtTipe, "i", $id_tipe);
mysqli_stmt_execute($stmtTipe);
$resTipe = mysqli_stmt_get_result($stmtTipe);
$tipeData = mysqli_fetch_assoc($resTipe);
if (!$tipeData) {
    echo "<script>alert('Tipe metode tidak ditemukan.'); window.history.back();</script>";
    exit;
}
$tipe_metode = strtolower(trim($tipeData['nama_tipe']));

// Ambil data penyewa (customer)
$sqlUser = "SELECT nama_penyewa, email, no_hp FROM penyewa WHERE id_penyewa = ?";
$stmtUser = mysqli_prepare($koneksi, $sqlUser);
mysqli_stmt_bind_param($stmtUser, "i", $id_penyewa);
mysqli_stmt_execute($stmtUser);
$resUser = mysqli_stmt_get_result($stmtUser);
$dataUser = mysqli_fetch_assoc($resUser);

// Tentukan status awal transaksi sesuai tipe metode
switch ($tipe_metode) {
    case 'langsung':
        $status_transaksi = 'menunggu konfirmasi pesanan';
        break;
    case 'transfer langsung':
    case 'online':
    default:
        $status_transaksi = 'belumbayar';
        break;
}

// Mulai transaksi DB
mysqli_begin_transaction($koneksi);

try {
    $total_harga = 0;
    foreach ($selected_ids_int as $cart_id) {
        $item = $items_db[$cart_id];
        $jumlah_pilih = (int)$selected_items[$cart_id];
        if ($jumlah_pilih <= 0 || $jumlah_pilih > $item['stok']) {
            throw new Exception("Jumlah barang tidak valid untuk cart ID: $cart_id");
        }
        $total_harga += $item['harga'] * $jumlah_pilih * $lama_sewa;
    }

    if ($id_metode === null) {
        // Insert tanpa id_metode (NULL)
        $stmtTransaksi = mysqli_prepare($koneksi, "INSERT INTO transaksi (id_penyewa, total_harga_sewa, status, id_metode, tanggal_sewa, tanggal_kembali, id_tipe) VALUES (?, ?, ?, NULL, ?, ?, ?)");
        if (!$stmtTransaksi) {
            throw new Exception("Prepare transaksi gagal: " . mysqli_error($koneksi));
        }
        mysqli_stmt_bind_param($stmtTransaksi, "idsssi", $id_penyewa, $total_harga, $status_transaksi, $tanggal_sewa, $tanggal_kembali, $id_tipe);

    } else {
        // Insert dengan id_metode
        $stmtTransaksi = mysqli_prepare($koneksi, "INSERT INTO transaksi (id_penyewa, total_harga_sewa, status, id_metode, tanggal_sewa, tanggal_kembali, id_tipe) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmtTransaksi) {
            throw new Exception("Prepare transaksi gagal: " . mysqli_error($koneksi));
        }
        mysqli_stmt_bind_param($stmtTransaksi, "idsissi", $id_penyewa, $total_harga, $status_transaksi, $id_metode, $tanggal_sewa, $tanggal_kembali, $id_tipe);
    }
    mysqli_stmt_execute($stmtTransaksi);
    $id_transaksi = mysqli_insert_id($koneksi);

    // Insert detail transaksi
    $stmtDetail = mysqli_prepare($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_barang, jumlah_barang, harga_satuan) VALUES (?, ?, ?, ?)");
    foreach ($selected_ids_int as $cart_id) {
        $item = $items_db[$cart_id];
        $id_barang = (int)$item['id_barang'];
        $jumlah = (int)$selected_items[$cart_id];
        $harga = (float)$item['harga'];
        mysqli_stmt_bind_param($stmtDetail, "iiid", $id_transaksi, $id_barang, $jumlah, $harga);
        mysqli_stmt_execute($stmtDetail);
    }

    // Hapus item cart yang sudah dibooking
    $hapusSQL = "DELETE FROM carts WHERE id_penyewa = ? AND id IN ($placeholders)";
    $hapusStmt = mysqli_prepare($koneksi, $hapusSQL);
    $hapusParams = array_merge([$id_penyewa], $selected_ids_int);
    $hapusTypes = str_repeat('i', count($hapusParams));
    mysqli_stmt_bind_param($hapusStmt, $hapusTypes, ...refValues($hapusParams));
    mysqli_stmt_execute($hapusStmt);

    mysqli_commit($koneksi);

    // Redirect sesuai tipe metode pembayaran
    if ($tipe_metode === 'langsung') {
        header("Location: ../page/transaksi.php");
        exit;
    }

    if ($tipe_metode === 'transfer langsung') {
        if ($id_metode === null) {
            header("Location: ../page/pembayaran_upload.php?id_transaksi=$id_transaksi&pilih=1");
            exit;
        } else {
            header("Location: ../page/pembayaran_upload.php?id_transaksi=$id_transaksi");
            exit;
        }
    }

    if ($tipe_metode === 'online') {
        // Setup Midtrans config
        Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $order_id = "TRX-$id_transaksi-" . time();

        // Update order_id di transaksi
        $stmtOrderId = mysqli_prepare($koneksi, "UPDATE transaksi SET order_id = ? WHERE id_transaksi = ?");
        mysqli_stmt_bind_param($stmtOrderId, "si", $order_id, $id_transaksi);
        mysqli_stmt_execute($stmtOrderId);

        $item_details = [];
        foreach ($selected_ids_int as $cart_id) {
            $item = $items_db[$cart_id];
            $jumlah = (int)$selected_items[$cart_id];
            $item_details[] = [
                'id' => (string)$cart_id,
                'price' => (float)$item['harga'],
                'quantity' => $jumlah * $lama_sewa,
                'name' => "Barang ID " . $item['id_barang']
            ];
        }

        $params = [
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => $total_harga
            ],
            'item_details' => $item_details,
            'customer_details' => [
                'first_name' => $dataUser['nama_penyewa'],
                'email' => $dataUser['email'],
                'phone' => $dataUser['no_hp']
            ]
        ];

        $snapToken = Snap::getSnapToken($params);

        $stmtSnap = mysqli_prepare($koneksi, "UPDATE transaksi SET snap_token = ? WHERE id_transaksi = ?");
        mysqli_stmt_bind_param($stmtSnap, "si", $snapToken, $id_transaksi);
        mysqli_stmt_execute($stmtSnap);

        header("Location: ../page/pembayaran.php?id_transaksi=$id_transaksi&token=$snapToken");
        exit;
    }

    // Default redirect
    header("Location: ../page/transaksi.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    echo "<script>alert('Terjadi kesalahan: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    exit;
}
