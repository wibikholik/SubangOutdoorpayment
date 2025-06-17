<?php
session_start();
include '../route/koneksi.php';

// Validasi akses
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

// Validasi metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pengembalian.php");
    exit;
}

// Ambil input
$id_pengembalian = filter_input(INPUT_POST, 'id_pengembalian', FILTER_VALIDATE_INT);
$status_baru = $_POST['status_pengembalian'] ?? '';
$denda = floatval($_POST['denda'] ?? 0);
$catatan = trim($_POST['catatan'] ?? '');

// Validasi input
$status_valid = ['Selesai Dikembalikan', 'Ditolak Pengembalian'];
if (!$id_pengembalian || !in_array($status_baru, $status_valid)) {
    header("Location: pengembalian.php?error=invalid_input");
    exit;
}

// Ambil data pengembalian
$q = mysqli_query($koneksi, "SELECT * FROM pengembalian WHERE id_pengembalian = $id_pengembalian");
if (!$q) {
    die("Query pengembalian gagal: " . mysqli_error($koneksi));
}
$data_pengembalian = mysqli_fetch_assoc($q);
if (!$data_pengembalian) {
    header("Location: pengembalian.php?error=data_not_found");
    exit;
}
$id_transaksi = $data_pengembalian['id_transaksi'];

// ============================
// Generate ORDER ID jika belum ada dan denda > 0
// ============================
$order_id = $data_pengembalian['order_id'];
if ($denda > 0 && empty($order_id)) {
    $prefix = 'DND-' . date('Ymd') . '-';
    $qMax = mysqli_query($koneksi, "SELECT MAX(order_id) AS max_id FROM pengembalian WHERE order_id LIKE '$prefix%'");
    if (!$qMax) {
        die("Query max order_id gagal: " . mysqli_error($koneksi));
    }
    $rowMax = mysqli_fetch_assoc($qMax);
    $last_number = 1;

    if (!empty($rowMax['max_id'])) {
        $last_number = intval(substr($rowMax['max_id'], -4)) + 1;
    }

    $order_id = $prefix . str_pad($last_number, 4, '0', STR_PAD_LEFT);
}

// ============================
// Update data pengembalian (termasuk order_id jika ada)
// ============================
if (!empty($order_id)) {
    $stmt = mysqli_prepare($koneksi, "UPDATE pengembalian SET status_pengembalian = ?, denda = ?, catatan = ?, order_id = ? WHERE id_pengembalian = ?");
    mysqli_stmt_bind_param($stmt, 'sdssi', $status_baru, $denda, $catatan, $order_id, $id_pengembalian);
} else {
    $stmt = mysqli_prepare($koneksi, "UPDATE pengembalian SET status_pengembalian = ?, denda = ?, catatan = ? WHERE id_pengembalian = ?");
    mysqli_stmt_bind_param($stmt, 'sdsi', $status_baru, $denda, $catatan, $id_pengembalian);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// ============================
// Generate Snap Token jik  ada denda > 0
// ============================
if ($denda > 0 && !empty($order_id)) {
    require_once __DIR__ . '/../vendor/autoload.php'; // Perbaikan path autoload

    \Midtrans\Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    // Ambil data penyewa dari transaksi
    $qTrans = mysqli_query($koneksi, "
        SELECT t.id_transaksi, u.nama_penyewa AS nama, u.email, u.no_hp AS nomor_hp 
        FROM transaksi t 
        JOIN penyewa u ON t.id_penyewa = u.id_penyewa 
        WHERE t.id_transaksi = $id_transaksi
    ");
    if (!$qTrans) {
        die("Query transaksi gagal: " . mysqli_error($koneksi));
    }
    $dataTrans = mysqli_fetch_assoc($qTrans);
    if (!$dataTrans) {
        die("Data transaksi tidak ditemukan");
    }

    $params = [
        'transaction_details' => [
            'order_id' => $order_id,
            'gross_amount' => $denda
        ],
        'customer_details' => [
            'first_name' => $dataTrans['nama'],
            'email' => $dataTrans['email'],
            'phone' => $dataTrans['nomor_hp']
        ]
    ];

    try {
        $snapToken = \Midtrans\Snap::getSnapToken($params);
        mysqli_query($koneksi, "UPDATE pengembalian SET snap_token = '$snapToken' WHERE id_pengembalian = $id_pengembalian");
    } catch (Exception $e) {
        error_log("Gagal generate SnapToken: " . $e->getMessage());
    }
}

// ============================
// Update stok jika pengembalian selesai
// ============================
if ($status_baru === 'Selesai Dikembalikan') {
    $qDetail = mysqli_query($koneksi, "SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = $id_transaksi");
    if (!$qDetail) {
        die("Query detail transaksi gagal: " . mysqli_error($koneksi));
    }
    while ($row = mysqli_fetch_assoc($qDetail)) {
        $id_barang = $row['id_barang'];
        $jumlah = $row['jumlah_barang'];
        mysqli_query($koneksi, "UPDATE barang SET stok = stok + $jumlah WHERE id_barang = $id_barang");
    }
}

// ============================
// Update status transaksi
// ============================
mysqli_query($koneksi, "UPDATE transaksi SET status = '$status_baru' WHERE id_transaksi = $id_transaksi");

// ============================
// Update status pembayaran jika ada
// ============================
$cekBayar = mysqli_query($koneksi, "SELECT id_pembayaran FROM pembayaran WHERE id_transaksi = $id_transaksi");
if (mysqli_num_rows($cekBayar) > 0) {
    $rowBayar = mysqli_fetch_assoc($cekBayar);
    $id_pembayaran = $rowBayar['id_pembayaran'];
    mysqli_query($koneksi, "UPDATE pembayaran SET status_pembayaran = '$status_baru' WHERE id_pembayaran = $id_pembayaran");
}

// ============================
// Redirect ke halaman detail
// ============================
header("Location: detail_pengembalian.php?id_pengembalian=$id_pengembalian&status=berhasil");
exit;
?>
