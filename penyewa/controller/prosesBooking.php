<?php
session_start(); 
include '../../route/koneksi.php'; // Menghubungkan ke database
require '../../vendor/autoload.php'; // Autoload library (Midtrans SDK)

use Midtrans\Config;
use Midtrans\Snap;

// Fungsi untuk mengubah array menjadi array referensi (untuk call_user_func_array dan bind_param)
function refValues($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

// Cek apakah user sudah login, jika belum redirect ke halaman login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

// Ambil user id penyewa dari session
$id_penyewa = $_SESSION['user_id'];

// Cek apakah penyewa diblokir
$cekStatus = mysqli_prepare($koneksi, "SELECT status FROM penyewa WHERE id_penyewa = ?");
mysqli_stmt_bind_param($cekStatus, "i", $id_penyewa);
mysqli_stmt_execute($cekStatus);
$resultStatus = mysqli_stmt_get_result($cekStatus);
$dataStatus = mysqli_fetch_assoc($resultStatus);

if ($dataStatus && $dataStatus['status'] === 'diblokir') {
    echo "<script>
        alert('Akun Anda telah diblokir dan tidak dapat melakukan transaksi.');
        window.location.href='../page/produk.php';
    </script>";
    exit;
}

// Pastikan request method adalah POST, jika bukan maka akses tidak diizinkan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Akses tidak diizinkan.'); window.location.href='../page/produk.php';</script>";
    exit;
}

// Ambil data dari form POST (tipe pembayaran, barang yang dipilih, tanggal sewa dan kembali)
$id_tipe = $_POST['id_tipe'] ?? null;
$selected_items = $_POST['items'] ?? [];
$tanggal_sewa = $_POST['tanggal_sewa'] ?? null;
$tanggal_kembali = $_POST['tanggal_kembali'] ?? null;

// Validasi data input, jika ada yang kosong langsung hentikan proses
if (!$id_tipe || empty($selected_items) || !$tanggal_sewa || !$tanggal_kembali) {
    echo "<script>alert('Data tidak lengkap.'); window.history.back();</script>";
    exit;
}

// Konversi tanggal ke timestamp untuk validasi dan hitung lama sewa
$ts_sewa = strtotime($tanggal_sewa);
$ts_kembali = strtotime($tanggal_kembali);
// Cek tanggal kembali harus lebih besar dari tanggal sewa
if (!$ts_sewa || !$ts_kembali || $ts_kembali <= $ts_sewa) {
    echo "<script>alert('Tanggal kembali harus lebih dari tanggal sewa.'); window.history.back();</script>";
    exit;
}

$lama_sewa = ceil(($ts_kembali - $ts_sewa) / (60 * 60 * 24)); // Hitung lama sewa dalam hari

// Ambil ID cart yang dipilih (key dari selected_items)
$selected_ids = array_keys($selected_items);
$selected_ids_int = array_map('intval', $selected_ids);

// Membuat placeholder untuk prepared statement sesuai jumlah id cart
$placeholders = implode(',', array_fill(0, count($selected_ids_int), '?'));

// Query untuk mengambil data cart dan barang terkait dari database
$sql = "SELECT c.id AS cart_id, c.id_barang, c.jumlah AS jumlah_cart, b.harga_sewa AS harga, b.stok
        FROM carts c
        JOIN barang b ON c.id_barang = b.id_barang
        WHERE c.id_penyewa = ? AND c.id IN ($placeholders)";
$stmt = mysqli_prepare($koneksi, $sql);
$params = array_merge([$id_penyewa], $selected_ids_int);
$types = str_repeat('i', count($params));
mysqli_stmt_bind_param($stmt, $types, ...refValues($params));
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Simpan hasil query ke array items_db, key-nya id cart
$items_db = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items_db[$row['cart_id']] = $row;
}

// Cek apakah data cart di database sesuai dengan yang dipilih user
if (count($items_db) !== count($selected_ids_int)) {
    echo "<script>alert('Data keranjang tidak valid.'); window.history.back();</script>";
    exit;
}

