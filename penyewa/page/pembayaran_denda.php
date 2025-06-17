<?php
session_start();
include '../../route/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = (int)$_SESSION['user_id'];
$id_pengembalian = isset($_GET['id_pengembalian']) ? (int)$_GET['id_pengembalian'] : 0;

if ($id_pengembalian <= 0) {
    echo "<script>alert('ID pengembalian tidak valid.'); window.location.href='../page/transaksi.php';</script>";
    exit;
}

// Ambil data pengembalian
$sql = "
    SELECT p.*, t.id_transaksi, t.tanggal_sewa, t.tanggal_kembali, t.status AS status_transaksi,
           pe.nama_penyewa,
           dt.id_barang, b.nama_barang
    FROM pengembalian p
    JOIN transaksi t ON p.id_transaksi = t.id_transaksi
    JOIN penyewa pe ON t.id_penyewa = pe.id_penyewa
    JOIN detail_transaksi dt ON dt.id_transaksi = t.id_transaksi
    JOIN barang b ON b.id_barang = dt.id_barang
    WHERE p.id_pengembalian = ? AND t.id_penyewa = ?
    LIMIT 1
";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id_pengembalian, $id_penyewa);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<script>alert('Data pengembalian tidak ditemukan.'); window.location.href='../page/transaksi.php';</script>";
    exit;
}

// Load Midtrans Snap library
require_once '../../vendor/autoload.php';
\Midtrans\Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Buat snap_token hanya jika denda > 0 dan belum ada token
if (floatval($data['denda']) > 0 && empty($data['snap_token'])) {
    $order_id = 'DENDA-' . $id_pengembalian . '-' . time();
    $gross_amount = (int) ceil($data['denda']);

    $params = [
        'transaction_details' => [
            'order_id' => $order_id,
            'gross_amount' => $gross_amount,
        ],
        'customer_details' => [
            'first_name' => $data['nama_penyewa'],
        ],
    ];

    try {
        $snapToken = \Midtrans\Snap::getSnapToken($params);

        $id_metode_online = 4; // ID metode online
        $stmt_update_snap = mysqli_prepare($koneksi, "UPDATE pengembalian SET snap_token = ?, order_id = ?, id_metode = ? WHERE id_pengembalian = ?");
        mysqli_stmt_bind_param($stmt_update_snap, 'ssii', $snapToken, $order_id, $id_metode_online, $id_pengembalian);
        mysqli_stmt_execute($stmt_update_snap);
        mysqli_stmt_close($stmt_update_snap);

        $data['snap_token'] = $snapToken;
        $data['order_id'] = $order_id;
        $data['id_metode'] = $id_metode_online;
    } catch (Exception $e) {
        error_log("Midtrans Error: " . $e->getMessage());
        echo "<script>alert('Gagal membuat token pembayaran online. Hubungi admin.');</script>";
    }
}

// Konfirmasi Bayar Langsung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['metode_bayar']) && $_POST['metode_bayar'] === 'bayar_langsung') {
    $sql_update = "UPDATE pengembalian SET status_pengembalian = 'Menunggu Konfirmasi Pembayaran', snap_token = NULL, order_id = NULL, id_metode = 1 WHERE id_pengembalian = ?";
    $stmt_update = mysqli_prepare($koneksi, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "i", $id_pengembalian);
    $success = mysqli_stmt_execute($stmt_update);

    if ($success) {
        $status_transaksi_baru = 'Menunggu Konfirmasi Pembayaran Denda';
        $sql_update_transaksi = "UPDATE transaksi SET status = ? WHERE id_transaksi = ?";
        $stmt_update_transaksi = mysqli_prepare($koneksi, $sql_update_transaksi);
        mysqli_stmt_bind_param($stmt_update_transaksi, "si", $status_transaksi_baru, $data['id_transaksi']);
        mysqli_stmt_execute($stmt_update_transaksi);

        echo "<script>alert('Pembayaran langsung dikonfirmasi. Silakan bayar ke toko.'); window.location.href='../page/transaksi.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan konfirmasi pembayaran.');</script>";
    }
}

