<?php
session_start();
include '../route/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

if (isset($_GET['id_pengembalian'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['id_pengembalian']);

    // Ambil nama file bukti pengembalian
    $query_file = "SELECT bukti_pengembalian FROM pengembalian WHERE id_pengembalian = '$id'";
    $result_file = mysqli_query($koneksi, $query_file);
    if ($result_file && mysqli_num_rows($result_file) > 0) {
        $row = mysqli_fetch_assoc($result_file);
        $nama_file = $row['bukti_pengembalian'];

        // Hapus file fisik jika ada dan file memang ada di folder uploads
        $path_file = '../uploads/bukti_pengembalian/' . $nama_file;
        if (file_exists($path_file) && is_file($path_file)) {
            unlink($path_file); // hapus file
        }
    }

    // Hapus data pengembalian dari database
    $query_delete = "DELETE FROM pengembalian WHERE id_pengembalian = '$id'";
    if (mysqli_query($koneksi, $query_delete)) {
        header("Location: pengembalian.php?msg=sukses");
    } else {
        header("Location: pengembalian.php?msg=gagal");
    }
} else {
    header("Location: pengembalian.php");
}
?>
