<?php
session_start();
include '../route/koneksi.php';

// Cek role admin/owner
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

if (!isset($_GET['id_kelengkapan'])) {
    header("Location: kelengkapan.php");
    exit;
}

$id_kelengkapan = (int) $_GET['id_kelengkapan'];

// Hapus data kelengkapan
$query = "DELETE FROM kelengkapan_barang WHERE id_kelengkapan = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_kelengkapan);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: kelengkapan.php?pesan=hapus");
    exit;
} else {
    $stmt->close();
    header("Location: kelengkapan.php?pesan=gagal");
    exit;
}
