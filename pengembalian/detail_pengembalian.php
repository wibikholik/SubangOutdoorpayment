<?php
session_start();
include '../route/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

if (!isset($_GET['id_pengembalian'])) {
    die("ID pengembalian tidak ditemukan.");
}

$id_pengembalian = intval($_GET['id_pengembalian']);

// Ambil data pengembalian + transaksi + penyewa
$query = "SELECT p.*, t.tanggal_sewa, t.tanggal_kembali, t.id_transaksi, u.nama_penyewa 
          FROM pengembalian p
          JOIN transaksi t ON p.id_transaksi = t.id_transaksi
          JOIN penyewa u ON t.id_penyewa = u.id_penyewa
          WHERE p.id_pengembalian = $id_pengembalian";

$result = mysqli_query($koneksi, $query);
$pengembalian = mysqli_fetch_assoc($result);

if (!$pengembalian) {
    die("Data pengembalian tidak ditemukan.");
}

// Ambil data detail barang (detail_transaksi + barang)
$queryDetailBarang = "
    SELECT dt.*, b.nama_barang 
    FROM detail_transaksi dt
    JOIN barang b ON dt.id_barang = b.id_barang
    WHERE dt.id_transaksi = " . intval($pengembalian['id_transaksi']);
$detailBarangResult = mysqli_query($koneksi, $queryDetailBarang);

if (!$detailBarangResult) {
    die("Query detail barang gagal: " . mysqli_error($koneksi));
}

// Ambil data checklist perlengkapan
$queryChecklist = "
    SELECT c.*, kb.nama_kelengkapan
    FROM checklist c
    JOIN kelengkapan_barang kb ON c.id_kelengkapan = kb.id_kelengkapan
    WHERE c.id_transaksi = " . intval($pengembalian['id_transaksi']);
$checklistResult = mysqli_query($koneksi, $queryChecklist);

if (!$checklistResult) {
    die("Query checklist gagal: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Detail Pengembalian - Subang Outdoor</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,400,700" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../layout/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../layout/navbar.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Detail Pengembalian</h1>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <p><strong>Nama Penyewa:</strong> <?= htmlspecialchars($pengembalian['nama_penyewa']) ?></p>
                            <p><strong>ID Transaksi:</strong> <?= htmlspecialchars($pengembalian['id_transaksi']) ?></p>
                            <p><strong>Tanggal Sewa:</strong> <?= htmlspecialchars($pengembalian['tanggal_sewa']) ?></p>
                            <p><strong>Tanggal Kembali:</strong> <?= htmlspecialchars($pengembalian['tanggal_kembali']) ?></p>
                            <p><strong>Tanggal Pengembalian:</strong> <?= htmlspecialchars($pengembalian['tanggal_pengembalian']) ?></p>
                            <p><strong>Status Pengembalian:</strong> <?= htmlspecialchars($pengembalian['status_pengembalian']) ?></p>
                            <p><strong>Denda:</strong> Rp<?= number_format($pengembalian['denda'], 0, ',', '.') ?></p>
                            <hr>

                            <h5>Detail Barang</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID Transaksi</th>
                                            <th>Nama Barang</th>
                                            <th>Jumlah</th>
                                            <th>Harga Satuan (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($detailBarangResult) > 0): ?>
                                            <?php while ($item = mysqli_fetch_assoc($detailBarangResult)): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['id_transaksi']) ?></td>
                                                    <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                                                    <td><?= htmlspecialchars($item['jumlah_barang']) ?></td>
                                                    <td><?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Data detail barang tidak ditemukan.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <h5>Checklist Perlengkapan</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID Transaksi</th>
                                            <th>Nama Perlengkapan</th>
                                            <th>Kondisi Awal</th>
                                            <th>Kondisi Akhir</th>
                                            <th>Keterangan Awal</th>
                                            <th>Keterangan Akhir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($checklistResult) > 0): ?>
                                            <?php while ($item = mysqli_fetch_assoc($checklistResult)): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['id_transaksi']) ?></td>
                                                    <td><?= htmlspecialchars($item['nama_kelengkapan']) ?></td>
                                                    <td><?= htmlspecialchars($item['status_awal']) ?></td>
                                                    <td><?= htmlspecialchars($item['status_akhir']) ?></td>
                                                    <td><?= htmlspecialchars($item['keterangan_awal']) ?></td>
                                                    <td><?= htmlspecialchars($item['keterangan_akhir']) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Data checklist perlengkapan tidak ditemukan.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($pengembalian['status_pengembalian']): ?>
                                <form action="proses_pengembalian.php" method="POST" class="mt-4">
                                    <input type="hidden" name="id_pengembalian" value="<?= $id_pengembalian ?>">
                                    <div class="form-group">
                                        <label for="status_pengembalian">Status Pengembalian</label>
                                        <select name="status_pengembalian" id="status_pengembalian" class="form-control" required>
                                            <option value="Selesai Dikembalikan">Selesai Dikembalikan</option>
                                            <option value="Ditolak Pengembalian">Ditolak Pengembalian</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="denda">Denda (Rp)</label>
                                        <input type="number" min="0" name="denda" id="denda" class="form-control" value="<?= $pengembalian['denda'] ?? 0 ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="catatan">Catatan</label>
                                        <textarea name="catatan" id="catatan" rows="3" class="form-control"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">Konfirmasi Pengembalian</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-info mt-4" role="alert">
                                    Status pengembalian: <strong><?= htmlspecialchars($pengembalian['status_pengembalian']) ?></strong>
                                </div>
                                <?php if (!empty($pengembalian['catatan'])): ?>
                                    <div class="alert alert-secondary mt-2">
                                        <strong>Catatan:</strong> <?= nl2br(htmlspecialchars($pengembalian['catatan'])) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sb-admin-2.min.js"></script>
</body>

</html>
