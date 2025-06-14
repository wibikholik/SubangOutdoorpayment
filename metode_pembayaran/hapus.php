<?php
include '../route/koneksi.php';

if (isset($_GET['id_metode'])) {
    $id_metode = intval($_GET['id_metode']); // pastikan id_metode integer

    // Ambil nama file gambar dari database
    $stmt = mysqli_prepare($koneksi, "SELECT gambar_metode FROM metode_pembayaran WHERE id_metode = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_metode);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $nama_gambar);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($nama_gambar) {
        $path_gambar = 'metode/gambar/' . $nama_gambar;
        if (file_exists($path_gambar)) {
            unlink($path_gambar); // hapus file gambar
        }
    }

    // Hapus data metode pembayaran dari database
    $stmt = mysqli_prepare($koneksi, "DELETE FROM metode_pembayaran WHERE id_metode = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_metode);

    if (mysqli_stmt_execute($stmt)) {
        header("location: metode.php?pesan=hapus");
        exit;
    } else {
        echo "Gagal menghapus data: " . mysqli_error($koneksi);
    }

    mysqli_stmt_close($stmt);
} else {
    echo "ID metode tidak ditemukan.";
}
?>
