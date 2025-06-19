<?php
session_start();
include '../../route/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = (int)$_SESSION['user_id'];
$id_transaksi = isset($_GET['id_transaksi']) ? (int)$_GET['id_transaksi'] : 0;
$snap_token = $_GET['token'] ?? null;

if ($id_transaksi === 0 || !$snap_token) {
    echo "<script>alert('Data transaksi tidak ditemukan.'); window.location.href='../page/produk.php';</script>";
    exit;
}

// Ambil data transaksi (dengan tipe metode)
$sql = "SELECT t.*, m.nama_metode, tm.nama_tipe AS tipe_metode, p.nama_penyewa 
        FROM transaksi t
        JOIN metode_pembayaran m ON t.id_metode = m.id_metode
        JOIN tipe_metode tm ON m.id_tipe = tm.id_tipe
        JOIN penyewa p ON t.id_penyewa = p.id_penyewa
        WHERE t.id_transaksi = ? AND t.id_penyewa = ?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id_transaksi, $id_penyewa);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) === 0) {
    echo "<script>alert('Transaksi tidak ditemukan.'); window.location.href='../page/produk.php';</script>";
    exit;
}
$transaksi = mysqli_fetch_assoc($result);

// Validasi status transaksi
if (strtolower($transaksi['status']) !== 'belumbayar') {
    echo "<script>alert('Transaksi ini sudah diproses atau dibayar.'); window.location.href='../page/transaksi.php';</script>";
    exit;
}

// Ambil detail transaksi
$sqlDetail = "SELECT d.*, b.nama_barang 
              FROM detail_transaksi d 
              JOIN barang b ON d.id_barang = b.id_barang 
              WHERE d.id_transaksi = ?";
$stmtDetail = mysqli_prepare($koneksi, $sqlDetail);
mysqli_stmt_bind_param($stmtDetail, "i", $id_transaksi);
mysqli_stmt_execute($stmtDetail);
$resultDetail = mysqli_stmt_get_result($stmtDetail);

$details = [];
while ($row = mysqli_fetch_assoc($resultDetail)) {
    $details[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Pembayaran Transaksi #<?= htmlspecialchars($id_transaksi) ?></title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="shortcut icon" href="../../assets/img/logo.jpg">
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-3qUv-F-EtozaPD5I"></script>
    <style>
        h1, h3, h4 { text-align: center; }
        .order_box { background: #f9f9f9; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        ul.list li { display: flex; justify-content: space-between; padding: 6px 0; }
        .primary-btn { background-color: #2d89ef; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .primary-btn:hover { background-color: #1b5fbd; }
    </style>
</head>
<body>
<?php include('../layout/navbar1.php') ?>

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

<section class="checkout_area section_gap">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="billing_details">
                    <h3 class="mb-4">Pembayaran Transaksi #<?= htmlspecialchars($id_transaksi) ?></h3>
                    <div class="order_box">
                        <h4>Informasi Penyewa</h4>
                        <hr>
                        <ul class="list">
                            <li>Nama Penyewa <span><?= htmlspecialchars($transaksi['nama_penyewa']) ?></span></li>
                            <li>Metode Pembayaran <span><?= htmlspecialchars($transaksi['nama_metode']) ?> (<?= htmlspecialchars($transaksi['tipe_metode']) ?>)</span></li>
                            <li>Tanggal Sewa <span><?= htmlspecialchars($transaksi['tanggal_sewa']) ?></span></li>
                            <li>Tanggal Kembali <span><?= htmlspecialchars($transaksi['tanggal_kembali']) ?></span></li>
                        </ul>

                        <hr>
                        <h4 class="mt-4">Detail Barang Disewa</h4>
                        <ul class="list">
                            <?php
                            $total_harga_check = 0;
                            foreach ($details as $d):
                                $subtotal = $d['jumlah_barang'] * $d['harga_satuan'];
                                $total_harga_check += $subtotal;
                            ?>
                            <li>
                                <?= htmlspecialchars($d['nama_barang']) ?> x<?= (int)$d['jumlah_barang'] ?>
                                <span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                            </li>
                            <?php endforeach; ?>
                            <li><strong>Total</strong> <span><strong>Rp <?= number_format($transaksi['total_harga_sewa'], 0, ',', '.') ?></strong></span></li>
                        </ul>

                        <div class="text-center mt-4">
                            <button class="primary-btn" id="pay-button">Bayar Sekarang</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('pay-button').addEventListener('click', function () {
        snap.pay('<?= $snap_token ?>', {
            onSuccess: function(result){
                console.log("Pembayaran sukses:", result);
                alert('Pembayaran berhasil!');
                window.location.href = '../page/transaksi.php';
            },
            onPending: function(result){
                console.log("Pembayaran pending:", result);
                alert('Pembayaran dalam proses.');
                window.location.href = '../page/transaksi.php';
            },
            onError: function(result){
                console.error("Pembayaran gagal:", result);
                alert('Pembayaran gagal atau dibatalkan.');
            },
            onClose: function(){
                alert('Anda menutup popup pembayaran tanpa menyelesaikan transaksi.');
            }
        });
    });
</script>

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
