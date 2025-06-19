<?php
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Keseluruhan.xls");

include '../route/koneksi.php';

$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : null;
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : null;

$whereFilterTrans = "";
$whereFilterPendapatan = "";
if ($bulan && $tahun) {
    $whereFilterTrans = " AND MONTH(t.tanggal_kembali) = '$bulan' AND YEAR(t.tanggal_kembali) = '$tahun'";
    $whereFilterPendapatan = " AND MONTH(p.tanggal_pengembalian) = '$bulan' AND YEAR(p.tanggal_pengembalian) = '$tahun'";
}

// Fungsi ambil total pendapatan per interval
function getTotal($conn, $interval, $filter) {
    $sql = "SELECT SUM(t.total_harga_sewa) AS total FROM transaksi t
            JOIN pengembalian p ON t.id_transaksi = p.id_transaksi
            WHERE t.status = 'Selesai Dikembalikan'
            AND p.tanggal_pengembalian >= DATE_SUB(CURDATE(), INTERVAL $interval) $filter";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result)['total'] ?? 0;
}

// Fungsi ambil total denda per interval
function getDenda($conn, $interval, $filter) {
    $sql = "SELECT SUM(denda) AS total FROM pengembalian p
            WHERE p.status_pengembalian = 'Selesai Dikembalikan'
            AND p.tanggal_pengembalian >= DATE_SUB(CURDATE(), INTERVAL $interval) $filter";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result)['total'] ?? 0;
}

// Ambil ringkasan keuangan
$harian = getTotal($koneksi, "1 DAY", $whereFilterPendapatan);
$mingguan = getTotal($koneksi, "7 DAY", $whereFilterPendapatan);
$bulanan = getTotal($koneksi, "1 MONTH", $whereFilterPendapatan);
$tahunan = getTotal($koneksi, "1 YEAR", $whereFilterPendapatan);
$denda = getDenda($koneksi, "1 YEAR", $whereFilterPendapatan);

// Query transaksi selesai
$sqlTrans = "SELECT t.id_transaksi, t.tanggal_sewa, t.tanggal_kembali, p.nama_penyewa, m.nama_metode, t.total_harga_sewa 
    FROM transaksi t
    JOIN metode_pembayaran m ON t.id_metode = m.id_metode
    JOIN penyewa p ON t.id_penyewa = p.id_penyewa
    WHERE t.status = 'Selesai Dikembalikan' $whereFilterTrans
    ORDER BY t.id_transaksi DESC";
$dataTrans = mysqli_query($koneksi, $sqlTrans);

// Query pengembalian
$sqlPeng = "SELECT p.id_pengembalian, p.id_transaksi, p.tanggal_pengembalian, py.nama_penyewa, p.denda, p.catatan 
    FROM pengembalian p
    JOIN transaksi t ON p.id_transaksi = t.id_transaksi
    JOIN penyewa py ON t.id_penyewa = py.id_penyewa
    WHERE p.status_pengembalian = 'Selesai Dikembalikan'
    ORDER BY p.tanggal_pengembalian DESC";
$dataPeng = mysqli_query($koneksi, $sqlPeng);

// --- Output laporan ---

echo "Laporan Keseluruhan Subang Outdoor\n";
echo "Filter Bulan: " . ($bulan ?: "Semua") . "\tTahun: " . ($tahun ?: "Semua") . "\n\n";

echo "Ringkasan Pendapatan\n";
echo "Periode\tTotal Pendapatan (Rp)\n";
echo "Harian\t" . number_format($harian, 0, ',', '.') . "\n";
echo "Mingguan\t" . number_format($mingguan, 0, ',', '.') . "\n";
echo "Bulanan\t" . number_format($bulanan, 0, ',', '.') . "\n";
echo "Tahunan\t" . number_format($tahunan, 0, ',', '.') . "\n";
echo "Total Denda\t" . number_format($denda, 0, ',', '.') . "\n\n";

echo "Data Transaksi Selesai\n";
echo "No\tID Transaksi\tTanggal Sewa\tTanggal Kembali\tNama Penyewa\tMetode Pembayaran\tTotal Harga (Rp)\n";
$no = 1;
while ($row = mysqli_fetch_assoc($dataTrans)) {
    echo $no++ . "\t" .
        $row['id_transaksi'] . "\t" .
        "=\"" . $row['tanggal_sewa'] . "\"\t" .  // supaya Excel treat sebagai teks
        "=\"" . $row['tanggal_kembali'] . "\"\t" .
        $row['nama_penyewa'] . "\t" .
        $row['nama_metode'] . "\t" .
        number_format($row['total_harga_sewa'], 0, ',', '.') . "\n";
}
echo "\n";

echo "Data Pengembalian Barang\n";
echo "No\tID Pengembalian\tID Transaksi\tTanggal Pengembalian\tNama Penyewa\tDenda (Rp)\tCatatan\n";
$no = 1;
while ($row = mysqli_fetch_assoc($dataPeng)) {
    echo $no++ . "\t" .
        $row['id_pengembalian'] . "\t" .
        $row['id_transaksi'] . "\t" .
        "=\"" . $row['tanggal_pengembalian'] . "\"\t" .
        $row['nama_penyewa'] . "\t" .
        number_format($row['denda'], 0, ',', '.') . "\t" .
        $row['catatan'] . "\n";
}
