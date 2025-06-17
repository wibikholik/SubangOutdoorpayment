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
$query = "SELECT * FROM transaksi WHERE id_transaksi = ? AND id_penyewa = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("ii", $id_transaksi, $id_penyewa);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Transaksi tidak ditemukan.");
}

// Ambil nama penyewa
$query_penyewa = "SELECT nama_penyewa FROM penyewa WHERE id_penyewa = ?";
$stmt2 = $koneksi->prepare($query_penyewa);
$stmt2->bind_param("i", $id_penyewa);
$stmt2->execute();
$result_penyewa = $stmt2->get_result();
$penyewa = $result_penyewa->fetch_assoc();
$stmt2->close();

// Ambil nomor rekening metode pembayaran
$query_rekening = "SELECT nomor_rekening FROM metode_pembayaran WHERE id_metode = ?";
$stmt3 = $koneksi->prepare($query_rekening);
$stmt3->bind_param("i", $data['id_metode']);
$stmt3->execute();
$result_metode = $stmt3->get_result();
$metode = $result_metode->fetch_assoc();
$stmt3->close();

// Ambil data barang yang disewa
$query_barang = "
    SELECT dt.*, b.nama_barang, b.gambar, b.kategori
    FROM detail_transaksi dt
    JOIN barang b ON dt.id_barang = b.id_barang
    WHERE dt.id_transaksi = ?
";
$stmt4 = $koneksi->prepare($query_barang);
$stmt4->bind_param("i", $id_transaksi);
$stmt4->execute();
$result_barang = $stmt4->get_result();
$barang_list = $result_barang->fetch_all(MYSQLI_ASSOC);
$stmt4->close();

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
    WHERE c.id_transaksi = ?
";
$stmt5 = $koneksi->prepare($query_checklist);
$stmt5->bind_param("i", $id_transaksi);
$stmt5->execute();
$result_checklist = $stmt5->get_result();
$checklist_list = $result_checklist->fetch_all(MYSQLI_ASSOC);
$stmt5->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pengembalian - Subang Outdoor</title>
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
    <form action="../controller/prosesPengembalian.php" method="post" onsubmit="return validateForm()">
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
              <option value="tidak ada" <?= ($item['status_akhir'] == 'tidak ada') ? 'selected' : '' ?>>Tidak Ada</option>
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

<script>
function validateForm() {
  const selects = document.querySelectorAll('select[name="status_akhir[]"]');
  for (const select of selects) {
    if (!select.value) {
      alert('Semua status akhir harus dipilih!');
      select.focus();
      return false;
    }
  }
  return true;
}
</script>

<script src="js/vendor/jquery-2.2.4.min.js"></script>
<script src="js/vendor/bootstrap.min.js"></script>
<script src="js/main.js"></script>
</body>

<?php include("../layout/footer.php"); ?>
</html>
