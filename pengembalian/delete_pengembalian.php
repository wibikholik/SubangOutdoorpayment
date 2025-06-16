<?php
session_start();
include '../route/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

if (isset($_GET['id_pengembalian'])) {
    $id = $_GET['id_pengembalian'];

    $query = "DELETE FROM pengembalian WHERE id_pengembalian = '$id'";
    if (mysqli_query($koneksi, $query)) {
        header("Location: pengembalian.php?msg=sukses");
    } else {
        header("Location: pengembalian.php?msg=gagal");
    }
} else {
    header("Location: pengembalian.php");
}
?>