// Ambil daftar metode pembayaran berdasarkan tipe
$sqlMetode = "SELECT id_metode FROM metode_pembayaran WHERE id_tipe = ?";
$stmtMetode = mysqli_prepare($koneksi, $sqlMetode);
mysqli_stmt_bind_param($stmtMetode, "i", $id_tipe);
mysqli_stmt_execute($stmtMetode);
$resMetode = mysqli_stmt_get_result($stmtMetode);
$metodeList = [];
while ($row = mysqli_fetch_assoc($resMetode)) {
    $metodeList[] = $row['id_metode'];
}
// Jika hanya ada satu metode, langsung pakai id metode itu, kalau tidak null (pilihan ganda)
$id_metode = (count($metodeList) === 1) ? $metodeList[0] : null;

// Ambil nama tipe metode pembayaran (misal: langsung, transfer langsung, online)
$sqlTipe = "SELECT nama_tipe FROM tipe_metode WHERE id_tipe = ?";
$stmtTipe = mysqli_prepare($koneksi, $sqlTipe);
mysqli_stmt_bind_param($stmtTipe, "i", $id_tipe);
mysqli_stmt_execute($stmtTipe);
$resTipe = mysqli_stmt_get_result($stmtTipe);
$tipeData = mysqli_fetch_assoc($resTipe);
$tipe_metode = strtolower(trim($tipeData['nama_tipe'] ?? ''));

// Ambil data user penyewa untuk kebutuhan customer detail di payment gateway
$sqlUser = "SELECT nama_penyewa, email, no_hp FROM penyewa WHERE id_penyewa = ?";
$stmtUser = mysqli_prepare($koneksi, $sqlUser);
mysqli_stmt_bind_param($stmtUser, "i", $id_penyewa);
mysqli_stmt_execute($stmtUser);
$resUser = mysqli_stmt_get_result($stmtUser);
$dataUser = mysqli_fetch_assoc($resUser);

// Tentukan status transaksi awal berdasarkan tipe pembayaran
$status_transaksi = match ($tipe_metode) {
    'langsung' => 'menunggu konfirmasi pesanan',
    'transfer langsung', 'online' => 'belumbayar',
    default => 'belumbayar'
};

mysqli_begin_transaction($koneksi); // Mulai transaksi MySQL (agar commit/rollback bisa dilakukan)

