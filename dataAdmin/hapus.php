<?php
include '../route/koneksi.php';

if (isset($_GET['id_admin'])) {
    $id_admin = intval($_GET['id_admin']); // pastikan id_admin integer

    $stmt = mysqli_prepare($koneksi, "DELETE FROM admin WHERE id_admin = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_admin);

    if (mysqli_stmt_execute($stmt)) {
        header("location: admin.php?pesan=hapus");
        exit;
    } else {
        echo "Gagal menghapus data: " . mysqli_error($koneksi);
    }

    mysqli_stmt_close($stmt);
} else {
    echo "ID admin tidak ditemukan.";
}
?>
