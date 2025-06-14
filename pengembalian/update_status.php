<?php
session_start();
include '../route/koneksi.php';

// Validasi akses
if (!isset($_SESSION['user_id'], $_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header('Location: ../login.php');
    exit;
}

// Pastikan request menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pengembalian.php');
    exit;
}

// Validasi CSRF token
if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token tidak valid.');
}

// Ambil input dan validasi
$id_pengembalian = filter_input(INPUT_POST, 'id_pengembalian', FILTER_VALIDATE_INT);
$id_transaksi = filter_input(INPUT_POST, 'id_transaksi', FILTER_VALIDATE_INT);
$status_baru = trim($_POST['status_baru'] ?? '');

if (!$id_pengembalian || !$id_transaksi || $status_baru === '') {
    header('Location: pengembalian.php?error=invalid_input');
    exit;
}

// Validasi status pengembalian yang diterima
$status_valid = ['Menunggu Konfirmasi Pengembalian', 'Selesai Dikembalikan', 'Ditolak Pengembalian'];
if (!in_array($status_baru, $status_valid)) {
    header('Location: pengembalian.php?error=invalid_status');
    exit;
}

// Update status pengembalian
$update_pengembalian = mysqli_prepare($koneksi, "UPDATE pengembalian SET status_pengembalian = ? WHERE id_pengembalian = ?");
mysqli_stmt_bind_param($update_pengembalian, 'si', $status_baru, $id_pengembalian);
mysqli_stmt_execute($update_pengembalian);
mysqli_stmt_close($update_pengembalian);

// Jika status pengembalian "Selesai Dikembalikan" update stok barang
if ($status_baru === 'Selesai Dikembalikan') {
    $query = "SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_transaksi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $id_barang = $row['id_barang'];
        $jumlah_kembali = $row['jumlah_barang'];

        $update_stok = mysqli_prepare($koneksi, "UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
        mysqli_stmt_bind_param($update_stok, 'ii', $jumlah_kembali, $id_barang);
        mysqli_stmt_execute($update_stok);
        mysqli_stmt_close($update_stok);
    }

    mysqli_stmt_close($stmt);
}

// Update status transaksi dan pembayaran mengikuti status_pengembalian
$status_transaksi = $status_baru;
$status_pembayaran = $status_baru;

// Update status transaksi
$update_transaksi = mysqli_prepare($koneksi, "UPDATE transaksi SET status = ? WHERE id_transaksi = ?");
mysqli_stmt_bind_param($update_transaksi, 'si', $status_transaksi, $id_transaksi);
mysqli_stmt_execute($update_transaksi);
mysqli_stmt_close($update_transaksi);

// Cari id_pembayaran berdasarkan id_transaksi
$query_pembayaran = "SELECT id_pembayaran FROM pembayaran WHERE id_transaksi = ?";
$stmt_pembayaran = mysqli_prepare($koneksi, $query_pembayaran);
mysqli_stmt_bind_param($stmt_pembayaran, 'i', $id_transaksi);
mysqli_stmt_execute($stmt_pembayaran);
$result_pembayaran = mysqli_stmt_get_result($stmt_pembayaran);
$pembayaran = mysqli_fetch_assoc($result_pembayaran);
mysqli_stmt_close($stmt_pembayaran);

// Update status pembayaran jika data pembayaran ditemukan
if ($pembayaran) {
    $id_pembayaran = $pembayaran['id_pembayaran'];
    $update_pembayaran = mysqli_prepare($koneksi, "UPDATE pembayaran SET status_pembayaran = ? WHERE id_pembayaran = ?");
    mysqli_stmt_bind_param($update_pembayaran, 'si', $status_pembayaran, $id_pembayaran);
    mysqli_stmt_execute($update_pembayaran);
    mysqli_stmt_close($update_pembayaran);
}

// Redirect kembali dengan pesan sukses
header("Location: pengembalian.php?status=berhasil");
exit;
