<?php
include '../route/koneksi.php';

if (isset($_GET['id_barang'])) {
    $id_barang = intval($_GET['id_barang']); // amankan input ID

    // Cek apakah barang masih dipakai di detail_transaksi
    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) as jumlah FROM detail_transaksi WHERE id_barang = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $jumlah_detail);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Cek apakah barang masih dipakai di carts
    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) as jumlah FROM carts WHERE id_barang = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $jumlah_cart);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Jika barang masih dipakai di transaksi atau carts
    if ($jumlah_detail > 0 || $jumlah_cart > 0) {
        // Jika barang masih digunakan, kirim pesan gagal via query string
        if ($jumlah_detail > 0) {
            $pesan = "Barang ini sedang digunakan di transaksi dan tidak dapat dihapus.";
        } elseif ($jumlah_cart > 0) {
            $pesan = "Barang ini sedang ada di dalam keranjang dan tidak dapat dihapus.";
        }

        // Redirect ke halaman barang dengan pesan
        header("location: barang.php?pesan=gagalhapus&error=" . urlencode($pesan));
        exit;
    }

    // Ambil nama file gambar dari database sebelum hapus
    $stmt = mysqli_prepare($koneksi, "SELECT gambar FROM barang WHERE id_barang = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $nama_gambar);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($nama_gambar) {
        $folder_upload = __DIR__ . "/barang/gambar/"; // __DIR__ adalah direktori file ini
        $file_gambar = $folder_upload . $nama_gambar;

        // Cek jika file gambar ada, hapus file gambar
        if (file_exists($file_gambar)) {
            if (!unlink($file_gambar)) {
                // Optional: Log atau berikan info jika gagal hapus file
                // error_log("Gagal menghapus file gambar: $file_gambar");
            }
        }
    }

    // Hapus data barang
    $stmt = mysqli_prepare($koneksi, "DELETE FROM barang WHERE id_barang = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_barang);

    if (mysqli_stmt_execute($stmt)) {
        header("location: barang.php?pesan=hapus");
        exit;
    } else {
        header("location: barang.php?pesan=gagalhapusdb");
        exit;
    }
} else {
    header("location: barang.php?pesan=invalid");
    exit;
}
?>
