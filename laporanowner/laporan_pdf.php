<?php
require '../vendor/autoload.php';
require '../route/koneksi.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    die("Akses ditolak. Hanya untuk owner.");
}

$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : null;
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : null;

$whereTransaksi = '';
$wherePengembalian = '';
if ($bulan && $tahun) {
    $whereTransaksi = " AND MONTH(t.tanggal_kembali) = '$bulan' AND YEAR(t.tanggal_kembali) = '$tahun'";
    $wherePengembalian = " AND MONTH(pg.tanggal_pengembalian) = '$bulan' AND YEAR(pg.tanggal_pengembalian) = '$tahun'";
}

// Total transaksi selesai
$qTotalTransaksi = mysqli_query($koneksi, "
    SELECT SUM(t.total_harga_sewa) AS total
    FROM transaksi t 
    WHERE t.status = 'Selesai Dikembalikan' $whereTransaksi
");
$totalTransaksi = mysqli_fetch_assoc($qTotalTransaksi)['total'] ?? 0;

// Total denda
$qTotalDenda = mysqli_query($koneksi, "
    SELECT SUM(pg.denda) AS total_denda 
    FROM pengembalian pg 
    WHERE pg.status_pengembalian = 'Selesai Dikembalikan' $wherePengembalian
");
$totalDenda = mysqli_fetch_assoc($qTotalDenda)['total_denda'] ?? 0;

// Rincian transaksi
$qTransaksi = mysqli_query($koneksi, "
    SELECT t.id_transaksi, t.tanggal_sewa, t.tanggal_kembali, t.total_harga_sewa,
           m.nama_metode, p.nama_penyewa
    FROM transaksi t
    JOIN metode_pembayaran m ON t.id_metode = m.id_metode
    JOIN penyewa p ON t.id_penyewa = p.id_penyewa
    WHERE t.status = 'Selesai Dikembalikan' $whereTransaksi
    ORDER BY t.id_transaksi DESC
");

// Rincian pengembalian
$qPengembalian = mysqli_query($koneksi, "
    SELECT pg.id_pengembalian, pg.id_transaksi, pg.tanggal_pengembalian, pg.denda,
           pg.catatan, pg.status_pengembalian, p.nama_penyewa
    FROM pengembalian pg
    JOIN transaksi t ON pg.id_transaksi = t.id_transaksi
    JOIN penyewa p ON t.id_penyewa = p.id_penyewa
    WHERE pg.status_pengembalian = 'Selesai Dikembalikan' $wherePengembalian
    ORDER BY pg.id_pengembalian DESC
");

$html = "<h2 style='text-align: center;'>LAPORAN KEUANGAN - SUBANG OUTDOOR</h2>";
if ($bulan && $tahun) {
    $html .= "<p style='text-align: center;'>Periode: " . date('F', mktime(0,0,0,$bulan,1)) . " $tahun</p>";
}

$html .= "
<h3>Ringkasan Keuangan</h3>
<table border='1' cellpadding='6' cellspacing='0' width='100%' style='font-size:12px; margin-bottom:20px;'>
    <tr style='background:#f0f0f0; font-weight:bold;'>
        <td>Total Pendapatan Transaksi</td>
        <td>Total Denda Pengembalian</td>
        <td>Total Keseluruhan</td>
    </tr>
    <tr>
        <td>Rp. " . number_format($totalTransaksi, 0, ',', '.') . "</td>
        <td>Rp. " . number_format($totalDenda, 0, ',', '.') . "</td>
        <td>Rp. " . number_format($totalTransaksi + $totalDenda, 0, ',', '.') . "</td>
    </tr>
</table>
";

// =================== Transaksi Selesai ========================
$html .= "<h3>Detail Transaksi Selesai</h3>";
$html .= "
<table border='1' cellpadding='6' cellspacing='0' style='width:100%; font-size:11px; margin-bottom:20px;'>
<thead>
    <tr style='background:#e0e0e0; font-weight:bold;'>
        <th>ID Transaksi</th>
        <th>Tanggal Sewa</th>
        <th>Tanggal Kembali</th>
        <th>Nama Penyewa</th>
        <th>Metode Pembayaran</th>
        <th>Total Bayar</th>
    </tr>
</thead>
<tbody>";
while ($row = mysqli_fetch_assoc($qTransaksi)) {
    $html .= "<tr>
        <td>{$row['id_transaksi']}</td>
        <td>{$row['tanggal_sewa']}</td>
        <td>{$row['tanggal_kembali']}</td>
        <td>{$row['nama_penyewa']}</td>
        <td>{$row['nama_metode']}</td>
        <td>Rp. " . number_format($row['total_harga_sewa'], 0, ',', '.') . "</td>
    </tr>";
}
$html .= "</tbody></table>";

// =================== Pengembalian ============================
$html .= "<h3>Detail Pengembalian</h3>";
$html .= "
<table border='1' cellpadding='6' cellspacing='0' style='width:100%; font-size:11px;'>
<thead>
    <tr style='background:#e0e0e0; font-weight:bold;'>
        <th>ID Pengembalian</th>
        <th>ID Transaksi</th>
        <th>Tanggal Pengembalian</th>
        <th>Nama Penyewa</th>
        <th>Denda</th>
        <th>Catatan</th>
        <th>Status</th>
    </tr>
</thead>
<tbody>";
while ($row = mysqli_fetch_assoc($qPengembalian)) {
    $html .= "<tr>
        <td>{$row['id_pengembalian']}</td>
        <td>{$row['id_transaksi']}</td>
        <td>{$row['tanggal_pengembalian']}</td>
        <td>{$row['nama_penyewa']}</td>
        <td>Rp. " . number_format($row['denda'], 0, ',', '.') . "</td>
        <td>{$row['catatan']}</td>
        <td>{$row['status_pengembalian']}</td>
    </tr>";
}
$html .= "</tbody></table>";

// =================== Output PDF ============================
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("Laporan_Keuangan_SubangOutdoor.pdf", ["Attachment" => false]);
exit;
