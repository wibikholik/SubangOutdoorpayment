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

// Proses konfirmasi bayar langsung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['metode_bayar']) && $_POST['metode_bayar'] == 'langsung') {
    $update = mysqli_query($koneksi, "UPDATE pengembalian SET status_pembayaran = 'Menunggu Konfirmasi Pembayaran', id_metode = 3 WHERE id_pengembalian = $id_pengembalian AND id_transaksi IN (SELECT id_transaksi FROM transaksi WHERE id_penyewa = $id_penyewa)");
    if ($update) {
        echo "<script>alert('Pembayaran langsung dikonfirmasi. Silakan bayar ke toko.'); window.location.href='../page/transaksi.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal mengupdate status pembayaran.');</script>";
    }
}

// Ambil data pengembalian
$sql = "
    SELECT p.*, t.id_transaksi, t.tanggal_sewa, t.tanggal_kembali, pe.nama_penyewa,
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
    <link rel="stylesheet" href="css/linearicons.css">
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/nouislider.min.css">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-3qUv-F-EtozaPD5I"></script>
    <style>
        ul.list li {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }
        .primary-btn {
            background-color: #2d89ef;
            color: white;
            padding: 12px 20px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .primary-btn:hover {
            background-color: #1b5fbd;
        }
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

                        <form method="post" class="mt-4">
                            <h4>Pilih Metode Pembayaran</h4>
                            <?php foreach ($metode_list as $metode): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input metode-radio" type="radio"
                                           name="metode_bayar"
                                           id="metode<?= $metode['id_metode']; ?>"
                                           value="<?= $metode['nama_metode'] == 'Bayar Langsung' ? 'langsung' : 'online'; ?>"
                                           <?= $metode['nama_metode'] == 'Bayar Online' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="metode<?= $metode['id_metode']; ?>">
                                        <?= htmlspecialchars($metode['nama_metode']); ?> (<?= htmlspecialchars($metode['atas_nama']); ?>)
                                      
                                      
                                    </label>
                                </div>
                            <?php endforeach; ?>

                            <div id="bayar-online" class="mt-4">
                                <?php if (empty($data['snap_token'])): ?>
                                    <div class="alert alert-danger">Token pembayaran belum tersedia. Hubungi admin.</div>
                                <?php else: ?>
                                    <button class="primary-btn w-100" type="button" id="pay-button">Bayar Sekarang (Online)</button>
                                <?php endif; ?>
                            </div>

                            <div id="bayar-langsung" class="mt-4" style="display: none;">
                                <div class="alert alert-info">
                                    Silakan bayar langsung ke toko setelah konfirmasi ini.
                                </div>
                                <button class="primary-btn w-100" type="submit">Konfirmasi Bayar Langsung</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function toggleMetode() {
        const metode = document.querySelector('input[name="metode_bayar"]:checked')?.value;
        document.getElementById('bayar-online').style.display = (metode === 'online') ? 'block' : 'none';
        document.getElementById('bayar-langsung').style.display = (metode === 'langsung') ? 'block' : 'none';
    }

    document.querySelectorAll('.metode-radio').forEach(el => el.addEventListener('change', toggleMetode));
    toggleMetode();

    document.getElementById('pay-button')?.addEventListener('click', function () {
        snap.pay('<?= $data['snap_token']; ?>', {
            onSuccess: function () {
                alert('Pembayaran berhasil!');
                window.location.href = '../page/transaksi.php';
            },
            onPending: function () {
                alert('Pembayaran sedang diproses.');
                window.location.href = '../page/transaksi.php';
            },
            onError: function () {
                alert('Terjadi kesalahan.');
            },
            onClose: function () {
                alert('Anda menutup popup pembayaran.');
            }
        });
    });
});
</script>

<?php include("../layout/footer.php"); ?>
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
