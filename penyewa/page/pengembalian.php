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
    die("ID transaksi tidak valid.");
}

// Ambil data transaksi utama
$query = "SELECT * FROM transaksi WHERE id_transaksi = $id_transaksi AND id_penyewa = $id_penyewa";
$result = mysqli_query($koneksi, $query);
if (!$result) {
    die("Query transaksi error: " . mysqli_error($koneksi));
}
$data = mysqli_fetch_assoc($result);
if (!$data) {
    die("Transaksi tidak ditemukan.");
}

// Ambil nama penyewa
$query_penyewa = "
    SELECT u.nama_penyewa
    FROM penyewa u
    JOIN transaksi t ON u.id_penyewa = t.id_penyewa
    WHERE t.id_transaksi = $id_transaksi AND t.id_penyewa = $id_penyewa
";
$result_penyewa = mysqli_query($koneksi, $query_penyewa);
if (!$result_penyewa) {
    die("Query penyewa error: " . mysqli_error($koneksi));
}
$penyewa = mysqli_fetch_assoc($result_penyewa);

// Ambil nomor rekening metode pembayaran
$query_rekening = "
    SELECT m.nomor_rekening
    FROM transaksi t
    JOIN metode_pembayaran m ON t.id_metode = m.id_metode
    WHERE t.id_transaksi = $id_transaksi
";
$result_metode = mysqli_query($koneksi, $query_rekening);
if (!$result_metode) {
    die("Query metode error: " . mysqli_error($koneksi));
}
$metode = mysqli_fetch_assoc($result_metode);

// Ambil data barang yang disewa
$query_barang = "
    SELECT dt.*, b.nama_barang, b.gambar, b.kategori
    FROM detail_transaksi dt
    JOIN barang b ON dt.id_barang = b.id_barang
    WHERE dt.id_transaksi = $id_transaksi
";
$result_barang = mysqli_query($koneksi, $query_barang);
if (!$result_barang) {
    die("Query detail barang error: " . mysqli_error($koneksi));
}
$barang_list = [];
while ($row = mysqli_fetch_assoc($result_barang)) {
    $barang_list[] = $row;
}

// Hitung lama sewa dan denda
$tanggal_kembali = new DateTime($data['tanggal_kembali']);
$tanggal_sewa = new DateTime($data['tanggal_sewa']);
$tanggal_sekarang = new DateTime();

$lama_sewa = $tanggal_sewa->diff($tanggal_kembali)->days;
$harga_per_hari = $lama_sewa > 0 ? $data['total_harga_sewa'] / $lama_sewa : 0;

$selisih_hari = (strtotime($tanggal_sekarang->format('Y-m-d')) - strtotime($tanggal_kembali->format('Y-m-d'))) / 86400;
$terlambat = $selisih_hari > 0 ? floor($selisih_hari) : 0;
$denda = $harga_per_hari * $terlambat;

// Ambil data checklist kondisi barang
$query_checklist = "
    SELECT c.id_checklist, c.id_kelengkapan, k.nama_kelengkapan, 
           c.status_awal, c.keterangan_awal, 
           IFNULL(c.status_akhir, '') AS status_akhir, 
           IFNULL(c.keterangan_akhir, '') AS keterangan_akhir
    FROM checklist c
    JOIN kelengkapan_barang k ON c.id_kelengkapan = k.id_kelengkapan
    WHERE c.id_transaksi = $id_transaksi
