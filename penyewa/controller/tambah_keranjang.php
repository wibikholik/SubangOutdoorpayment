<?php
session_start();
include '../../route/koneksi.php';

// Gunakan ID penyewa dari session login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}
$id_penyewa = $_SESSION['user_id'];

// Validasi data POST
if (isset($_POST['id_barang']) && isset($_POST['jumlah'])) {
    $id_barang = mysqli_real_escape_string($koneksi, $_POST['id_barang']);
    $jumlah = (int) $_POST['jumlah'];

    if ($jumlah <= 0) {
        echo "<script>alert('Jumlah harus lebih dari 0.'); window.history.back();</script>";
        exit;
    }

    // Ambil data barang
    $barangQuery = mysqli_query($koneksi, "SELECT * FROM barang WHERE id_barang = '$id_barang'");
    $barang = mysqli_fetch_assoc($barangQuery);

    if (!$barang) {
        echo "<script>alert('Barang tidak ditemukan.'); window.history.back();</script>";
        exit;
    }

    $harga_sewa = $barang['harga_sewa'];

    // Cek apakah barang sudah ada di keranjang
    $cekQuery = mysqli_query($koneksi, "SELECT * FROM carts WHERE id_penyewa = '$id_penyewa' AND id_barang = '$id_barang'");
    if (mysqli_num_rows($cekQuery) > 0) {
        // Barang sudah ada di keranjang: update jumlah
        $cart = mysqli_fetch_assoc($cekQuery);
        $jumlah_baru = $cart['jumlah'] + $jumlah;
        $harga_baru = $harga_sewa * $jumlah_baru;

        $updateQuery = mysqli_query($koneksi, "UPDATE carts SET jumlah = '$jumlah_baru', harga = '$harga_baru' WHERE id_penyewa = '$id_penyewa' AND id_barang = '$id_barang'");

        if ($updateQuery) {
            echo "<script>alert('Jumlah barang di keranjang berhasil diperbarui.'); window.location.href='../page/produk.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui keranjang.'); window.history.back();</script>";
        }
    } else {
        // Barang belum ada: insert baru
        $total_harga = $harga_sewa * $jumlah;
        $insertQuery = mysqli_query($koneksi, "INSERT INTO carts (id_penyewa, id_barang, jumlah, harga) VALUES ('$id_penyewa', '$id_barang', '$jumlah', '$total_harga')");

        if ($insertQuery) {
            echo "<script>alert('Barang berhasil ditambahkan ke keranjang.'); window.location.href='../page/produk.php';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan ke keranjang.'); window.history.back();</script>";
        }
    }

} else {
    echo "<script>alert('Data tidak lengkap.'); window.history.back();</script>";
}
?>
