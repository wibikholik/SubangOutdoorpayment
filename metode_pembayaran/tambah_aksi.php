<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_metode = $_POST['nama_metode'] ?? '';
    $nomor_rekening = $_POST['nomor_rekening'] ?? '';
    $atas_nama = $_POST['atas_nama'] ?? '';
    $id_tipe = $_POST['id_tipe'] ?? '';

    // Validasi sederhana
    if (empty($nama_metode) || empty($nomor_rekening) || empty($atas_nama) || empty($id_tipe)) {
        die("Semua field harus diisi.");
    }

    // Upload gambar
    $target_dir = "metode/gambar/";
    $gambar_name = null;
    if (isset($_FILES['gambar_metode']) && $_FILES['gambar_metode']['error'] == 0) {
        $file_tmp = $_FILES['gambar_metode']['tmp_name'];
        $file_name = basename($_FILES['gambar_metode']['name']);
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (!in_array($ext, $allowed)) {
            die("Format file tidak didukung. Harus jpg, jpeg, png, atau gif.");
        }

        $gambar_name = uniqid() . '.' . $ext;
        if (!move_uploaded_file($file_tmp, $target_dir . $gambar_name)) {
            die("Gagal mengupload gambar.");
        }
    } else {
        die("Gambar harus diupload.");
    }

    // Insert ke database
    $stmt = mysqli_prepare($koneksi, "INSERT INTO metode_pembayaran (id_tipe, nama_metode, nomor_rekening, gambar_metode, atas_nama) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issss", $id_tipe, $nama_metode, $nomor_rekening, $gambar_name, $atas_nama);
    $exec = mysqli_stmt_execute($stmt);

    if ($exec) {
        header("Location: metode.php?pesan=input");
        exit;
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
} else {
    header("Location: metode.php");
    exit;
}
