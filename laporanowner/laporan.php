<?php
session_start();
include '../route/koneksi.php';

// Akses hanya owner
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../login.php?message=access_denied");
    exit;
}

// Fungsi bantu untuk ambil total
function getTotalByPeriod($koneksi, $interval, $bulan = null, $tahun = null) {
    $where = "";
    if ($bulan && $tahun) {
        $where = "AND MONTH(p.tanggal_pengembalian) = '$bulan' AND YEAR(p.tanggal_pengembalian) = '$tahun'";
    }
    $query = "
        SELECT SUM(t.total_harga_sewa) AS total 
        FROM transaksi t
        JOIN pengembalian p ON t.id_transaksi = p.id_transaksi
        WHERE t.status = 'Selesai Dikembalikan' 
        AND p.tanggal_pengembalian >= DATE_SUB(CURDATE(), INTERVAL $interval) $where
    ";
    $result = mysqli_query($koneksi, $query);
    if (!$result) {
        die("Query getTotalByPeriod error: " . mysqli_error($koneksi));
    }
    $data = mysqli_fetch_assoc($result);
    return $data['total'] ?? 0;
}

// Ambil filter bulan dan tahun dari GET
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : null;
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : null;

// Data ringkasan
$total_harian = getTotalByPeriod($koneksi, "1 DAY", $bulan, $tahun);
$total_mingguan = getTotalByPeriod($koneksi, "7 DAY", $bulan, $tahun);
$total_bulanan = getTotalByPeriod($koneksi, "1 MONTH", $bulan, $tahun);
$total_tahunan = getTotalByPeriod($koneksi, "1 YEAR", $bulan, $tahun);

// Filter tambahan untuk transaksi dan grafik
$whereFilter = "";
if ($bulan && $tahun) {
    $whereFilter = " AND MONTH(t.tanggal_kembali) = '$bulan' AND YEAR(t.tanggal_kembali) = '$tahun' ";
}

// Query transaksi selesai dengan filter, join ke penyewa untuk nama
$queryTransaksi = "
    SELECT t.*, m.nama_metode, p.nama_penyewa 
    FROM transaksi t
    JOIN metode_pembayaran m ON t.id_metode = m.id_metode 
    JOIN penyewa p ON t.id_penyewa = p.id_penyewa
    WHERE t.status = 'Selesai Dikembalikan' $whereFilter
    ORDER BY t.id_transaksi DESC
";
$transaksi = mysqli_query($koneksi, $queryTransaksi);
if (!$transaksi) {
    die("Query transaksi error: " . mysqli_error($koneksi));
}

// Query data sumber penghasilan untuk chart
$queryChart = "
    SELECT m.nama_metode, SUM(t.total_harga_sewa) AS total 
    FROM transaksi t
    JOIN metode_pembayaran m ON t.id_metode = m.id_metode 
    WHERE t.status = 'Selesai Dikembalikan' $whereFilter
    GROUP BY t.id_metode
";
$chart = mysqli_query($koneksi, $queryChart);
if (!$chart) {
    die("Query chart error: " . mysqli_error($koneksi));
}
$chart_labels = [];
$chart_data = [];
while ($row = mysqli_fetch_assoc($chart)) {
    $chart_labels[] = $row['nama_metode'];
    $chart_data[] = (float)$row['total'];
}