";
$result_checklist = mysqli_query($koneksi, $query_checklist);
$checklist_list = [];
while ($row = mysqli_fetch_assoc($result_checklist)) {
    $checklist_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pengembalian - Subang Outdoor</title>
  <link rel="stylesheet" href="css/linearicons.css" />
  <link rel="stylesheet" href="css/owl.carousel.css" />
  <link rel="stylesheet" href="css/themify-icons.css" />
  <link rel="stylesheet" href="css/font-awesome.min.css" />
  <link rel="stylesheet" href="css/nice-select.css" />
  <link rel="stylesheet" href="css/nouislider.min.css" />
  <link rel="stylesheet" href="css/bootstrap.css" />
  <link rel="stylesheet" href="css/main.css" />
  <link rel="shortcut icon" href="../../assets/img/logo.jpg" />
</head>
<body>
<?php include("../layout/navbar1.php"); ?>

<section class="banner-area organic-breadcrumb">
  <div class="container">
    <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
      <div class="col-first">
        <h1>Subang Outdoor</h1>
        <nav class="d-flex align-items-center">
          <p>Pengembalian:</p>
          <p>Nomor Transaksi: <strong><?= htmlspecialchars($id_transaksi) ?></strong></p>
        </nav>
      </div>
    </div>
  </div>
</section>

<section class="container mt-5 mb-5">
  <div class="card p-4">
    <h3>Informasi Transaksi</h3>
    <p><strong>Nama Penyewa:</strong> <?= htmlspecialchars($penyewa['nama_penyewa'] ?? 'Data tidak tersedia') ?></p>
    <p><strong>Tanggal Sewa:</strong> <?= htmlspecialchars($data['tanggal_sewa']) ?></p>
    <p><strong>Tanggal Kembali:</strong> <?= htmlspecialchars($data['tanggal_kembali']) ?></p>
    <p><strong>Lama Periode Sewa:</strong> <?= $lama_sewa; ?> hari</p>
    <p><strong>Total Harga Sewa:</strong> Rp <?= number_format($data['total_harga_sewa'], 0, ',', '.') ?></p>

    <h4 class="mt-4">Barang yang Disewa</h4>
    <?php if (count($barang_list) > 0): ?>
      <ul class="list-group mb-4">
        <?php foreach ($barang_list as $barang): ?>
          <li class="list-group-item d-flex align-items-center">
            <?php if ($barang['gambar']): ?>
              <img src="../../barang/barang/gambar/<?= htmlspecialchars($barang['gambar']) ?>" alt="<?= htmlspecialchars($barang['nama_barang']) ?>" style="width: 60px; height: 40px; object-fit: cover; margin-right: 15px;">
            <?php endif; ?>
            <div>
              <strong><?= htmlspecialchars($barang['nama_barang']) ?></strong><br>
              Jumlah Barang: <?= htmlspecialchars($barang['jumlah_barang']) ?><br>
              <?= nl2br(htmlspecialchars($barang['kategori'])) ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Tidak ada barang yang disewa.</p>
    <?php endif; ?>

    <?php if ($terlambat > 0): ?>
      <div class="alert alert-danger">
        Anda terlambat mengembalikan barang selama <strong><?= $terlambat ?></strong> hari.<br>
        Denda yang harus dibayar: <strong>Rp <?= number_format($denda, 0, ',', '.') ?></strong>
      </div>
    <?php endif; ?>

    <h2>Form Pengembalian Barang</h2>
    <form action="../controller/prosesPengembalian.php" method="post">
      <input type="hidden" name="id_transaksi" value="<?= $id_transaksi ?>">

      <?php if (count($checklist_list) > 0): ?>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Nama Kelengkapan</th>
            <th>Status Awal</th>
            <th>Status Akhir</th>
            <th>Keterangan Akhir</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($checklist_list as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['nama_kelengkapan']) ?></td>
            <td>
              <?= htmlspecialchars($item['status_awal']) ?>
              <?= $item['keterangan_awal'] ? "({$item['keterangan_awal']})" : "" ?>
            </td>
            <td>
              <input type="hidden" name="id_checklist[]" value="<?= $item['id_checklist'] ?>">
              <select name="status_akhir[]" class="form-select" required>
                <option value="">-- Pilih Status --</option>
                <option value="ada" <?= ($item['status_akhir'] == 'ada') ? 'selected' : '' ?>>Ada</option>
                <option value="hilang" <?= ($item['status_akhir'] == 'hilang') ? 'selected' : '' ?>>Hilang</option>
                <option value="rusak" <?= ($item['status_akhir'] == 'rusak') ? 'selected' : '' ?>>Rusak</option>
              </select>
            </td>
            <td>
              <input type="text" name="keterangan_akhir[]" class="form-control" placeholder="Opsional" value="<?= htmlspecialchars($item['keterangan_akhir']) ?>">
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p>Tidak ada data checklist kelengkapan untuk transaksi ini.</p>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary">Simpan Pengembalian</button>
    </form>
  </div>
</section>

<!-- Script JS -->
<script src="js/vendor/jquery-2.2.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="js/vendor/bootstrap.min.js"></script>
<script src="js/jquery.ajaxchimp.min.js"></script>
<script src="js/jquery.nice-select.min.js"></script>
<script src="js/jquery.sticky.js"></script>
<script src="js/nouislider.min.js"></script>
<script src="js/jquery.magnific-popup.min.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/gmaps.min.js"></script>
<script src="js/main.js"></script>
</body>

<?php include("../layout/footer.php"); ?>
</html>