try {
    $total_harga = 0;
    // Validasi jumlah barang yang dipilih dan hitung total harga sewa
    foreach ($selected_ids_int as $cart_id) {
        $item = $items_db[$cart_id];
        $jumlah_pilih = (int)$selected_items[$cart_id]['jumlah'];
        if ($jumlah_pilih <= 0 || $jumlah_pilih > $item['jumlah_cart']) {
            throw new Exception("Jumlah barang tidak valid untuk cart ID: $cart_id");
        }
        if ($item['stok'] < $jumlah_pilih) {
            throw new Exception("Stok tidak cukup untuk Barang ID {$item['id_barang']}");
        }
        // Total harga = harga per hari * jumlah * lama sewa
        $total_harga += $item['harga'] * $jumlah_pilih * $lama_sewa;
    }

    // Insert data transaksi ke tabel transaksi
    $query = "INSERT INTO transaksi (id_penyewa, total_harga_sewa, status, id_metode, tanggal_sewa, tanggal_kembali, id_tipe)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "iissssi", $id_penyewa, $total_harga, $status_transaksi, $id_metode, $tanggal_sewa, $tanggal_kembali, $id_tipe);
    mysqli_stmt_execute($stmt);
    $id_transaksi = mysqli_insert_id($koneksi); // Ambil id transaksi baru

    // Insert detail tiap barang ke detail_transaksi & update stok barang
    $stmtDetail = mysqli_prepare($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_barang, jumlah_barang, harga_satuan) VALUES (?, ?, ?, ?)");
    foreach ($selected_ids_int as $cart_id) {
        $item = $items_db[$cart_id];
        $id_barang = (int)$item['id_barang'];
        $jumlah = (int)$selected_items[$cart_id]['jumlah'];
        $harga = (float)$item['harga'];

        mysqli_stmt_bind_param($stmtDetail, "iiid", $id_transaksi, $id_barang, $jumlah, $harga);
        mysqli_stmt_execute($stmtDetail);

        // Kurangi stok barang di tabel barang
        $stmtStok = mysqli_prepare($koneksi, "UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
        mysqli_stmt_bind_param($stmtStok, "ii", $jumlah, $id_barang);
        mysqli_stmt_execute($stmtStok);
    }

    // Hapus data cart yang sudah dibooking oleh user
    $hapusSQL = "DELETE FROM carts WHERE id_penyewa = ? AND id IN ($placeholders)";
    $hapusStmt = mysqli_prepare($koneksi, $hapusSQL);
    $hapusParams = array_merge([$id_penyewa], $selected_ids_int);
    $hapusTypes = str_repeat('i', count($hapusParams));
    mysqli_stmt_bind_param($hapusStmt, $hapusTypes, ...refValues($hapusParams));
    mysqli_stmt_execute($hapusStmt);

    mysqli_commit($koneksi); // Commit transaksi MySQL

    // Jika metode pembayaran langsung (cod)
    if ($tipe_metode === 'langsung') {
        header("Location: ../page/transaksi.php");
        exit;
    }

    // Jika metode pembayaran transfer langsung
    if ($tipe_metode === 'transfer langsung') {
        $url = ($id_metode === null) ? "pembayaran_upload.php?id_transaksi=$id_transaksi&pilih=1" : "pembayaran_upload.php?id_transaksi=$id_transaksi";
        header("Location: ../page/$url");
        exit;
    }

    // Jika metode pembayaran online via Midtrans
    if ($tipe_metode === 'online') {
        // Konfigurasi Midtrans (server key, mode sandbox, dll)
        Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $order_id = "TRX-$id_transaksi-" . time();

        // Simpan order_id di database untuk referensi transaksi
        $stmtOrderId = mysqli_prepare($koneksi, "UPDATE transaksi SET order_id = ? WHERE id_transaksi = ?");
        mysqli_stmt_bind_param($stmtOrderId, "si", $order_id, $id_transaksi);
        mysqli_stmt_execute($stmtOrderId);

        // Buat detail item untuk Midtrans, kuantitas dikali lama sewa
        $item_details = [];
        foreach ($selected_ids_int as $cart_id) {
            $item = $items_db[$cart_id];
            $jumlah = (int)$selected_items[$cart_id]['jumlah'];
            $item_details[] = [
                'id' => (string)$cart_id,
                'price' => (float)$item['harga'],
                'quantity' => $jumlah * $lama_sewa,
                'name' => "Barang ID " . $item['id_barang']
            ];
        }

        // Set parameter transaksi dan data pelanggan untuk Midtrans
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

        // Ambil token pembayaran Snap Midtrans
        $snapToken = Snap::getSnapToken($params);

        // Simpan snap_token di database transaksi
        $stmtSnap = mysqli_prepare($koneksi, "UPDATE transaksi SET snap_token = ? WHERE id_transaksi = ?");
        mysqli_stmt_bind_param($stmtSnap, "si", $snapToken, $id_transaksi);
        mysqli_stmt_execute($stmtSnap);

        // Redirect ke halaman pembayaran dengan token snap
        header("Location: ../page/pembayaran.php?id_transaksi=$id_transaksi&token=$snapToken");
        exit;
    }

    // Jika tidak ada kondisi khusus, redirect ke halaman transaksi
    header("Location: ../page/transaksi.php");
    exit;

} catch (Exception $e) {
    // Jika ada error, rollback transaksi dan tampilkan pesan error
    mysqli_rollback($koneksi);
    echo "<script>alert('Terjadi kesalahan: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    exit;
}
