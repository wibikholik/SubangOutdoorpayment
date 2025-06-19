<?php
include '../../route/koneksi.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = $_SESSION['user_id'];

$selected_ids = isset($_POST['selected_items']) && is_array($_POST['selected_items']) ? $_POST['selected_items'] : [];
$jumlah_items = isset($_POST['jumlah']) && is_array($_POST['jumlah']) ? $_POST['jumlah'] : [];

if (empty($selected_ids)) {
    die("Tidak ada item yang dipilih.");
}

$stmt_update = $koneksi->prepare("UPDATE carts SET jumlah = ? WHERE id = ? AND id_penyewa = ?");
foreach ($selected_ids as $cart_id) {
    $cart_id_int = (int)$cart_id;
    $jumlah_baru = isset($jumlah_items[$cart_id]) ? (int)$jumlah_items[$cart_id] : 1;
    if ($jumlah_baru < 1) $jumlah_baru = 1;
    $stmt_update->bind_param("iis", $jumlah_baru, $cart_id_int, $id_penyewa);
    $stmt_update->execute();
}
$stmt_update->close();

$id_list = implode(',', array_map('intval', $selected_ids));

$sql_carts = "SELECT carts.id, carts.id_barang, barang.gambar, barang.nama_barang, carts.jumlah, carts.harga
              FROM carts
              JOIN barang ON carts.id_barang = barang.id_barang
              WHERE carts.id_penyewa = ? AND carts.id IN ($id_list)";
$stmt_carts = $koneksi->prepare($sql_carts);
$stmt_carts->bind_param("s", $id_penyewa);
$stmt_carts->execute();
$result_carts = $stmt_carts->get_result();

if (!$result_carts) {
    die("Query error: " . $koneksi->error);
}

$stmt_penyewa = $koneksi->prepare("SELECT nama_penyewa, no_hp, alamat FROM penyewa WHERE id_penyewa = ?");
$stmt_penyewa->bind_param("s", $id_penyewa);
$stmt_penyewa->execute();
$result_penyewa = $stmt_penyewa->get_result();
$penyewa = $result_penyewa->fetch_assoc();
$stmt_penyewa->close();

$tipe_metode = [];
$sql = "SELECT tm.id_tipe, tm.nama_tipe
        FROM tipe_metode tm
        ORDER BY tm.nama_tipe ASC";
