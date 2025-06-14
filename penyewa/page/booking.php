<?php
include '../../route/koneksi.php';
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = $_SESSION['user_id'];

// Pastikan input POST tersedia dan berupa array
$selected_ids = isset($_POST['selected_items']) && is_array($_POST['selected_items']) ? $_POST['selected_items'] : [];
$jumlah_items = isset($_POST['jumlah']) && is_array($_POST['jumlah']) ? $_POST['jumlah'] : [];

if (empty($selected_ids)) {
    die("Tidak ada item yang dipilih.");
}

// Prepared statement untuk update jumlah di carts
$stmt_update = $koneksi->prepare("UPDATE carts SET jumlah = ? WHERE id = ? AND id_penyewa = ?");

// Update jumlah di carts
foreach ($selected_ids as $cart_id) {
    $cart_id_int = (int)$cart_id;
    $jumlah_baru = isset($jumlah_items[$cart_id]) ? (int)$jumlah_items[$cart_id] : 1;
    if ($jumlah_baru < 1) $jumlah_baru = 1;

    $stmt_update->bind_param("iis", $jumlah_baru, $cart_id_int, $id_penyewa);
    $stmt_update->execute();
}
$stmt_update->close();

// Buat daftar id carts untuk query select
$id_list = implode(',', array_map('intval', $selected_ids));

// Ambil data carts dan barang
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

// Ambil data penyewa
$stmt_penyewa = $koneksi->prepare("SELECT nama_penyewa, no_hp, alamat FROM penyewa WHERE id_penyewa = ?");
$stmt_penyewa->bind_param("s", $id_penyewa);
$stmt_penyewa->execute();
$result_penyewa = $stmt_penyewa->get_result();
$penyewa = $result_penyewa->fetch_assoc();
$stmt_penyewa->close();

// Ambil metode pembayaran
$result_metode = $koneksi->query("SELECT * FROM metode_pembayaran");

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
     <link rel="shortcut icon" href="../../assets/img/logo.jpg">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>

    <!-- Header -->
    <?php include("../layout/navbar1.php") ?>

    <!-- Banner -->
    <section class="banner-area organic-breadcrumb">
        <div class="container">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Subang Outdoor</h1>
                    <nav class="d-flex align-items-center">
                        <a href="checkout.php">Booking</a>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Checkout Area -->
    <section class="checkout_area section_gap">
        <div class="container">
            <div class="info mb-3" style="display: flex; justify-content: space-between; align-items: center;">
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
<input type="date" id="tanggal_sewa" name="tanggal_sewa" class="w3-input w3-border mb-3" required
       style="font-size:14px; padding:6px;"
       min="<?= date('Y-m-d') ?>" />

<label for="tanggal_kembali" class="fw-bold fs-6">Kembali:</label>
<input type="date" id="tanggal_kembali" name="tanggal_kembali" class="w3-input w3-border mb-3" required
       style="font-size:14px; padding:6px;"
       min="<?= date('Y-m-d') ?>" />
                        <h4>Metode Pembayaran</h4>
                        <?php
                        if ($result_metode->num_rows > 0) :
                            while ($metode = $result_metode->fetch_assoc()) : ?>
                                <label class="me-3" style="cursor:pointer;">
                                    <input type="radio" name="id_metode" value="<?= (int)$metode['id_metode'] ?>" required />
                                    <img src="../../metode_pembayaran/metode/gambar/<?= htmlspecialchars($metode['gambar_metode']) ?>" alt="<?= htmlspecialchars($metode['nama_metode']) ?>" style="height:40px;" />
                                </label>
                            <?php endwhile;
                        endif; ?>

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

    <!-- Scripts -->
    <script>
        const cartItems = <?= json_encode($cart_items ?? []) ?>;

        function hitungSelisihHari(tgl1, tgl2) {
            if (!tgl1 || !tgl2) return 0;
            const date1 = new Date(tgl1);
            const date2 = new Date(tgl2);
            const diffTime = date2 - date1;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            return diffDays > 0 ? diffDays : 0;
        }

        function updateTotalBayar() {
            const tglSewa = document.getElementById('tanggal_sewa').value;
            const tglKembali = document.getElementById('tanggal_kembali').value;
            const hariSewa = hitungSelisihHari(tglSewa, tglKembali);

            let totalBayar = 0;
            cartItems.forEach(item => {
                const subtotalElem = document.getElementById('subtotal-' + item.id);
                const subtotal = hariSewa * item.harga * item.jumlah;
                subtotalElem.textContent = "Subtotal: Rp " + subtotal.toLocaleString('id-ID');
                totalBayar += subtotal;
            });

            document.getElementById('total_bayar_display').textContent = "Rp " + totalBayar.toLocaleString('id-ID');
            document.getElementById('total_harga_sewa').value = totalBayar;
        }

        function validateDates() {
            const tglSewa = document.getElementById('tanggal_sewa').value;
            const tglKembali = document.getElementById('tanggal_kembali').value;
            const totalBayar = parseInt(document.getElementById('total_harga_sewa').value, 10);

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

        document.getElementById('tanggal_sewa').addEventListener('change', updateTotalBayar);
        document.getElementById('tanggal_kembali').addEventListener('change', updateTotalBayar);

        // Update harga saat load halaman (jika ada tanggal default)
        window.addEventListener('load', updateTotalBayar);
    </script>
    </script>
    <script src="js/vendor/jquery-2.2.4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4"
        crossorigin="anonymous"></script>
    <script src="js/vendor/bootstrap.min.js"></script>
    <script src="js/jquery.ajaxchimp.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/nouislider.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <!--gmaps Js-->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjCGmQ0Uq4exrzdcL6rvxywDDOvfAu6eE"></script>
    <script src="js/gmaps.min.js"></script>
    <script src="js/main.js"></script>
</body>
<?php include("../layout/footer.php") ?>
</html>