// Ambil metode pembayaran
$metode_q = mysqli_query($koneksi, "SELECT * FROM metode_pembayaran ORDER BY id_metode");
$metode_list = [];
while ($m = mysqli_fetch_assoc($metode_q)) {
    $metode_list[] = $m;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Pembayaran Denda #<?= htmlspecialchars($id_pengembalian) ?></title>
    <link rel="stylesheet" href="css/bootstrap.css">
     <link rel="shortcut icon" href="img/fav.png">
    <!-- Meta -->
    <meta name="author" content="CodePixar">
    <meta name="description" content="">
    <meta name="keywords" content="">

    <!-- CSS -->
    <link rel="stylesheet" href="css/linearicons.css">
  <link rel="stylesheet" href="css/owl.carousel.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/themify-icons.css">
  <link rel="stylesheet" href="css/nice-select.css">
  <link rel="stylesheet" href="css/nouislider.min.css">
  <link rel="stylesheet" href="css/bootstrap.css">
  <link rel="stylesheet" href="css/main.css">
  <link rel="shortcut icon" href="../../assets/img/logo.jpg">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-3qUv-F-EtozaPD5I"></script>
</head>
<body>

<?php include('../layout/navbar1.php') ?>
<section class="banner-area organic-breadcrumb">
        <div class="container">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Subang Outdoor</h1>
                    <nav class="d-flex align-items-center">
                        <a href="#">Pembayaran Denda</a>
                    </nav>
                </div>
            </div>
        </div>
    </section>
<section class="checkout_area section_gap">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-12">
                <div class="billing_details">
                    <h3 class="mb-4 text-center">Pembayaran Denda #<?= htmlspecialchars($id_pengembalian); ?></h3>
                    <div class="order_box bg-light p-4 rounded shadow-sm">
                        <h4>Detail Pengembalian</h4>
                        <ul class="list">
                            <li>Nama Penyewa <span><?= htmlspecialchars($data['nama_penyewa']); ?></span></li>
                            <li>Barang <span><?= htmlspecialchars($data['nama_barang']); ?></span></li>
                            <li>Tanggal Sewa <span><?= htmlspecialchars($data['tanggal_sewa']); ?></span></li>
                            <li>Tanggal Kembali <span><?= htmlspecialchars($data['tanggal_kembali']); ?></span></li>
                            <li>Denda <span><strong>Rp <?= number_format($data['denda'], 0, ',', '.'); ?></strong></span></li>
                        </ul>

                        <form method="post" class="mt-4" id="form-bayar">
                            <h4>Pilih Metode Pembayaran</h4>
                            <?php foreach ($metode_list as $metode): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input metode-radio" type="radio"
                                           name="metode_bayar"
                                           id="metode<?= $metode['id_metode']; ?>"
                                           value="<?= strtolower(str_replace(' ', '_', $metode['nama_metode'])); ?>"
                                           <?= $metode['nama_metode'] === 'Bayar Online' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="metode<?= $metode['id_metode']; ?>">
                                        <?= htmlspecialchars($metode['nama_metode']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>

                            <div id="bayar-online" class="mt-4" <?= empty($data['snap_token']) ? 'style="display:none;"' : ''; ?>>
                                <?php if (empty($data['snap_token'])): ?>
                                    <div class="alert alert-danger">Token pembayaran belum tersedia. Hubungi admin.</div>
                                <?php else: ?>
                                    <button class="btn btn-success w-100" type="button" id="pay-button">Bayar Sekarang (Online)</button>
                                <?php endif; ?>
                            </div>

                            <div id="bayar-langsung" class="mt-4" style="display: none;">
                                <div class="alert alert-info">Silakan bayar langsung ke toko setelah konfirmasi ini.</div>
                                <button class="btn btn-success w-100" type="submit">Konfirmasi Bayar Langsung</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include('../layout/footer.php') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function toggleMetode() {
        const metode = document.querySelector('input[name="metode_bayar"]:checked')?.value;
        document.getElementById('bayar-online').style.display = (metode === 'bayar_online') ? 'block' : 'none';
        document.getElementById('bayar-langsung').style.display = (metode === 'bayar_langsung') ? 'block' : 'none';
    }

    document.querySelectorAll('.metode-radio').forEach(el => el.addEventListener('change', toggleMetode));
    toggleMetode();

    const payButton = document.getElementById('pay-button');
    if (payButton) {
        payButton.addEventListener('click', function () {
            payButton.disabled = true;
            snap.pay('<?= $data['snap_token']; ?>', {
                onSuccess: function () {
                    alert('Pembayaran berhasil!');
                    window.location.href = 'transaksi.php';
                },
                onPending: function () {
                    alert('Pembayaran sedang diproses.');
                    window.location.href = 'transaksi.php';
                },
                onError: function () {
                    alert('Terjadi kesalahan saat pembayaran.');
                    payButton.disabled = false;
                },
                onClose: function () {
                    alert('Anda menutup popup pembayaran.');
                    payButton.disabled = false;
                }
            });
        });
    }
});
</script>
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
</html>
