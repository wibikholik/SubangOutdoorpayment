<?php
include '../../route/koneksi.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = $_SESSION['user_id'];

// Query gabungan dengan LEFT JOIN ke metode_pembayaran dan tipe_metode agar nama_tipe tersedia
// Perbaikan: tipe_metode di JOIN berdasarkan t.id_tipe, bukan mp.id_tipe
$query_transaksi = "
    SELECT t.*, mp.nama_metode, mp.gambar_metode, mp.nomor_rekening, tm.nama_tipe
    FROM transaksi t
    LEFT JOIN metode_pembayaran mp ON t.id_metode = mp.id_metode
    LEFT JOIN tipe_metode tm ON t.id_tipe = tm.id_tipe
    WHERE t.id_penyewa = ?
    ORDER BY t.id_transaksi DESC
";

$stmt = $koneksi->prepare($query_transaksi);
if (!$stmt) {
    die("Prepare query transaksi gagal: " . $koneksi->error);
}
$stmt->bind_param("i", $id_penyewa);
$stmt->execute();
$result_transaksi = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Histori Transaksi - Subang Outdoor</title>
  <link rel="stylesheet" href="css/bootstrap.css">
  <link rel="stylesheet" href="css/main.css">
  <link rel="shortcut icon" href="../../assets/img/logo.jpg">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include("../layout/navbar1.php"); ?>

<section class="banner-area organic-breadcrumb">
  <div class="container">
    <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
      <div class="col-first">
        <h1>Subang Outdoor</h1>
        <nav class="d-flex align-items-center">
          <a href="#">Histori Penyewaan</a>
        </nav>
      </div>
    </div>
  </div>
</section>

