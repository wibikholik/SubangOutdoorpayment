<?php
session_start();
include("../../route/koneksi.php");

if (!isset($_SESSION['user_id'])) {
    // Jika belum login, arahkan ke login
    header("Location: ../../login.php");
    exit;
}

$id_penyewa = $_SESSION['user_id'];

// Ambil data dari form POST
$nama_penyewa = trim($_POST['nama_penyewa'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validasi sederhana
if (empty($nama_penyewa) || empty($alamat) || empty($no_hp) || empty($email)) {
    header("Location: ../page/profil.php?status=gagal");
    exit;
}

// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../page/profil.php?status=gagal");
    exit;
}

// Validasi no_hp (boleh angka, spasi, +, -)
if (!preg_match('/^[0-9+\-\s]{7,20}$/', $no_hp)) {
    header("Location: ../page/profil.php?status=gagal");
    exit;
}

// Update data ke database
$sql = "UPDATE penyewa SET nama_penyewa = ?, alamat = ?, no_hp = ?, email = ? WHERE id_penyewa = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("ssssi", $nama_penyewa, $alamat, $no_hp, $email, $id_penyewa);

if ($stmt->execute()) {
    header("Location: ../page/profil.php?status=sukses");
} else {
    header("Location: ../page/profil.php?status=gagal");
}

$stmt->close();
$koneksi->close();