<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pengembalian.php');
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token tidak valid.');
}

if (empty($_POST['id_pengembalian'])) {
    die('ID pengembalian tidak ditemukan.');
}

$id_pengembalian = $_POST['id_pengembalian'];

include '../route/koneksi.php';

// Ambil nama file bukti_pengembalian dan bukti_denda dari database
$query = "SELECT bukti_pengembalian, bukti_denda FROM pengembalian WHERE id_pengembalian = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'i', $id_pengembalian);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $bukti_pengembalian, $bukti_denda);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$folder_pengembalian = '../uploads/pengembalian/';
$folder_denda = '../uploads/denda/';

// Hapus file bukti_pengembalian jika ada
if ($bukti_pengembalian) {
    $file_path = $folder_pengembalian . $bukti_pengembalian;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Hapus file bukti_denda jika ada
if ($bukti_denda) {
    $file_path = $folder_denda . $bukti_denda;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Hapus data pengembalian dari database
$stmt = mysqli_prepare($koneksi, "DELETE FROM pengembalian WHERE id_pengembalian = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_pengembalian);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['message'] = "Data pengembalian dan file bukti berhasil dihapus.";
    header('Location: pengembalian.php');
    exit;
} else {
    die('Gagal menghapus data: ' . mysqli_error($koneksi));
}
?>
