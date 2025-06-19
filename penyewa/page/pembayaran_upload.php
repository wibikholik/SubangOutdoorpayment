<?php
include '../../route/koneksi.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}
$id_penyewa = $_SESSION['user_id'];

$id_transaksi = isset($_GET['id_transaksi']) ? intval($_GET['id_transaksi']) : null;
if (!$id_transaksi) {
    echo "<script>alert('ID transaksi tidak valid.'); window.location.href='../page/produk.php';</script>";
    exit;
}

// Ambil data transaksi (bisa id_metode = 0 jika user belum pilih metode)
$stmt = $koneksi->prepare("
    SELECT t.*, mp.nama_metode, mp.nomor_rekening, mp.gambar_metode, mp.id_metode
    FROM transaksi t
    LEFT JOIN metode_pembayaran mp ON t.id_metode = mp.id_metode
    WHERE t.id_transaksi = ? AND t.id_penyewa = ?
");
$stmt->bind_param("ii", $id_transaksi, $id_penyewa);
$stmt->execute();
$result_transaksi = $stmt->get_result();
if (!$result_transaksi || $result_transaksi->num_rows === 0) {
    echo "<script>alert('Transaksi tidak ditemukan atau bukan milik Anda.'); window.location.href='../page/produk.php';</script>";
    exit;
}
$transaksi = $result_transaksi->fetch_assoc();

// Ambil daftar metode transfer (tipe metode 'transfer')
$query_metode = $koneksi->query("
    SELECT mp.id_metode, mp.nama_metode, mp.nomor_rekening 
    FROM metode_pembayaran mp
    JOIN tipe_metode tm ON mp.id_tipe = tm.id_tipe
    WHERE LOWER(tm.nama_tipe) LIKE '%transfer%'
");
$metode_transfer = [];
while ($row = $query_metode->fetch_assoc()) {
    $metode_transfer[$row['id_metode']] = $row;
}

// Ambil detail transaksi
$stmt_detail = $koneksi->prepare("
    SELECT dt.*, b.nama_barang, b.gambar 
    FROM detail_transaksi dt
    JOIN barang b ON dt.id_barang = b.id_barang
    WHERE dt.id_transaksi = ?
");
$stmt_detail->bind_param("i", $id_transaksi);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

// Ambil data penyewa
$stmt_penyewa = $koneksi->prepare("SELECT nama_penyewa, no_hp, alamat FROM penyewa WHERE id_penyewa = ?");
$stmt_penyewa->bind_param("i", $id_penyewa);
$stmt_penyewa->execute();
$result_penyewa = $stmt_penyewa->get_result();
$penyewa = $result_penyewa->fetch_assoc();
$stmt_penyewa->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Pembayaran - Subang Outdoor <?= htmlspecialchars($id_transaksi) ?></title>
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css" />
  <link rel="stylesheet" href="css/bootstrap.css">
  <link rel="stylesheet" href="css/main.css">
  <link rel="shortcut icon" href="../../assets/img/logo.jpg">
  <style>
    .barang-img {
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
    }
  </style>
</head>
<body>

<?php include("../layout/navbar1.php"); ?>
<section class="banner-area organic-breadcrumb">
  <div class="container">
    <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
      <div class="col-first">
        <h1>Subang Outdoor</h1>
        <nav class="d-flex align-items-center">
          <p>Pembayaran:</p>
          <p>Nomor Transaksi: <strong><?= htmlspecialchars($id_transaksi) ?></strong></p>
        </nav>
      </div>
    </div>
  </div>
</section>

<div class="w3-container w3-padding-32">
  <div class="w3-padding-32 w3-round-large w3-margin-bottom">

    <div class="w3-row-padding w3-margin-top">
      <!-- Informasi Transaksi -->
      <div class="w3-half">
        <div class="w3-card w3-padding w3-round-large">
          <h4>Informasi Transaksi</h4>
          <p><strong>Total Bayar:</strong><br> Rp <?= number_format($transaksi['total_harga_sewa'], 0, ',', '.') ?></p>
          <p><strong>Status:</strong> <?= htmlspecialchars($transaksi['status']) ?></p>

          <h4>Metode Pembayaran Sebelumnya</h4>
          <?php if (!empty($transaksi['gambar_metode'])): ?>
            <img src="../../metode_pembayaran/metode/gambar/<?= htmlspecialchars($transaksi['gambar_metode']) ?>" 
                 alt="<?= htmlspecialchars($transaksi['nama_metode']) ?>" 
                 style="height: 50px;" class="w3-margin-bottom">
            <p><strong><?= htmlspecialchars($transaksi['nama_metode']) ?></strong></p>
            <p>Nomor Rekening: <?= htmlspecialchars($transaksi['nomor_rekening']) ?></p>
          <?php else: ?>
            <p><em>Belum memilih metode pembayaran</em></p>
          <?php endif; ?>

          <p>
            <strong>Nama Penyewa:</strong> <?= htmlspecialchars($penyewa['nama_penyewa']) ?><br>
            <strong>No. HP:</strong> <?= htmlspecialchars($penyewa['no_hp']) ?><br>
            <strong>Alamat:</strong> <small><?= htmlspecialchars($penyewa['alamat']) ?></small>
          </p>
        </div>
      </div>

      <!-- Daftar Barang -->
      <div class="w3-half">
        <div class="w3-card w3-padding w3-round-large">
          <h4>Daftar Barang</h4>
          <table class="w3-table w3-striped w3-bordered">
            <thead>
              <tr class="w3-light-grey">
                <th>Gambar</th>
                <th>Nama</th>
                <th>Jumlah</th>
                <th>Harga</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $result_detail->fetch_assoc()) : ?>
              <tr>
                <td><img src="../../barang/barang/gambar/<?= htmlspecialchars($row['gambar']) ?>" class="barang-img" alt="<?= htmlspecialchars($row['nama_barang']) ?>"></td>
                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                <td><?= (int)$row['jumlah_barang'] ?></td>
                <td>Rp <?= number_format($row['harga_satuan'], 0, ',', '.') ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Upload Bukti -->
    <div class="w3-card w3-padding w3-margin-top w3-round-large">
      <h4>Upload Bukti Pembayaran</h4>
      <form action="../controller/prosesPembayaran.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id_transaksi" value="<?= htmlspecialchars($id_transaksi) ?>">

        <label for="id_metode">Pilih Metode Transfer:</label>
        <select class="w3-select w3-margin-bottom" name="id_metode" id="id_metode" required>
          <option value="">-- Pilih Metode --</option>
          <?php foreach ($metode_transfer as $idMetode => $m) : ?>
            <option value="<?= $idMetode ?>" <?= ($idMetode == $transaksi['id_metode']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($m['nama_metode']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div class="w3-margin-bottom">
          <strong>Nomor Rekening:</strong>
          <div class="w3-padding w3-border w3-round w3-pale-yellow" id="nomor_rekening_display">
            <?= isset($metode_transfer[$transaksi['id_metode']]) ? htmlspecialchars($metode_transfer[$transaksi['id_metode']]['nomor_rekening']) : 'Pilih metode pembayaran terlebih dahulu' ?>
          </div>
        </div>

        <input class="w3-input w3-border w3-margin-bottom" type="file" name="bukti_pembayaran" required accept="image/*,application/pdf" />
        <button type="submit" class="w3-button w3-green w3-round">Upload</button>
      </form>
    </div>
  </div>
</div>

<?php include ('../layout/footer.php'); ?>

<script>
  const dataMetode = <?= json_encode($metode_transfer) ?>;
  const selectMetode = document.getElementById('id_metode');
  const rekeningDisplay = document.getElementById('nomor_rekening_display');

  function updateRekening() {
    const selected = selectMetode.value;
    if (dataMetode[selected]) {
      rekeningDisplay.textContent = dataMetode[selected]['nomor_rekening'];
    } else {
      rekeningDisplay.textContent = 'Pilih metode pembayaran terlebih dahulu';
    }
  }

  updateRekening();

  selectMetode.addEventListener('change', updateRekening);
</script>

</body>
</html>
