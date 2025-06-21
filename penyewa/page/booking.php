<?php
include '../../route/koneksi.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = (int)$_SESSION['user_id'];

$selected_ids = isset($_POST['selected_items']) && is_array($_POST['selected_items']) ? $_POST['selected_items'] : [];
$jumlah_items = isset($_POST['jumlah']) && is_array($_POST['jumlah']) ? $_POST['jumlah'] : [];

if (empty($selected_ids)) {
    die("Tidak ada item yang dipilih.");
}

// Update jumlah di tabel carts sesuai input user (gunakan prepared statement)
$stmt_update = $koneksi->prepare("UPDATE carts SET jumlah = ? WHERE id = ? AND id_penyewa = ?");
foreach ($selected_ids as $cart_id) {
    $cart_id_int = (int)$cart_id;
    $jumlah_baru = isset($jumlah_items[$cart_id]) ? (int)$jumlah_items[$cart_id] : 1;
    if ($jumlah_baru < 1) $jumlah_baru = 1;
    $stmt_update->bind_param("iii", $jumlah_baru, $cart_id_int, $id_penyewa);
    $stmt_update->execute();
}
$stmt_update->close();

$id_list = implode(',', array_map('intval', $selected_ids));

// Ambil data cart dengan harga satuan dari barang
$sql_carts = "SELECT carts.id, carts.id_barang, barang.gambar, barang.nama_barang, carts.jumlah, barang.harga_sewa AS harga
              FROM carts
              JOIN barang ON carts.id_barang = barang.id_barang
              WHERE carts.id_penyewa = ? AND carts.id IN ($id_list)";
$stmt_carts = $koneksi->prepare($sql_carts);
$stmt_carts->bind_param("i", $id_penyewa);
$stmt_carts->execute();
$result_carts = $stmt_carts->get_result();

if (!$result_carts) {
    die("Query error: " . $koneksi->error);
}

// Ambil data penyewa
$stmt_penyewa = $koneksi->prepare("SELECT nama_penyewa, no_hp, alamat FROM penyewa WHERE id_penyewa = ?");
$stmt_penyewa->bind_param("i", $id_penyewa);
$stmt_penyewa->execute();
$result_penyewa = $stmt_penyewa->get_result();
$penyewa = $result_penyewa->fetch_assoc();
$stmt_penyewa->close();

