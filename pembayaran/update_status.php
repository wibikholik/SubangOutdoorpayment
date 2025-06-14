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
    header('Location: pembayaran.php');
    exit;
}

// Ambil input dan validasi
$id_pembayaran = filter_input(INPUT_POST, 'id_pembayaran', FILTER_VALIDATE_INT);
$id_transaksi = filter_input(INPUT_POST, 'id_transaksi', FILTER_VALIDATE_INT);
$status_baru = trim($_POST['status_baru'] ?? '');

if (!$id_pembayaran || !$id_transaksi || $status_baru === '') {
    header('Location: pembayaran.php?error=invalid_input');
    exit;
}

// Daftar status pembayaran yang valid (case sensitive)
$status_valid = [
    'Menunggu Konfirmasi Pembayaran',
    'dikonfirmasi Pembayaran Silahkan AmbilBarang',
    'Ditolak Pembayaran',
    'selesai Pembayaran'
];

// Validasi status
if (!in_array($status_baru, $status_valid, true)) {
    header('Location: pembayaran.php?error=invalid_status');
    exit;
}

// Update status di tabel pembayaran
$stmt1 = $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = ? WHERE id_pembayaran = ?");
$stmt1->bind_param('si', $status_baru, $id_pembayaran);
$stmt1->execute();
$stmt1->close();

// Update status di tabel transaksi
$stmt2 = $koneksi->prepare("UPDATE transaksi SET status = ? WHERE id_transaksi = ?");
$stmt2->bind_param('si', $status_baru, $id_transaksi);
$stmt2->execute();
$stmt2->close();

header("Location: pembayaran.php?status=berhasil");
exit;
