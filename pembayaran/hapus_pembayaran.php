<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pembayaran = $_POST['id_pembayaran'] ?? '';

    if (empty($id_pembayaran)) {
        $_SESSION['error'] = "ID pembayaran tidak valid.";
        header("Location: pembayaran.php");
        exit;
    }

    // Hapus file bukti pembayaran jika ada
    $query_bukti = "SELECT bukti_pembayaran FROM pembayaran WHERE id_pembayaran = ?";
    $stmt_bukti = $koneksi->prepare($query_bukti);
    $stmt_bukti->bind_param("i", $id_pembayaran);
    $stmt_bukti->execute();
    $result_bukti = $stmt_bukti->get_result();
    if ($row = $result_bukti->fetch_assoc()) {
        $bukti_file = $row['bukti_pembayaran'];
        if ($bukti_file && file_exists("../uploads/bukti/" . $bukti_file)) {
            unlink("../uploads/bukti/" . $bukti_file);
        }
    }
    $stmt_bukti->close();

    // Hapus data pembayaran
    $query_delete = "DELETE FROM pembayaran WHERE id_pembayaran = ?";
    $stmt_delete = $koneksi->prepare($query_delete);
    $stmt_delete->bind_param("i", $id_pembayaran);

    if ($stmt_delete->execute()) {
        $_SESSION['success'] = "Pembayaran berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Gagal menghapus pembayaran: " . $stmt_delete->error;
    }
    $stmt_delete->close();

    header("Location: pembayaran.php");
    exit;
}

header("Location: pembayaran.php");
exit;
