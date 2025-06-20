<?php
include '../../route/koneksi.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}
$id_penyewa = $_SESSION['user_id'];

// PERBAIKAN KEAMANAN: Menggunakan Prepared Statement
$query = "
    SELECT carts.id, barang.gambar, barang.nama_barang, carts.jumlah, barang.harga_sewa, barang.stok
    FROM carts
    JOIN barang ON carts.id_barang = barang.id_barang
    WHERE carts.id_penyewa = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_penyewa);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Keranjang Belanja - Subang Outdoor</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/linearicons.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/nouislider.min.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="shortcut icon" href="../../assets/img/logo.jpg">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .cart_area { padding: 60px 0; }
        .cart-item-card {
            background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 20px; margin-bottom: 20px; display: flex; align-items: center; transition: all 0.3s ease;
        }
        .cart-item-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .cart-item-card img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
        .item-details { flex-grow: 1; margin: 0 20px; }
        .item-details .nama-barang { font-weight: 600; font-size: 1.1rem; }
        .item-details .harga-satuan { color: #6c757d; }
        
        .quantity-stepper { display: flex; align-items: center; border: 1px solid #ccc; border-radius: 50px; overflow: hidden; }
        .quantity-stepper .btn-qty { background-color: #f8f9fa; border: none; font-weight: bold; cursor: pointer; padding: 5px 12px; }
        .quantity-stepper .btn-qty:hover { background-color: #e2e6ea; }
        .quantity-stepper .qty-input { width: 45px; text-align: center; border: none; font-size: 1rem; font-weight: 600;}
        .quantity-stepper .qty-input::-webkit-outer-spin-button, .quantity-stepper .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        
        .summary-card {
            background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px; position: -webkit-sticky; position: sticky; top: 110px;
        }
        .primary-btn {
            background-color: #fab700; border: none; color: #fff !important; padding: 12px 25px;
            border-radius: 50px; font-weight: 700; cursor: pointer; text-decoration: none;
            display: inline-block; transition: all 0.3s ease; width: 100%; text-align: center;
        }
        .primary-btn:hover { background-color: #e0a800; }
        .primary-btn:disabled { background-color: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <?php include("../layout/navbar1.php") ?>
    
    <section class="banner-area organic-breadcrumb">
        <div class="container">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Keranjang Belanja</h1>
                    <nav class="d-flex align-items-center">
                        <a href="/subangoutdoor/index.php">Home<span class="lnr lnr-arrow-right"></span></a>
                        <a href="#">Keranjang</a>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <section class="cart_area">
        <div class="container">
            <?php if ($result->num_rows > 0): ?>
                <form method="POST" action="booking.php" id="cart-form" onsubmit="return validateCheckout()">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded shadow-sm">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="select-all">
                                    <label class="form-check-label" for="select-all">Pilih Semua</label>
                                </div>
                            </div>

                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <div class="cart-item-card" data-cart-id="<?= $row['id'] ?>">
                                    <div class="form-check">
                                        <input type="checkbox" class="item-checkbox form-check-input" name="selected_items[]" value="<?= $row['id'] ?>" data-price="<?= $row['harga_sewa'] ?>">
                                    </div>
                                    <img src="../../barang/barang/gambar/<?= htmlspecialchars($row['gambar']); ?>" alt="<?= htmlspecialchars($row['nama_barang']); ?>" class="mx-3">
                                    <div class="item-details">
                                        <div class="nama-barang"><?= htmlspecialchars($row['nama_barang']); ?></div>
                                        <div class="harga-satuan">Rp <?= number_format($row['harga_sewa'], 0, ",", "."); ?>/hari</div>
                                    </div>
                                    <div class="quantity-stepper">
                                        <button type="button" class="btn-qty minus">-</button>
                                        <input type="number" class="qty-input quantity-input" name="jumlah[<?= $row['id'] ?>]" value="<?= $row['jumlah'] ?>" min="1" max="<?= $row['stok'] ?>">
                                        <button type="button" class="btn-qty plus">+</button>
                                    </div>
                                    <div class="subtotal-display ml-4" style="width: 120px; text-align: right;">
                                        <strong>Rp <?= number_format($row['harga_sewa'] * $row['jumlah'], 0, ",", "."); ?></strong>
                                    </div>
                                    <a href="../controller/hapus.php?id=<?= $row['id'] ?>" class="btn btn-link text-danger ml-2" onclick="return confirm('Yakin ingin hapus item ini?')" title="Hapus"><i class="fas fa-trash"></i></a>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="col-lg-4">
                            <div class="summary-card">
                                <h4>Ringkasan Pesanan</h4>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Subtotal</span>
                                    <strong id="total-display">Rp 0</strong>
                                </div>
                                <p class="small text-muted">Total akhir akan dihitung berdasarkan tanggal sewa pada halaman booking.</p>
                                <button type="submit" id="checkout-btn" class="primary-btn mt-3" disabled>Booking</button>
                            </div>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-info mt-4 text-center">
                    <p>Keranjang belanja Anda masih kosong.</p>
                    <a href="produk.php" class="btn btn-primary">Mulai Belanja</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include('../layout/footer.php'); ?>

    <script src="js/vendor/jquery-2.2.4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="js/vendor/bootstrap.min.js"></script>
    <script src="js/jquery.ajaxchimp.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/nouislider.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
    <script>
    $(document).ready(function() {
        function updateTotal() {
            let total = 0;
            $('.item-checkbox:checked').each(function() {
                const card = $(this).closest('.cart-item-card');
                const harga = parseInt($(this).data('price'));
                const jumlah = parseInt(card.find('.quantity-input').val());
                if (!isNaN(harga) && !isNaN(jumlah)) {
                    total += harga * jumlah;
                }
            });
            $('#total-display').text("Rp " + total.toLocaleString("id-ID"));
            toggleCheckoutButton();
        }

        function updateSubtotal(inputEl) {
            const card = $(inputEl).closest('.cart-item-card');
            const harga = parseInt(card.find('.item-checkbox').data('price'));
            let jumlah = parseInt($(inputEl).val());

            if (isNaN(jumlah) || jumlah < 1) {
                jumlah = 1;
                $(inputEl).val(1);
            }

            const maxStok = parseInt($(inputEl).attr('max'));
            if(jumlah > maxStok){
                alert('Jumlah melebihi stok yang tersedia (' + maxStok + ')');
                jumlah = maxStok;
                $(inputEl).val(maxStok);
            }
            
            const subtotal = harga * jumlah;
            card.find('.subtotal-display strong').text("Rp " + subtotal.toLocaleString("id-ID"));
            
            if (card.find('.item-checkbox').is(':checked')) {
                updateTotal();
            }
        }

        function toggleCheckoutButton() {
            const anyChecked = $('.item-checkbox:checked').length > 0;
            $('#checkout-btn').prop('disabled', !anyChecked);
        }

        function validateCheckout() {
            if ($('.item-checkbox:checked').length === 0) {
                alert("Silakan pilih minimal satu barang untuk di-booking.");
                return false;
            }
            return true;
        }

        // Event listeners
        $('#select-all').change(function() {
            $('.item-checkbox').prop('checked', this.checked).trigger('change');
        });

        $('.item-checkbox').change(function() {
            if ($('.item-checkbox:checked').length === $('.item-checkbox').length) {
                $('#select-all').prop('checked', true);
            } else {
                $('#select-all').prop('checked', false);
            }
            updateTotal();
        });

        $('.quantity-input').on('change keyup', function() {
            updateSubtotal(this);
        });

        $('.quantity-stepper .minus').click(function() {
            const input = $(this).siblings('.qty-input');
            let count = parseInt(input.val()) - 1;
            count = count < 1 ? 1 : count;
            input.val(count).trigger('change');
        });

        $('.quantity-stepper .plus').click(function() {
            const input = $(this).siblings('.qty-input');
            const max = parseInt(input.attr('max'));
            let count = parseInt(input.val()) + 1;
            if (count > max) { count = max; }
            input.val(count).trigger('change');
        });

        // Initial state
        updateTotal();
    });
    </script>
</body>
</html>