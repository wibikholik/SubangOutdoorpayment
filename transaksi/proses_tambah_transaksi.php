<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

// Ambil input dari form
$id_penyewa = $_POST['id_penyewa'] ?? '';
$nama_baru = trim($_POST['nama_baru'] ?? '');
$id_barang = $_POST['id_barang'] ?? '';
$jumlah_barang = (int)($_POST['jumlah_barang'] ?? 1);
$id_metode = $_POST['id_metode'] ?? '';
$tanggal_sewa = $_POST['tanggal_sewa'] ?? '';
$tanggal_kembali = $_POST['tanggal_kembali'] ?? '';

// Validasi sederhana
if ($id_barang === '' || $jumlah_barang < 1 || $id_metode === '' || $tanggal_sewa === '' || $tanggal_kembali === '') {
    die("Data tidak lengkap. Silakan isi semua field yang diperlukan.");
}

// Jika input penyewa baru tidak kosong, buat penyewa baru dulu
if ($nama_baru !== '') {
    $nama_baru_escaped = mysqli_real_escape_string($koneksi, $nama_baru);
    $insert_penyewa = "INSERT INTO penyewa (nama_penyewa) VALUES ('$nama_baru_escaped')";
    if (!mysqli_query($koneksi, $insert_penyewa)) {
        die("Gagal menyimpan penyewa baru: " . mysqli_error($koneksi));
    }
    $id_penyewa = mysqli_insert_id($koneksi);
}

// Jika penyewa tidak dipilih dan tidak input baru, error
if ($id_penyewa === '') {
    die("Penyewa harus dipilih atau diinput baru.");
}

// Ambil harga satuan barang dari database
$query_harga = "SELECT harga_sewa FROM barang WHERE id_barang = '$id_barang'";
$result_harga = mysqli_query($koneksi, $query_harga);
if (!$result_harga || mysqli_num_rows($result_harga) == 0) {
    die("Barang tidak ditemukan.");
}
$row_harga = mysqli_fetch_assoc($result_harga);
$harga_satuan = (int)$row_harga['harga_sewa'];

// Hitung total harga sewa
$total_harga_sewa = $harga_satuan * $jumlah_barang;

// Simpan ke tabel transaksi
$insert_transaksi = "
    INSERT INTO transaksi (id_penyewa, total_harga_sewa, status, tanggal_sewa, tanggal_kembali, id_metode)
    VALUES ('$id_penyewa', '$total_harga_sewa', 'menunggu konfirmasi pembayaran', '$tanggal_sewa', '$tanggal_kembali', '$id_metode')
";

if (!mysqli_query($koneksi, $insert_transaksi)) {
    die("Gagal menyimpan transaksi: " . mysqli_error($koneksi));
}
$id_transaksi = mysqli_insert_id($koneksi);

// Simpan detail transaksi
$insert_detail = "
    INSERT INTO detail_transaksi (id_transaksi, id_barang, jumlah_barang, harga_satuan)
    VALUES ('$id_transaksi', '$id_barang', '$jumlah_barang', '$harga_satuan')
";

if (!mysqli_query($koneksi, $insert_detail)) {
    die("Gagal menyimpan detail transaksi: " . mysqli_error($koneksi));
}

// Redirect ke halaman daftar transaksi dengan pesan sukses
header("Location: transaksi.php?status=success");
exit;
