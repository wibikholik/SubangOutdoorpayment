<?php
session_start();
include '../route/koneksi.php';

// Pastikan session valid
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// Ambil ID penyewa yang ingin dihapus
$id_penyewa = $_GET['id_penyewa'];

// Cek apakah penyewa memiliki transaksi
$query = "SELECT COUNT(*) FROM transaksi WHERE id_penyewa = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $transaction_count);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Jika penyewa memiliki transaksi, tampilkan pesan error
if ($transaction_count > 0) {
    echo "<script>alert('Penyewa ini memiliki transaksi yang sudah dilakukan. Data penyewa tidak dapat dihapus.');</script>";
    echo "<script>window.location.href = 'penyewa.php';</script>";
    exit;
}

// Jika tidak ada transaksi, lanjutkan untuk menghapus penyewa
$query = "DELETE FROM penyewa WHERE id_penyewa = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    echo "<script>alert('Penyewa berhasil dihapus.');</script>";
    echo "<script>window.location.href = 'penyewa.php';</script>";
} else {
    echo "<script>alert('Gagal menghapus penyewa.');</script>";
    echo "<script>window.location.href = 'penyewa.php';</script>";
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
?>
