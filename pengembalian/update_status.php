<?php
session_start();
include '../route/koneksi.php';

// Validasi akses
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

// Pastikan request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pengembalian.php");
    exit;
}

// Ambil dan validasi input
$id_pengembalian = filter_input(INPUT_POST, 'id_pengembalian', FILTER_VALIDATE_INT);
$status_baru = $_POST['status_pengembalian'] ?? '';
$denda = floatval($_POST['denda'] ?? 0);
$catatan = trim($_POST['catatan'] ?? '');

// Validasi input penting
$status_valid = ['Selesai Dikembalikan', 'Ditolak Pengembalian'];
if (!$id_pengembalian || !in_array($status_baru, $status_valid)) {
    header("Location: pengembalian.php?error=invalid_input");
    exit;
}

// Ambil data pengembalian dan transaksi terkait
$q = mysqli_query($koneksi, "SELECT * FROM pengembalian WHERE id_pengembalian = $id_pengembalian");
$data_pengembalian = mysqli_fetch_assoc($q);
if (!$data_pengembalian) {
    header("Location: pengembalian.php?error=data_not_found");
    exit;
}
$id_transaksi = $data_pengembalian['id_transaksi'];
$order_id = $data_pengembalian['order_id'];

// Generate order_id jika ada denda dan order_id belum ada
if ($denda > 0 && empty($order_id)) {
    $order_id = "DENDA-$id_pengembalian-" . time();

    // Update order_id di tabel pengembalian
    $order_id_escaped = mysqli_real_escape_string($koneksi, $order_id);
    if (!mysqli_query($koneksi, "UPDATE pengembalian SET order_id = '$order_id_escaped' WHERE id_pengembalian = $id_pengembalian")) {
        die("Error update order_id pengembalian: " . mysqli_error($koneksi));
    }

    // Insert/update data pembayaran denda
    $cekPembayaran = mysqli_query($koneksi, "SELECT * FROM pembayaran WHERE id_transaksi = $id_transaksi");
    if (mysqli_num_rows($cekPembayaran) > 0) {
        mysqli_query($koneksi, "UPDATE pembayaran SET denda = $denda, order_id = '$order_id_escaped', status_pembayaran = 'Menunggu Pembayaran' WHERE id_transaksi = $id_transaksi");
    } else {
        mysqli_query($koneksi, "INSERT INTO pembayaran (id_transaksi, denda, order_id, status_pembayaran) VALUES ($id_transaksi, $denda, '$order_id_escaped', 'Menunggu Pembayaran')");
    }
}

// Update data pengembalian (status, denda, catatan)
$stmt = mysqli_prepare($koneksi, "UPDATE pengembalian SET status_pengembalian = ?, denda = ?, catatan = ? WHERE id_pengembalian = ?");
mysqli_stmt_bind_param($stmt, 'sdsi', $status_baru, $denda, $catatan, $id_pengembalian);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Jika status pengembalian selesai → update stok barang
if ($status_baru === 'Selesai Dikembalikan') {
    $qDetail = mysqli_query($koneksi, "SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = $id_transaksi");
    while ($row = mysqli_fetch_assoc($qDetail)) {
        $id_barang = $row['id_barang'];
        $jumlah = $row['jumlah_barang'];
        mysqli_query($koneksi, "UPDATE barang SET stok = stok + $jumlah WHERE id_barang = $id_barang");
    }
}

// Update status transaksi sesuai status pengembalian
mysqli_query($koneksi, "UPDATE transaksi SET status = '$status_baru' WHERE id_transaksi = $id_transaksi");

// Update status pembayaran jika ada
$cekBayar = mysqli_query($koneksi, "SELECT id_pembayaran FROM pembayaran WHERE id_transaksi = $id_transaksi");
if (mysqli_num_rows($cekBayar) > 0) {
    $rowBayar = mysqli_fetch_assoc($cekBayar);
    $id_pembayaran = $rowBayar['id_pembayaran'];
    mysqli_query($koneksi, "UPDATE pembayaran SET status_pembayaran = '$status_baru' WHERE id_pembayaran = $id_pembayaran");
}

// Redirect kembali ke halaman detail dengan pesan sukses
header("Location: detail_pengembalian.php?id_pengembalian=$id_pengembalian&status=berhasil");
exit;
?>