<div class="container mt-4">
  <h4>Histori Transaksi Anda</h4>

  <?php if ($result_transaksi->num_rows < 1): ?>
    <div class="alert alert-info">Belum ada transaksi.</div>
  <?php endif; ?>

  <div class="d-flex flex-wrap gap-4" style="gap: 10px;">
    <?php while ($transaksi = $result_transaksi->fetch_assoc()) : ?>
      <?php
        $status = trim($transaksi['status']);
        $id_transaksi = $transaksi['id_transaksi']; 
        $tanggal_sewa = new DateTime($transaksi['tanggal_sewa']);
        $tanggal_kembali = new DateTime($transaksi['tanggal_kembali']);
        $lama_sewa = $tanggal_sewa->diff($tanggal_kembali)->days;

        // Ambil detail barang per transaksi
        $query_detail = "
          SELECT dt.*, b.nama_barang, b.gambar 
          FROM detail_transaksi dt
          JOIN barang b ON dt.id_barang = b.id_barang
          WHERE dt.id_transaksi = ?
        ";
        $stmt_detail = $koneksi->prepare($query_detail);
        $result_detail = false;
        if ($stmt_detail) {
            $stmt_detail->bind_param("i", $id_transaksi);
            $stmt_detail->execute();
            $result_detail = $stmt_detail->get_result();
        }

        // Pastikan nama_tipe aman dan bebas spasi
        $tipe = strtolower(trim($transaksi['nama_tipe'] ?? ''));
      ?>

      <div class="card mb-4 shadow-sm p-3" style="min-width: 360px; max-width: 520px; flex: 1;">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <strong>ID Transaksi:</strong> <?= htmlspecialchars($id_transaksi); ?><br>
            <strong>Status:</strong> <?= htmlspecialchars($status); ?><br>
            <strong>Metode:</strong> <?= htmlspecialchars($transaksi['nama_metode'] ?? 'Tidak ada metode'); ?><br>
            <strong>Tipe Pembayaran:</strong> <?= htmlspecialchars($transaksi['nama_tipe'] ?? '-'); ?>
          </div>
        </div>

        <div class="card-body">
          <div><strong>Periode Sewa:</strong> <?= $tanggal_sewa->format('d M Y'); ?> - <?= $tanggal_kembali->format('d M Y'); ?></div>
          <div><strong>Lama Sewa:</strong> <?= $lama_sewa; ?> hari</div>

          <?php if ($result_detail && $result_detail->num_rows > 0): ?>
            <table class="table mt-2">
              <thead>
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
                    <td><img src="../../barang/barang/gambar/<?= htmlspecialchars($row['gambar']); ?>" alt="Barang" style="width: 80px;"></td>
                    <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                    <td><?= htmlspecialchars($row['jumlah_barang']); ?></td>
                    <td>Rp<?= number_format($row['harga_satuan'], 0, ',', '.'); ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="text-muted">Tidak ada detail barang.</div>
          <?php endif; ?>

          <div class="d-flex justify-content-between mt-3 align-items-center">
  <div><strong>Total:</strong> Rp<?= number_format($transaksi['total_harga_sewa'], 0, ',', '.'); ?></div>
  <div>
    <?php if ($tipe === 'online' && strtolower($status) === 'belumbayar' && !empty($transaksi['snap_token'])): ?>
      <form action="pembayaran.php" method="GET" class="d-inline">
        <input type="hidden" name="id_transaksi" value="<?= $id_transaksi; ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($transaksi['snap_token']); ?>"> 
        <button type="submit" class="btn btn-primary btn-sm">Bayar Sekarang</button>
      </form>
    <?php elseif ($tipe === 'transfer langsung' && strtolower($status) === 'belumbayar'): ?>
      <a href="pembayaran_upload.php?id_transaksi=<?= $id_transaksi ?>&pilih=1" class="btn btn-sm btn-success">Upload Bukti Transfer</a>
    <?php elseif (strtolower($status) === 'belumbayar'): ?>
      <span class="text-muted">Menunggu pembayaran langsung.</span>
    <?php endif; ?>

    <!-- Tombol Batalkan untuk transaksi yang belum dibayar -->
    <?php if (in_array(strtolower($status), ['belumbayar', 'menunggu pembayaran', 'menunggu konfirmasi pembayaran'])): ?>
      <form action="batal_transaksi.php" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin membatalkan transaksi ini?');">
        <input type="hidden" name="id_transaksi" value="<?= $id_transaksi; ?>">
        <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan</button>
      </form>
    <?php endif; ?>

    <?php if (in_array(strtolower($status), ['dikonfirmasi pembayaran silahkan ambilbarang', 'dikonfirmasi (silahkan ambil barang)'])): ?>
      <?php if ($transaksi['status_checklist'] == 0): ?>
        <a href="checklist.php?id_transaksi=<?= $id_transaksi ?>" class="btn btn-sm btn-warning">Form pengambilan Barang</a>
      <?php else: ?>
        <span>Sudah Dicek</span>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (in_array(strtolower($status), ['disewa', 'terlambat dikembalikan'])): ?>
      <a href="pengembalian.php?id_transaksi=<?= $id_transaksi ?>" class="btn btn-sm btn-danger ms-2">Pengembalian</a>
    <?php endif; ?>

    <?php
    if (strcasecmp($status, 'Ditolak Pengembalian') === 0) {
        $query_denda = "
          SELECT id_pengembalian, denda, snap_token 
          FROM pengembalian 
          WHERE id_transaksi = ? 
          ORDER BY id_pengembalian DESC LIMIT 1
        ";
        $stmt_denda = $koneksi->prepare($query_denda);

        if ($stmt_denda) {
            $stmt_denda->bind_param("i", $id_transaksi);
            $stmt_denda->execute();
            $result_denda = $stmt_denda->get_result();

            if ($result_denda && $row_denda = $result_denda->fetch_assoc()) {
                $denda = (float) $row_denda['denda'];
                $snap_token_denda = $row_denda['snap_token'];
                $id_pengembalian = $row_denda['id_pengembalian'];

                if ($denda < 0 || $denda > 1000000) {
                    $denda = 0;
                }

                if ($denda > 0 && $snap_token_denda && $id_pengembalian) {
                    ?>
                    <form action="pembayaran_denda.php" method="GET" class="d-inline">
                      <input type="hidden" name="id_pengembalian" value="<?= htmlspecialchars($id_pengembalian); ?>">
                      <button type="submit" class="btn btn-sm btn-danger ms-2">
                          Bayar Denda: Rp<?= number_format($denda, 0, ',', '.'); ?>
                      </button>
                    </form>
                    <?php
                } else {
                    echo '<span class="text-danger ms-2">Token pembayaran denda belum tersedia.</span>';
                }
            } else {
                echo '<span class="text-muted ms-2">Data denda tidak ditemukan.</span>';
            }
            $stmt_denda->close();
        }
    }
    ?>
  </div>
</div>

        </div>
      </div>

    <?php endwhile; ?>
  </div>
</div>

<?php include('../layout/footer.php'); ?>

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