$result = $koneksi->query($sql);
while ($row = $result->fetch_assoc()) {
    $id_tipe = $row['id_tipe'];
    $tipe_metode[$id_tipe] = [
        'nama_tipe' => $row['nama_tipe']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Booking - Subang Outdoor</title>

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

    <?php include("../layout/navbar1.php") ?>
    <section class="banner-area organic-breadcrumb">
        <div class="container">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Subang Outdoor</h1>
                    <nav class="d-flex align-items-center">
                        <a href="#">Form Kondisi Awal Barang</a>
                    </nav>
                </div>
            </div>
        </div>
    </section>
    <section class="checkout_area section_gap">
        <div class="container">
            <div class="info mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <strong>Nama Penyewa</strong><br />
                    <?= htmlspecialchars($penyewa['nama_penyewa']) ?>
                </div>
                <div>
                    <strong>No. HP</strong><br />
                    <?= htmlspecialchars($penyewa['no_hp']) ?>
                </div>
                <div>
                    <strong>Alamat</strong><br />
                    <small><?= htmlspecialchars($penyewa['alamat']) ?></small>
                </div>
            </div>

            <h3>Daftar Pesanan</h3>

            <?php if ($result_carts->num_rows > 0) :
                $cart_items = $result_carts->fetch_all(MYSQLI_ASSOC);
            ?>
                <form action="../controller/prosesBooking.php" method="post" id="checkout-form" class="d-flex gap-4 align-items-start" onsubmit="return validateDates()">
                    <div style="flex: 0 0 700px; max-width: 700px;">
                        <?php foreach ($cart_items as $item) : ?>
                            <div class="pesanan-item d-flex align-items-center gap-3 mb-3">
                                <img src="../../barang/barang/gambar/<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama_barang']) ?>" style="height:80px; flex-shrink:0;">
                                <div class="pesanan-detail">
                                    <strong><?= htmlspecialchars($item['nama_barang']) ?> (<?= (int)$item['jumlah'] ?>)</strong>
                                    <div>Harga/hari: Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                                    <div id="subtotal-<?= (int)$item['id'] ?>">Subtotal: Rp 0</div>
                                </div>

                                <input type="hidden" name="items[<?= (int)$item['id'] ?>][id_barang]" value="<?= (int)$item['id_barang'] ?>">
                                <input type="hidden" name="items[<?= (int)$item['id'] ?>][jumlah]" value="<?= (int)$item['jumlah'] ?>">
                                <input type="hidden" name="items[<?= (int)$item['id'] ?>][harga]" value="<?= (int)$item['harga'] ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="min-width: 300px;">
                        <label for="tanggal_sewa" class="fw-bold fs-6">Sewa:</label>
                        <input type="date" id="tanggal_sewa" name="tanggal_sewa" class="form-control mb-3" required min="<?= date('Y-m-d') ?>" />

                        <label for="tanggal_kembali" class="fw-bold fs-6">Kembali:</label>
                        <input type="date" id="tanggal_kembali" name="tanggal_kembali" class="form-control mb-3" required min="<?= date('Y-m-d') ?>" />

                        <h4>Metode Pembayaran (Tipe Metode)</h4>
                        <?php
                        $first = true;
                        foreach ($tipe_metode as $id_tipe => $data) :
                        ?>
                            <label class="me-3" style="cursor:pointer;" title="<?= htmlspecialchars($data['nama_tipe']) ?>">
                                <input type="radio" name="id_tipe" value="<?= $id_tipe ?>" <?= $first ? 'checked' : '' ?> required />
                                <span><?= htmlspecialchars($data['nama_tipe']) ?></span>
                            </label>
                        <?php
                            $first = false;
                        endforeach;
                        ?>

                        <h4>Total Bayar</h4>
                        <div id="total_bayar_display" class="fw-bold fs-5 mb-3">Rp 0</div>
                        <input type="hidden" id="total_harga_sewa" name="total_harga_sewa" value="0" />

                        <button type="submit" class="btn btn-dark">Konfirmasi</button>
                    </div>
                </form>
            <?php else : ?>
                <p>Tidak ada item di keranjang Anda.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Semua library JS -->
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

    <script>
        const cartItems = <?= json_encode($cart_items ?? []) ?>;

        $(document).ready(function() {
            function hitungSelisihHari(tgl1, tgl2) {
                if (!tgl1 || !tgl2) return 0;
                const date1 = new Date(tgl1);
                const date2 = new Date(tgl2);
                const diffTime = date2 - date1;
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                return diffDays > 0 ? diffDays : 0;
            }

            function updateTotalBayar() {
                const tglSewa = $('#tanggal_sewa').val();
                const tglKembali = $('#tanggal_kembali').val();
                const hariSewa = hitungSelisihHari(tglSewa, tglKembali);

                let totalBayar = 0;
                cartItems.forEach(function(item) {
                    const subtotal = hariSewa * item.harga * item.jumlah;
                    $('#subtotal-' + item.id).text("Subtotal: Rp " + subtotal.toLocaleString('id-ID'));
                    totalBayar += subtotal;
                });

                $('#total_bayar_display').text("Rp " + totalBayar.toLocaleString('id-ID'));
                $('#total_harga_sewa').val(totalBayar);
            }

            $('#tanggal_sewa, #tanggal_kembali').on('change', updateTotalBayar);

            function validateDates() {
                const tglSewa = $('#tanggal_sewa').val();
                const tglKembali = $('#tanggal_kembali').val();
                const totalBayar = parseInt($('#total_harga_sewa').val(), 10);

                if (!tglSewa || !tglKembali) {
                    alert('Tanggal sewa dan tanggal kembali harus diisi.');
                    return false;
                }

                if (tglKembali <= tglSewa) {
                    alert('Tanggal kembali harus setelah tanggal sewa.');
                    return false;
                }

                if (totalBayar <= 0) {
                    alert('Total bayar harus lebih dari 0.');
                    return false;
                }

                return true;
            }

            $('#checkout-form').on('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                }
            });

            // Hitung total awal saat halaman load
            updateTotalBayar();
        });
    </script>
</body>

<?php include("../layout/footer.php") ?>

</html>