// Query laporan pengembalian dengan join ke penyewa
$pengembalian = mysqli_query($koneksi, "
    SELECT p.*, t.tanggal_sewa, t.tanggal_kembali, t.id_penyewa, py.nama_penyewa,
           k.nama_kelengkapan,
           c.status_awal AS kondisi_awal,
           c.status_akhir AS kondisi_akhir,
           c.keterangan_awal,
           c.keterangan_akhir
    FROM pengembalian p
    JOIN transaksi t ON p.id_transaksi = t.id_transaksi
    JOIN penyewa py ON t.id_penyewa = py.id_penyewa
    LEFT JOIN checklist c ON p.id_transaksi = c.id_transaksi
    LEFT JOIN kelengkapan_barang k ON c.id_kelengkapan = k.id_kelengkapan
    WHERE p.status_pengembalian = 'Selesai Dikembalikan'
    ORDER BY p.tanggal_pengembalian DESC
");

if (!$pengembalian) {
    die("Query pengembalian error: " . mysqli_error($koneksi));
}

$pengembalianData = [];
while ($row = mysqli_fetch_assoc($pengembalian)) {
    $id = $row['id_pengembalian'];
    if (!isset($pengembalianData[$id])) {
        $pengembalianData[$id] = [
            'id_pengembalian' => $row['id_pengembalian'],
            'id_transaksi' => $row['id_transaksi'],
            'tanggal_pengembalian' => $row['tanggal_pengembalian'],
            'denda' => $row['denda'],
            'catatan' => $row['catatan'],
            'status_pengembalian' => $row['status_pengembalian'],
            'nama_penyewa' => $row['nama_penyewa'],
            'perlengkapan' => []
        ];
    }
    if ($row['nama_kelengkapan']) {
        $pengembalianData[$id]['perlengkapan'][] = [
            'nama_kelengkapan' => $row['nama_kelengkapan'],
            'kondisi_awal' => $row['kondisi_awal'] . " (" . ($row['keterangan_awal'] ?: '-') . ")",
            'kondisi_akhir' => $row['kondisi_akhir'] . " (" . ($row['keterangan_akhir'] ?: '-') . ")"
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi & Pengembalian - Subang Outdoor</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include '../layout/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include '../layout/navbar.php'; ?>
            <div class="container-fluid">

                <h1 class="h3 mb-4 text-gray-800">Laporan Transaksi & Pengembalian</h1>

                <!-- Filter -->
                <form method="get" class="mb-4">
                    <div class="form-row">
                        <div class="col-md-4 mb-2">
                            <select name="bulan" class="form-control">
                                <option value="">Pilih Bulan</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($i == $bulan) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $i, 10)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <input type="number" name="tahun" class="form-control" placeholder="Tahun" value="<?= $tahun ?>" min="2000" max="<?= date('Y') ?>">
                        </div>
                        <div class="col-md-4 mb-2">
                            <button type="submit" class="btn btn-primary btn-block">Terapkan Filter</button>
                        </div>
                    </div>
                </form>

                <!-- Ringkasan -->
                <div class="row mb-4">
                    <div class="col-lg-9">
                        <div class="row">
                            <?php
                            $labels = ['Hari Ini', 'Minggu Ini', 'Bulan Ini', 'Tahun Ini'];
                            $values = [$total_harian, $total_mingguan, $total_bulanan, $total_tahunan];
                            $colors = ['primary', 'success', 'info', 'warning'];
                            foreach ($labels as $i => $label):
                            ?>
                            <div class="col-xl-3 col-md-6 mb-4">
                                <div class="card border-left-<?= $colors[$i] ?> shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="text-xs font-weight-bold text-<?= $colors[$i] ?> text-uppercase mb-1"><?= $label ?></div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp.<?= number_format($values[$i], 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                   
                </div>
 <div class="col-auto">
    <div class="card shadow mb-4" style="max-width: 350px;">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Grafik Sumber Penghasilan</h6>
        </div>
        <div class="card-body">
            <canvas id="chartMetode" style="height: 300px;"></canvas>
        </div>
    </div>
</div>

                <!-- Tabel Transaksi -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Riwayat Transaksi Selesai</h6></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTableTransaksi">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tanggal Sewa</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Nama Penyewa</th>
                                        <th>Metode</th>
                                        <th>Total Bayar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($row = mysqli_fetch_assoc($transaksi)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id_transaksi']) ?></td>
                                        <td><?= htmlspecialchars($row['tanggal_sewa']) ?></td>
                                        <td><?= htmlspecialchars($row['tanggal_kembali']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_penyewa']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_metode']) ?></td>
                                        <td>Rp.<?= number_format($row['total_harga_sewa'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tabel Pengembalian -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Laporan Pengembalian Barang</h6></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTablePengembalian">
                                <thead>
                                    <tr>
                                        <th>ID Pengembalian</th>
                                        <th>ID Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Nama Penyewa</th>
                                        <th>Perlengkapan & Kondisi</th>
                                        <th>Denda</th>
                                        <th>Catatan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($pengembalianData as $p): ?>
                                    <tr>
                                        <td><?= $p['id_pengembalian'] ?></td>
                                        <td><?= $p['id_transaksi'] ?></td>
                                        <td><?= $p['tanggal_pengembalian'] ?></td>
                                        <td><?= htmlspecialchars($p['nama_penyewa']) ?></td>
                                        <td>
                                            <ul>
                                            <?php foreach ($p['perlengkapan'] as $pl): ?>
                                                <li><?= htmlspecialchars($pl['nama_kelengkapan']) ?><br>Awal: <?= htmlspecialchars($pl['kondisi_awal']) ?><br>Akhir: <?= htmlspecialchars($pl['kondisi_akhir']) ?></li>
                                            <?php endforeach; ?>
                                            </ul>
                                        </td>
                                        <td>Rp.<?= number_format($p['denda'], 0, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($p['catatan']) ?></td>
                                        <td><?= htmlspecialchars($p['status_pengembalian']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
      
    </div>
</div>

<!-- Scripts -->
<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>
<script src="../assets/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $('#dataTableTransaksi').DataTable();
    $('#dataTablePengembalian').DataTable();
});
const ctx = document.getElementById('chartMetode').getContext('2d');
const chartMetode = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            data: <?= json_encode($chart_data) ?>,
            backgroundColor: ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            title: { display: true, text: 'Distribusi Pendapatan berdasarkan Metode Pembayaran' }
        }
    }
});
</script>
</body>
</html>
