<?php
session_start();
include '../route/koneksi.php';

// Query barang dengan stok menipis
$barang_menipis = mysqli_query($koneksi, "SELECT * FROM barang WHERE stok <= 5 ORDER BY stok ASC LIMIT 10");

// Query penyewa top
$penyewa_top = mysqli_query($koneksi, "
    SELECT p.id_penyewa, p.nama_penyewa, COUNT(t.id_transaksi) AS total_transaksi
    FROM penyewa p
    LEFT JOIN transaksi t ON p.id_penyewa = t.id_penyewa
    GROUP BY p.id_penyewa
    ORDER BY total_transaksi DESC
    LIMIT 5
");

// Grafik penyewaan (sewa selesai)
$grafik_penyewaan = mysqli_query($koneksi, "
    SELECT DATE_FORMAT(tanggal_sewa, '%Y-%m') AS bulan, COUNT(*) AS total_penyewaan
    FROM transaksi
    WHERE status = 'Selesai Dikembalikan'
    GROUP BY bulan
    ORDER BY bulan ASC
");

// Grafik pengembalian (tanggal_kembali dengan status selesai)
$grafik_pengembalian = mysqli_query($koneksi, "
    SELECT DATE_FORMAT(tanggal_pengembalian, '%Y-%m') AS bulan, COUNT(*) AS total_pengembalian
    FROM pengembalian
    WHERE status_pengembalian = 'Selesai Dikembalikan' AND tanggal_pengembalian IS NOT NULL
    GROUP BY bulan
    ORDER BY bulan ASC
");

$labels_bulan = [];
$data_penyewaan = [];
$data_pengembalian = [];
$bulan_unik = [];

// Ambil data penyewaan dan kumpulkan bulan unik
while ($row = mysqli_fetch_assoc($grafik_penyewaan)) {
    if (!in_array($row['bulan'], $bulan_unik)) {
        $bulan_unik[] = $row['bulan'];
    }
    $penyewaan_tmp[$row['bulan']] = (int)$row['total_penyewaan'];
}

// Ambil data pengembalian ke array asosiasi per bulan
$pengembalian_tmp = [];
while ($row = mysqli_fetch_assoc($grafik_pengembalian)) {
    if (!in_array($row['bulan'], $bulan_unik)) {
        $bulan_unik[] = $row['bulan'];
    }
    $pengembalian_tmp[$row['bulan']] = (int)$row['total_pengembalian'];
}
sort($bulan_unik);

// Sesuaikan data penyewaan dan pengembalian berdasarkan label bulan (jika tidak ada data, 0)
foreach ($bulan_unik as $bulan) {
    $labels_bulan[] = date("M Y", strtotime($bulan . "-01")); // Format bulan agar lebih mudah dibaca
    $data_penyewaan[] = $penyewaan_tmp[$bulan] ?? 0;
    $data_pengembalian[] = $pengembalian_tmp[$bulan] ?? 0;
}


// Ringkasan
$total_penyewa = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM penyewa"))['total'];
$total_admin = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM admin"))['total'];
$total_barang = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang"))['total'];
$total_barang_disewa = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT SUM(dt.jumlah_barang) AS total 
    FROM detail_transaksi dt 
    JOIN transaksi t ON dt.id_transaksi = t.id_transaksi 
    WHERE t.status = 'Disewa'
"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard - Subang Outdoor</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet" />
    <link href="../assets/css/custom.css" rel="stylesheet" />
</head>
<body id="page-top">
<div id="wrapper">
    <?php include '../layout/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include '../layout/navbar.php'; ?>

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>

                <div class="row">
                    <?php
                    // BAGIAN INI YANG DIUBAH DENGAN MENAMBAHKAN KARTU KE-4
                    $ringkasan = [
                        ['title' => 'Penyewa Terdaftar', 'total' => $total_penyewa, 'icon' => 'fa-users', 'color' => 'primary'],
                        ['title' => 'Barang Tersedia', 'total' => $total_barang, 'icon' => 'fa-boxes', 'color' => 'warning'],
                        ['title' => 'Barang Disewa', 'total' => $total_barang_disewa, 'icon' => 'fa-shopping-cart', 'color' => 'danger'],
                        ['title' => 'Total Admin', 'total' => $total_admin, 'icon' => 'fa-user-shield', 'color' => 'success'],
                    ];
                    foreach ($ringkasan as $data): ?>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-<?= $data['color'] ?> shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-<?= $data['color'] ?> text-uppercase mb-1"><?= $data['title'] ?></div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $data['total'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas <?= $data['icon'] ?> fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="row">
                    <div class="col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-info">Grafik Penyewaan & Pengembalian per Bulan</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height:300px;">
                                    <canvas id="grafikPenyewaan"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Barang Stok Menipis</h6></div>
                            <div class="card-body" style="max-height: 150px; overflow-y: auto;">
                                <ul class="list-group list-group-flush">
                                    <?php if (mysqli_num_rows($barang_menipis) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($barang_menipis)): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?= htmlspecialchars($row['nama_barang']) ?>
                                                <span class="badge badge-danger badge-pill"><?= (int)$row['stok'] ?></span>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-center">Aman, tidak ada stok menipis.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="card shadow mb-4">
                             <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success">Penyewa Teratas</h6></div>
                             <div class="card-body" style="max-height: 150px; overflow-y: auto;">
                                <ul class="list-group list-group-flush">
                                    <?php if (mysqli_num_rows($penyewa_top) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($penyewa_top)): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?= htmlspecialchars($row['nama_penyewa']) ?>
                                                <span class="badge badge-primary badge-pill"><?= (int)$row['total_transaksi'] ?>x sewa</span>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-center">Belum ada data transaksi.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const labelsBulan = <?= json_encode($labels_bulan ?? []) ?>;
    const dataPenyewaan = <?= json_encode($data_penyewaan ?? []) ?>;
    const dataPengembalian = <?= json_encode($data_pengembalian ?? []) ?>;
    const ctx = document.getElementById('grafikPenyewaan').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labelsBulan,
            datasets: [{
                label: 'Jumlah Penyewaan',
                data: dataPenyewaan,
                backgroundColor: '#4e73df',
                borderColor: '#4e73df',
                borderWidth: 1,
                maxBarThickness: 40
            }, {
                label: 'Jumlah Pengembalian',
                data: dataPengembalian,
                backgroundColor: '#1cc88a',
                borderColor: '#1cc88a',
                borderWidth: 1,
                maxBarThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    offset: true
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y + ' transaksi';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>