<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

// Data penyewa untuk dropdown
$query_penyewa = "SELECT id_penyewa, nama_penyewa FROM penyewa ORDER BY nama_penyewa";
$result_penyewa = mysqli_query($koneksi, $query_penyewa);

// Data barang untuk dropdown
$query_barang = "SELECT id_barang, nama_barang, harga_sewa FROM barang ORDER BY nama_barang";
$result_barang = mysqli_query($koneksi, $query_barang);

// Data metode pembayaran
$query_metode = "SELECT id_metode, nama_metode FROM metode_pembayaran ORDER BY nama_metode";
$result_metode = mysqli_query($koneksi, $query_metode);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Tambah Transaksi - Subang Outdoor</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet" />
</head>
<body id="page-top">

<div id="wrapper">

    <?php include '../layout/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include '../layout/navbar.php'; ?>

            <div class="container-fluid">

                <h1 class="h3 mb-4 text-gray-800">Tambah Transaksi</h1>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form action="proses_tambah_transaksi.php" method="POST">

                            <div class="mb-3">
                                <label for="id_penyewa" class="form-label">Pilih Penyewa (Jika sudah terdaftar)</label>
                                <select name="id_penyewa" id="id_penyewa" class="form-control">
                                    <option value="">-- Pilih Penyewa --</option>
                                    <?php while ($row = mysqli_fetch_assoc($result_penyewa)): ?>
                                        <option value="<?= $row['id_penyewa'] ?>"><?= htmlspecialchars($row['nama_penyewa']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="nama_baru" class="form-label">Atau input nama penyewa baru</label>
                                <input type="text" name="nama_baru" id="nama_baru" class="form-control" placeholder="Nama Penyewa Baru" />
                                <small class="form-text text-muted">Jika memilih ini, penyewa baru akan dibuat otomatis.</small>
                            </div>

                            <div class="mb-3">
                                <label for="id_barang" class="form-label">Pilih Barang</label>
                                <select name="id_barang" id="id_barang" class="form-control" required>
                                    <option value="">-- Pilih Barang --</option>
                                    <?php
                                    // reset pointer to re-fetch barang if needed
                                    mysqli_data_seek($result_barang, 0);
                                    while ($row = mysqli_fetch_assoc($result_barang)): ?>
                                        <option value="<?= $row['id_barang'] ?>" data-harga="<?= $row['harga_sewa'] ?>">
                                            <?= htmlspecialchars($row['nama_barang']) ?> (Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="jumlah_barang" class="form-label">Jumlah Barang</label>
                                <input type="number" name="jumlah_barang" id="jumlah_barang" class="form-control" value="1" min="1" required />
                            </div>

                            <div class="mb-3">
                                <label for="id_metode" class="form-label">Metode Pembayaran</label>
                                <select name="id_metode" id="id_metode" class="form-control" required>
                                    <option value="">-- Pilih Metode Pembayaran --</option>
                                    <?php
                                    mysqli_data_seek($result_metode, 0);
                                    while ($row = mysqli_fetch_assoc($result_metode)): ?>
                                        <option value="<?= $row['id_metode'] ?>"><?= htmlspecialchars($row['nama_metode']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
    <label for="tanggal_sewa" class="form-label">Tanggal Sewa</label>
    <input type="date" name="tanggal_sewa" id="tanggal_sewa" class="form-control" 
        required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" />
</div>

<div class="mb-3">
    <label for="tanggal_kembali" class="form-label">Tanggal Kembali</label>
    <input type="date" name="tanggal_kembali" id="tanggal_kembali" class="form-control" 
        required min="<?= date('Y-m-d') ?>" />
</div>


                            <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                            <a href="transaksi.php" class="btn btn-secondary">Batal</a>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>
<script>
    const tanggalSewa = document.getElementById('tanggal_sewa');
    const tanggalKembali = document.getElementById('tanggal_kembali');

    tanggalSewa.addEventListener('change', function () {
        tanggalKembali.min = this.value;
    });
</script>

</body>
</html>