// Ambil tipe metode pembayaran
$tipe_metode = [];
$sql = "SELECT id_tipe, nama_tipe FROM tipe_metode ORDER BY nama_tipe ASC";
$result = $koneksi->query($sql);
while ($row = $result->fetch_assoc()) {
    $tipe_metode[$row['id_tipe']] = $row['nama_tipe'];
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
<style>
    /* Container utama form checkout */
.checkout_area .container {
  padding: 2rem 1rem;
}

/* Styling tiap item pesanan */
.pesanan-item {
  border: 1px solid #ddd;
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 1rem;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
  transition: box-shadow 0.3s ease;
  background-color: #fff;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.pesanan-item:hover {
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

/* Gambar barang */
.pesanan-item img {
  max-height: 80px;
  border-radius: 8px;
  flex-shrink: 0;
  object-fit: cover;
}

/* Detail teks produk */
.pesanan-detail strong {
  font-size: 1.1rem;
  margin-bottom: 0.3rem;
  display: block;
}

.pesanan-detail div {
  font-size: 0.9rem;
  color: #555;
}

/* Input jumlah */
.pesanan-item input.jumlah-input {
  width: 80px;
  padding: 6px 10px;
  font-size: 1rem;
  border-radius: 8px;
  border: 1px solid #ccc;
  text-align: center;
  transition: border-color 0.3s ease;
}

.pesanan-item input.jumlah-input:focus {
  border-color: #495057;
  outline: none;
}

/* Container form kanan */
.checkout_area form > .row > div:last-child {
  background-color: #f9f9f9;
  padding: 1.5rem 1.2rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

/* Label form */
.checkout_area label {
  font-weight: 600;
  margin-bottom: 0.3rem;
}

/* Tombol submit */
.checkout_area button[type="submit"] {
  padding: 12px;
  font-size: 1.1rem;
  border-radius: 10px;
  transition: background-color 0.3s ease;
}

.checkout_area button[type="submit"]:hover {
  background-color: #222;
  color: #fff;
}

/* Total bayar */
#total_bayar_display {
  font-weight: 700;
  font-size: 1.4rem;
  color: #333;
  margin-bottom: 1rem;
}

/* Responsive tweaks */
@media (max-width: 768px) {
  .pesanan-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .pesanan-item img {
    margin-bottom: 0.7rem;
  }

  .pesanan-item input.jumlah-input {
    width: 100%;
  }
}

</style>
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
                <form action="../controller/prosesBooking.php" method="post" id="checkout-form" onsubmit="return validateDates()">
  <div class="row">
    <div class="col-lg-8 col-md-7 col-12">
      <?php foreach ($cart_items as $item) : ?>
        <div class="d-flex align-items-center gap-3 mb-3 border p-3 rounded">
          <img src="../../barang/barang/gambar/<?= htmlspecialchars($item['gambar']) ?>" 
               alt="<?= htmlspecialchars($item['nama_barang']) ?>" 
               class="img-fluid" style="max-height:80px; width:auto; flex-shrink:0;">
          <div class="flex-grow-1">
            <strong><?= htmlspecialchars($item['nama_barang']) ?></strong>
            <div>Harga/hari: Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
            <div id="subtotal-<?= (int)$item['id'] ?>">Subtotal: Rp 0</div>
          </div>

          <div style="width: 90px;">
            <input type="hidden" name="items[<?= (int)$item['id'] ?>][id_barang]" value="<?= (int)$item['id_barang'] ?>">
            <input type="number" 
                   name="items[<?= (int)$item['id'] ?>][jumlah]" 
                   min="1" 
                   value="<?= (int)$item['jumlah'] ?>" 
                   class="form-control jumlah-input" 
                   required 
                   aria-label="Jumlah <?= htmlspecialchars($item['nama_barang']) ?>" />
            <input type="hidden" name="items[<?= (int)$item['id'] ?>][harga]" value="<?= (int)$item['harga'] ?>">
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="col-lg-4 col-md-5 col-12">
      <div class="mb-3">
        <label for="tanggal_sewa" class="form-label fw-bold">Sewa:</label>
        <input type="date" id="tanggal_sewa" name="tanggal_sewa" class="form-control" required min="<?= date('Y-m-d') ?>" />
      </div>

      <div class="mb-3">
        <label for="tanggal_kembali" class="form-label fw-bold">Kembali:</label>
        <input type="date" id="tanggal_kembali" name="tanggal_kembali" class="form-control" required min="<?= date('Y-m-d') ?>" />
      </div>

      <h5>Metode Pembayaran (Tipe Metode)</h5>
      <div class="mb-3 d-flex flex-wrap gap-3">
        <?php
        $first = true;
        foreach ($tipe_metode as $id_tipe => $nama_tipe) :
        ?>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="id_tipe" id="tipe-<?= $id_tipe ?>" value="<?= $id_tipe ?>" <?= $first ? 'checked' : '' ?> required />
            <label class="form-check-label" for="tipe-<?= $id_tipe ?>" title="<?= htmlspecialchars($nama_tipe) ?>">
              <?= htmlspecialchars($nama_tipe) ?>
            </label>
          </div>
        <?php
          $first = false;
        endforeach;
        ?>
      </div>

      <h5>Total Bayar</h5>
      <div id="total_bayar_display" class="fw-bold fs-5 mb-3">Rp 0</div>
      <input type="hidden" id="total_harga_sewa" name="total_harga_sewa" value="0" />

      <button type="submit" class="btn btn-dark w-100">Konfirmasi</button>
    </div>
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
                    // Ambil jumlah dari input yang user ubah
                    let jumlahInput = $(`input[name="items[${item.id}][jumlah]"]`);
                    let jumlah = parseInt(jumlahInput.val());
                    if (isNaN(jumlah) || jumlah < 1) {
                        jumlah = 1;
                        jumlahInput.val(jumlah);
                    }
                    const subtotal = hariSewa * item.harga * jumlah;
                    $('#subtotal-' + item.id).text("Subtotal: Rp " + subtotal.toLocaleString('id-ID'));
                    totalBayar += subtotal;
                });

                $('#total_bayar_display').text("Rp " + totalBayar.toLocaleString('id-ID'));
                $('#total_harga_sewa').val(totalBayar);
            }

            $('#tanggal_sewa, #tanggal_kembali').on('change', updateTotalBayar);
            $('.jumlah-input').on('change', updateTotalBayar);

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
