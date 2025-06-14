<?php
session_start();
include '../../route/koneksi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}
$id_penyewa = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="zxx" class="no-js">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Keranjang  - Subang Outdoor</title>

    <!-- Favicon-->
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
</head>

<body>

    <!-- Start Header Area -->
    <?php include("../layout/navbar1.php") ?>
    
    <!-- Start Banner Area -->
    <section class="banner-area organic-breadcrumb">
        <div class="container">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Subang Outdoor</h1>
                    <nav class="d-flex align-items-center">
                        <a href="#">Cart</a>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!--================Cart Area =================-->
    <section class="cart_area">
        <div class="container">
            <div class="cart_inner">
                <div class="table-responsive">
                    <div class="container">
                        <h5>SUBANG OUTDOOR | Keranjang Belanja</h5>

                        <?php
                            $query = "SELECT carts.id, barang.gambar, barang.nama_barang, carts.jumlah, barang.harga_sewa
                                      FROM carts
                                      JOIN barang ON carts.id_barang = barang.id_barang
                                      WHERE carts.id_penyewa = '$id_penyewa'";
                            $result = mysqli_query($koneksi, $query);

                            if (!$result) {
                                die("Query error: " . mysqli_error($koneksi));
                            }

                            if (mysqli_num_rows($result) > 0) {
                                echo '<form method="POST" action="booking.php" onsubmit="return validateCheckout()">';
                                echo '<table class="table mt-4">
                                        <thead>
                                          <tr>
                                            <th></th>
                                            <th>Gambar</th>
                                            <th>Nama Barang</th>
                                            <th>Jumlah</th>
                                            <th>Harga Satuan</th>
                                            <th>Subtotal</th>
                                            <th>Aksi</th>
                                          </tr>
                                        </thead>
                                        <tbody>';

                                $no = 0;
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $subtotal = $row['harga_sewa']*$row['jumlah'];
                                    $id_cart = $row['id'];

                                    echo '<tr>
                                        <td><input type="checkbox" class="item-checkbox" name="selected_items[]" value="' . $id_cart . '" onchange="toggleCheckoutButton(); updateTotal()"></td>
                                        <td><img src="../../barang/barang/gambar/' . $row['gambar'] . '" alt="' . htmlspecialchars($row['nama_barang']) . '" style="width: 80px;"></td>
                                        <td>' . htmlspecialchars($row['nama_barang']) . '</td>
                                        <td>
                                          <input type="number" class="form-control quantity-input" name="jumlah[' . $id_cart . ']" value="' . $row['jumlah'] . '" min="1" data-price="' . $row['harga_sewa'] . '" onchange="updateSubtotal(this); updateTotal()" />
                                        </td>
                                        <td>Rp. ' . number_format($row['harga_sewa'], 0, ",", ".") . '</td>
                                        <td class="subtotal">Rp. ' . number_format($subtotal, 0, ",", ".") . '</td>
                                        <td><a href="../controller/hapus.php?id=' . $id_cart . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Yakin ingin hapus?\')">Hapus</a></td>
                                      </tr>';
                                    $no++;
                                }

                                echo '</tbody></table>';

                                echo '
                                  <div class="d-flex align-items-center justify-content-between border-top pt-3 mt-3">
                                    <div>
                                      <input type="checkbox" id="select-all" />
                                      <label class="mb-0"><strong>Pilih Semua (' . $no . ')</strong></label>
                                    </div>
                                    <div class="fw-bold">Total: <span id="total-display">Rp. 0</span></div>
                                    <button type="submit" id="checkout-btn" class="btn btn-dark" disabled>Booking</button>
                                  </div>
                                </form>';
                            } else {
                                echo '<div class="alert alert-info mt-4">Keranjang belanja kosong.</div>';
                            }
                        
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include('../layout/footer.php'); ?>

    <!-- Script JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('select-all')?.addEventListener('change', function () {
            const checked = this.checked;
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checked);
            toggleCheckoutButton();
            updateTotal();
        });

        function toggleCheckoutButton() {
            const anyChecked = [...document.querySelectorAll('.item-checkbox')].some(cb => cb.checked);
            document.getElementById('checkout-btn').disabled = !anyChecked;
        }

        function validateCheckout() {
            const anyChecked = [...document.querySelectorAll('.item-checkbox')].some(cb => cb.checked);
            if (!anyChecked) {
                alert("Silakan pilih minimal satu barang.");
                return false;
            }
            return true;
        }

        function updateSubtotal(input) {
            const harga = parseInt(input.dataset.price);
            let jumlah = parseInt(input.value);
            if (isNaN(jumlah) || jumlah < 1) {
                jumlah = 1;
                input.value = 1;
            }
            const subtotal = harga * jumlah;
            input.closest('tr').querySelector('.subtotal').textContent = "Rp. " + subtotal.toLocaleString("id-ID");
        }

        function updateTotal() {
            let total = 0;
            document.querySelectorAll('.item-checkbox').forEach(cb => {
                if (cb.checked) {
                    const row = cb.closest('tr');
                    const harga = parseInt(row.querySelector('.quantity-input').dataset.price);
                    const jumlah = parseInt(row.querySelector('.quantity-input').value);
                    total += harga * jumlah;
                }
            });
            document.getElementById('total-display').textContent = "Rp. " + total.toLocaleString("id-ID");
        }

        window.addEventListener('DOMContentLoaded', () => {
            updateTotal();
            toggleCheckoutButton();
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
