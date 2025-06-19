<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_metode = $_POST['id_metode'] ?? 0;
    $nama_metode = $_POST['nama_metode'] ?? '';
    $nomor_rekening = $_POST['nomor_rekening'] ?? '';
    $atas_nama = $_POST['atas_nama'] ?? '';
    $id_tipe = $_POST['id_tipe'] ?? '';

    if (!$id_metode || empty($nama_metode) || empty($nomor_rekening) || empty($atas_nama) || empty($id_tipe)) {
        die("Data tidak lengkap.");
    }

    // Cek gambar baru
    $gambar_name = null;
    $target_dir = "metode/gambar/";

    if (isset($_FILES['gambar_metode']) && $_FILES['gambar_metode']['error'] == 0) {
        $file_tmp = $_FILES['gambar_metode']['tmp_name'];
        $file_name = basename($_FILES['gambar_metode']['name']);
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (!in_array($ext, $allowed)) {
            die("Format file tidak didukung.");
        }

        $gambar_name = uniqid() . '.' . $ext;
        if (!move_uploaded_file($file_tmp, $target_dir . $gambar_name)) {
            die("Gagal mengupload gambar.");
        }

        // Hapus gambar lama jika ada
        $old_img_query = mysqli_prepare($koneksi, "SELECT gambar_metode FROM metode_pembayaran WHERE id_metode = ?");
        mysqli_stmt_bind_param($old_img_query, "i", $id_metode);
        mysqli_stmt_execute($old_img_query);
        mysqli_stmt_bind_result($old_img_query, $old_img);
        mysqli_stmt_fetch($old_img_query);
        mysqli_stmt_close($old_img_query);

        if ($old_img && file_exists($target_dir . $old_img)) {
            unlink($target_dir . $old_img);
        }
    }

    if ($gambar_name) {
        $stmt = mysqli_prepare($koneksi, "UPDATE metode_pembayaran SET id_tipe = ?, nama_metode = ?, nomor_rekening = ?, gambar_metode = ?, atas_nama = ? WHERE id_metode = ?");
        mysqli_stmt_bind_param($stmt, "issssi", $id_tipe, $nama_metode, $nomor_rekening, $gambar_name, $atas_nama, $id_metode);
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE metode_pembayaran SET id_tipe = ?, nama_metode = ?, nomor_rekening = ?, atas_nama = ? WHERE id_metode = ?");
        mysqli_stmt_bind_param($stmt, "isssi", $id_tipe, $nama_metode, $nomor_rekening, $atas_nama, $id_metode);
    }

    if (mysqli_stmt_execute($stmt)) {
        header("Location: metode.php?pesan=update");
        exit;
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
} else {
    header("Location: metode.php");
    exit;
}
