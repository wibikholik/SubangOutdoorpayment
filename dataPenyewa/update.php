<?php
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

include '../route/koneksi.php';

// Ambil data dari form
$id_penyewa   = $_POST['id_penyewa'];
$nama_penyewa = $_POST['namapenyewa'];
$alamat       = $_POST['alamat'];
$no_hp        = $_POST['no_hp'];
$email        = $_POST['email'];
$password     = $_POST['password']; // Password langsung digunakan, tanpa di hash

// Mengecek apakah email sudah terdaftar
$query_email = "SELECT id_penyewa FROM penyewa WHERE email = ? AND id_penyewa != ?";
$stmt_email = mysqli_prepare($koneksi, $query_email);
mysqli_stmt_bind_param($stmt_email, "si", $email, $id_penyewa);
mysqli_stmt_execute($stmt_email);
mysqli_stmt_store_result($stmt_email);

// Jika email sudah ada (selain yang saat ini), beri pesan error
if (mysqli_stmt_num_rows($stmt_email) > 0) {
    header("Location: editPenyewa.php?id_penyewa=$id_penyewa&pesan=duplikat_email");
    exit;
}

// Mengecek apakah nomor HP sudah terdaftar
$query_hp = "SELECT id_penyewa FROM penyewa WHERE no_hp = ? AND id_penyewa != ?";
$stmt_hp = mysqli_prepare($koneksi, $query_hp);
mysqli_stmt_bind_param($stmt_hp, "si", $no_hp, $id_penyewa);
mysqli_stmt_execute($stmt_hp);
mysqli_stmt_store_result($stmt_hp);

// Jika nomor HP sudah ada (selain yang saat ini), beri pesan error
if (mysqli_stmt_num_rows($stmt_hp) > 0) {
    header("Location: editPenyewa.php?id_penyewa=$id_penyewa&pesan=duplikat_no_hp");
    exit;
}

// Query untuk update data penyewa
$query = "UPDATE penyewa 
          SET nama_penyewa = ?, 
              alamat = ?, 
              no_hp = ?, 
              email = ?, 
              password = ? 
          WHERE id_penyewa = ?";

// Persiapkan statement
$stmt = mysqli_prepare($koneksi, $query);

// Bind parameter ke statement
mysqli_stmt_bind_param($stmt, "sssssi", $nama_penyewa, $alamat, $no_hp, $email, $password, $id_penyewa);

// Eksekusi query
$execute = mysqli_stmt_execute($stmt);

// Cek apakah query berhasil dieksekusi
if ($execute) {
    header("Location: penyewa.php?pesan=update"); // Jika berhasil, redirect ke halaman penyewa
} else {
    echo "Gagal update data: " . mysqli_error($koneksi); // Menampilkan pesan error jika gagal
}

// Menutup statement dan koneksi
mysqli_stmt_close($stmt);
mysqli_stmt_close($stmt_email);
mysqli_stmt_close($stmt_hp);
mysqli_close($koneksi);
?>
