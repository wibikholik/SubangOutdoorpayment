<?php
include '../../route/koneksi.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_transaksi = isset($_GET['id_transaksi']) ? intval($_GET['id_transaksi']) : 0;

$query_transaksi = "
    SELECT t.*, mp.nama_metode, mp.nomor_rekening, tm.nama_tipe
    FROM transaksi t
    LEFT JOIN metode_pembayaran mp ON t.id_metode = mp.id_metode
    LEFT JOIN tipe_metode tm ON t.id_tipe = tm.id_tipe
    WHERE t.id_transaksi = ? AND t.id_penyewa = ?
";
$stmt = $koneksi->prepare($query_transaksi);
$stmt->bind_param("ii", $id_transaksi, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$transaksi = $result->fetch_assoc();

if (!$transaksi) {
    echo "<script>alert('Transaksi tidak ditemukan.'); window.history.back();</script>";
    exit;
}

// Ambil detail barang
$query_detail = "
    SELECT dt.*, b.nama_barang, b.gambar
    FROM detail_transaksi dt
    JOIN barang b ON dt.id_barang = b.id_barang
    WHERE dt.id_transaksi = ?
";
$stmt_detail = $koneksi->prepare($query_detail);
$stmt_detail->bind_param("i", $id_transaksi);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Transaksi - Subang Outdoor</title>
  <link rel="stylesheet" href="css/bootstrap.css">
  <link rel="stylesheet" href="css/main.css">
  <link rel="shortcut icon" href="../../assets/img/logo.jpg">
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include("../layout/navbar1.php"); ?>

<section class="banner-area organic-breadcrumb">
  <div class="container">
    <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
      <div class="col-first">
        <h1>Detail Transaksi</h1>
        <nav class="d-flex align-items-center">
          <a href="riwayat_transaksi.php">Kembali</a>
          <span class="lnr lnr-arrow-right"></span>
          <a href="#">Transaksi #<?= $id_transaksi ?></a>
        </nav>
      </div>
    </div>
  </div>
</section>

<div class="container mt-5 mb-5">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h5>Transaksi #<?= $id_transaksi ?> | Status: <?= htmlspecialchars($transaksi['status']) ?></h5>
    </div>
    <div class="card-body">
      <p><strong>Metode Pembayaran:</strong> <?= htmlspecialchars($transaksi['nama_metode'] ?? '-') ?></p>
      <p><strong>Nomor Rekening:</strong> <?= htmlspecialchars($transaksi['nomor_rekening'] ?? '-') ?></p>
      <p><strong>Tipe Pembayaran:</strong> <?= htmlspecialchars($transaksi['nama_tipe'] ?? '-') ?></p>
      <p><strong>Periode Sewa:</strong>
        <?= (new DateTime($transaksi['tanggal_sewa']))->format('d M Y') ?> -
        <?= (new DateTime($transaksi['tanggal_kembali']))->format('d M Y') ?>
      </p>
      <p><strong>Lama Sewa:</strong>
        <?= (new DateTime($transaksi['tanggal_sewa']))->diff(new DateTime($transaksi['tanggal_kembali']))->days ?> hari
      </p>

      <hr>
      <h5 class="mt-4">Detail Barang Disewa</h5>
      <?php if ($result_detail->num_rows > 0): ?>
        <div class="table-responsive mt-3">
          <table class="table table-bordered">
            <thead class="thead-light">
              <tr>
                <th>Gambar</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Harga Satuan</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $result_detail->fetch_assoc()) : ?>
                <tr>
                  <td><img src="../../barang/barang/gambar/<?= htmlspecialchars($row['gambar']); ?>" alt="Barang" style="width: 70px;"></td>
                  <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                  <td><?= $row['jumlah_barang'] ?></td>
                  <td>Rp<?= number_format($row['harga_satuan'], 0, ',', '.') ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-muted">Tidak ada detail barang ditemukan.</div>
      <?php endif; ?>

      <div class="mt-4">
        <h5>Total Harga Sewa</h5>
        <p class="h5 text-primary">Rp<?= number_format($transaksi['total_harga_sewa'], 0, ',', '.') ?></p>
      </div>

      <a href="transaksi.php" class="btn btn-secondary mt-3">← Kembali ke Riwayat</a>
    </div>
  </div>
</div>

<?php include("../layout/footer.php"); ?>

<script src="js/vendor/jquery-2.2.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/jquery.ajaxchimp.min.js"></script>
<script src="js/jquery.nice-select.min.js"></script>
<script src="js/jquery.sticky.js"></script>
<script src="js/nouislider.min.js"></script>
<script src="js/jquery.magnific-popup.min.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/gmaps.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
