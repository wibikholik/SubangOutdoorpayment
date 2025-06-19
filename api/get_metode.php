<?php
include '../route/koneksi.php';
header('Content-Type: application/json');

$id_tipe = isset($_GET['id_tipe']) ? (int)$_GET['id_tipe'] : 0;

$sql = "SELECT id_metode, nama_metode, nomor_rekening FROM metode_pembayaran WHERE id_tipe = $id_tipe ORDER BY nama_metode ASC";
$result = $koneksi->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
