<?php
include '../../route/koneksi.php';


$id = $_GET['id'];


mysqli_query($koneksi, "DELETE FROM carts WHERE id = '$id'");

// Redirect ke halaman dashboard setelah penghapusan data
header("location: ../page/keranjang.php?pesan=hapus");
?>
