<?php
// 1. Memulai Session (HARUS PALING PERTAMA)
session_start();
// 2. Koneksi ke Database
include 'route/koneksi.php';

// 3. Logika untuk Navbar (mengambil data user dan keranjang)
$username = $_SESSION['username'] ?? 'Guest';
$id_penyewa = $_SESSION['user_id'] ?? 0;
$currentPage = basename($_SERVER['PHP_SELF']);

$jumlah_cart = 0;
if ($id_penyewa) {
    // Menggunakan prepared statement yang aman
    $stmt_cart = mysqli_prepare($koneksi, "SELECT SUM(jumlah) AS total FROM carts WHERE id_penyewa = ?");
    if ($stmt_cart) {
        mysqli_stmt_bind_param($stmt_cart, "i", $id_penyewa);
        mysqli_stmt_execute($stmt_cart);
        $result_cart = mysqli_stmt_get_result($stmt_cart);
        $data_cart = mysqli_fetch_assoc($result_cart);
        $jumlah_cart = $data_cart['total'] ?? 0;
        mysqli_stmt_close($stmt_cart);
    }
}

// 4. Logika untuk Halaman index.php (mengambil produk terbaru)
$query_barang = "SELECT * FROM barang ORDER BY id_barang DESC LIMIT 8";
$result_barang = mysqli_query($koneksi, $query_barang);
if (!$result_barang) {
    die("Query Error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Subang Outdoor - Sewa Alat Camping</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="penyewa/page/css/linearicons.css">
    <link rel="stylesheet" href="penyewa/page/css/themify-icons.css">
    <link rel="stylesheet" href="penyewa/page/css/bootstrap.css">
    <link rel="stylesheet" href="penyewa/page/css/owl.carousel.css">
    <link rel="stylesheet" href="penyewa/page/css/nice-select.css">
    <link rel="stylesheet" href="penyewa/page/css/nouislider.min.css">
    <link rel="stylesheet" href="penyewa/page/css/main.css">
    <link rel="shortcut icon" href="assets/img/logo.jpg">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .section_gap { padding: 60px 0; }
        .section-title h2 { font-size: 36px; font-weight: 700; margin-bottom: 1rem; color: #222; }
        .section-title p { color: #6c757d; font-size: 16px; }

        /* Gaya Navbar */
        .cart-icon-wrapper { position: relative; display: inline-flex; align-items: center; }
        .cart-badge {
            position: absolute; top: 0; right: 0;
            transform: translate(50%, -60%); height: 22px; width: 22px;
            display: flex; align-items: center; justify-content: center;
            background-color: #e74a3b; color: white; font-size: 11px;
            font-weight: 700; border-radius: 50%; border: 2px solid white;
            z-index: 10; padding: 0;
        }
        .header_area .navbar .nav .nav-item.active .nav-link { color: #fab700 !important; font-weight: bold; }
        .user-dropdown-toggle i { margin-right: 8px; }
        .navbar-brand.site-brand {
            color: #fab700 !important; font-weight: 700; font-size: 20px;
            letter-spacing: 1px; transition: color 0.3s ease;
        }
        .navbar-brand.site-brand:hover { color: #e0a800 !important; }

        /* Gaya Konten Halaman */
        .product-card { background: #fff; border-radius: 12px; border: none; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.07); transition: all 0.3s ease; display: flex; flex-direction: column; height: 100%; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }
        .product-card .card-img-top { height: 250px; object-fit: cover; }
        .product-card .card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .product-card .card-title { font-weight: 600; font-size: 16px; flex-grow: 1; margin-bottom: 10px; }
        .product-card .card-text { font-size: 18px; color: #fab700; font-weight: 700; margin-top: auto; }
        .product-card .card-footer { padding: 0 20px 20px 20px; background: transparent; border-top: none; }
        .primary-btn { background-color: #fab700; border: none; color: #fff !important; padding: 10px 20px; border-radius: 50px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s ease; width: 100%; text-align: center; }
        .primary-btn:hover { background-color: #e0a800; color: #fff !important; }
        .feature-item { padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.07); transition: all 0.3s ease; height: 100%; }
        .feature-item:hover { transform: translateY(-5px); }
        .feature-item .icon { font-size: 40px; color: #fab700; margin-bottom: 20px; }
        .feature-item h6 { font-size: 18px; font-weight: 600; }
        .about-section ul { list-style: none; padding-left: 0; }
        .about-section li { display: flex; align-items: flex-start; margin-bottom: 15px; font-size: 16px; }
        .about-section .icon { color: #1cc88a; margin-right: 12px; margin-top: 3px; font-size: 18px; width: 20px; text-align: center; }
        .about-us-image { width: 100%; max-width: 480px; height: 320px; object-fit: cover; display: block; margin: 0 auto; border-radius: 15px;}
    </style>
</head>
<body>

<header class="header_area sticky-header">
    <div class="main_menu">
        <nav class="navbar navbar-expand-lg navbar-light main_box">
            <div class="container">
                <a class="navbar-brand site-brand" href="index.php">SUBANG OUTDOOR</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
                </button>
                <div class="collapse navbar-collapse offset" id="navbarSupportedContent">
                    <ul class="nav navbar-nav menu_nav ml-auto">
                        <li class="nav-item mx-2 <?php if ($currentPage == 'index.php') echo 'active'; ?>">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                        <li class="nav-item mx-2 <?php if ($currentPage == 'produk.php') echo 'active'; ?>">
                            <a class="nav-link" href="penyewa/page/produk.php">Produk</a>
                        </li>
                        <li class="nav-item mx-2 <?php if (in_array($currentPage, ['transaksi.php', 'pembayaran.php', 'booking.php'])) echo 'active'; ?>">
                            <a class="nav-link" href="penyewa/page/transaksi.php">Penyewaan</a>
                        </li>
                        <li class="nav-item mx-2 <?php if ($currentPage == 'bantuan.php') echo 'active'; ?>">
                            <a class="nav-link" href="penyewa/page/bantuan.php">Bantuan</a>
                        </li>
                    </ul>
                    <ul class="nav navbar-nav navbar-right d-flex align-items-center">
                        <li class="nav-item mx-2">
                            <a href="penyewa/page/keranjang.php" class="nav-link" title="Keranjang">
                                <span class="cart-icon-wrapper">
                                    <i class="fas fa-shopping-cart fa-lg"></i>
                                    <?php if ($jumlah_cart > 0): ?>
                                        <span class="cart-badge"><?= $jumlah_cart ?></span>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item dropdown mx-2">
                            <a class="nav-link dropdown-toggle d-flex align-items-center user-dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-user-circle fa-lg"></i>
                                <span><?= htmlspecialchars($username) ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <?php if (!isset($_SESSION['user_id'])): ?>
                                    <a class="dropdown-item" href="login.php">Login</a>
                                <?php else: ?>
                                    <a class="dropdown-item" href="penyewa/page/profil.php">Profil</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="prosesLogin.php?logout=true">Logout</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</header>

<section class="banner-area organic-breadcrumb">
    <div class="container">
        <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
            <div class="col-first">
                <h1>Selamat Datang</h1>
                <nav class="d-flex align-items-center">
                    <a href="#">Jelajahi Kebutuhan Petualangan Anda</a>
                </nav>
            </div>
        </div>
    </div>
</section>

<section class="lattest-product-area py-5" id="produk">
    <div class="container">
        <div class="text-center mb-5 section-title">
            <h2>Produk Terbaru Kami</h2>
            <p>Lihat koleksi terbaru kami untuk kebutuhan camping Anda berikutnya.</p>
        </div>
        <div class="row">
            <?php if (mysqli_num_rows($result_barang) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result_barang)): ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="product-card">
                            <a href="penyewa/page/detail_produk.php?id=<?= $row['id_barang'] ?>">
                                <img src="barang/barang/gambar/<?= htmlspecialchars($row['gambar']); ?>" class="card-img-top" alt="<?= htmlspecialchars($row['nama_barang']); ?>">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($row['nama_barang']); ?></h5>
                                <p class="card-text">Rp <?= number_format($row['harga_sewa'], 0, ',', '.'); ?> / hari</p>
                            </div>
                            <div class="card-footer">
                                <button type="button" class="primary-btn" data-toggle="modal" data-target="#keranjangModal<?= $row['id_barang']; ?>">
                                    <i class="fas fa-cart-plus mr-2"></i>Booking Sekarang
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12"><p class='text-center alert alert-info'>Tidak ada barang tersedia saat ini.</p></div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="penyewa/page/produk.php" class="primary-btn" style="width: auto;">Lihat Semua Produk</a>
        </div>
    </div>
</section>

<section class="features-area section_gap">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 mb-4">
                <div class="feature-item">
                    <div class="icon"><i class="fas fa-box-open"></i></div>
                    <h6>Peralatan Lengkap</h6>
                    <p>Berbagai pilihan tenda, matras, kompor, dll.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="feature-item">
                    <div class="icon"><i class="fas fa-undo-alt"></i></div>
                    <h6>Pengembalian Mudah</h6>
                    <p>Proses pengembalian simpel dan fleksibel.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="feature-item">
                    <div class="icon"><i class="fas fa-headset"></i></div>
                    <h6>24/7 Support</h6>
                    <p>Kami siap membantu Anda kapan saja.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="feature-item">
                    <div class="icon"><i class="fas fa-credit-card"></i></div>
                    <h6>Pembayaran Simpel</h6>
                    <p>Bisa transfer bank atau bayar langsung.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- modal.php -->
<div class="modal fade" id="keranjangModal<?php echo $row['id_barang']; ?>" tabindex="-1" aria-labelledby="keranjangLabel<?php echo $row['id_barang']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="keranjangLabel<?php echo $row['id_barang']; ?>">Tambah ke Keranjang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong><?php echo $row['nama_barang']; ?></strong></p>
        <p>Harga Sewa: Rp.<?php echo number_format($row['harga_sewa'], 0, ',', '.'); ?></p>
        <p>Stok tersedia: <?php echo $row['stok']; ?></p>
        <form method="POST" action="penyewa/controller/tambah_keranjang.php">
          <input type="hidden" name="id_barang" value="<?php echo $row['id_barang']; ?>">
          <input type="number" name="jumlah" class="form-control mb-2" placeholder="Jumlah" min="1" max="<?php echo $row['stok']; ?>" required>
          <button type="submit" class="btn btn-dark">Tambah ke Keranjang</button>
        </form>
      </div>
    </div>
  </div>
</div>


<section class="about-area section_gap" id="about">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="assets/img/bekgrun.jpg" class="img-fluid rounded shadow about-us-image" alt="Tentang Kami">
            </div>
            <div class="col-lg-6">
                <div class="section-title text-left">
                    <h2 class="mb-4">Tentang Subang Outdoor</h2>
                </div>
                <p>Subang Outdoor adalah layanan penyewaan alat camping terlengkap di Subang. Kami menyediakan tenda, matras, kompor , dan perlengkapan lainnya dengan harga terjangkau dan proses yang mudah.</p>
                <ul class="about-section">
                    <li><span class="icon"><i class="fas fa-check-circle"></i></span>Proses pemesanan cepat</li>
                    <li><span class="icon"><i class="fas fa-check-circle"></i></span>Lokasi strategis</li>
                    <li><span class="icon"><i class="fas fa-check-circle"></i></span>Alat selalu bersih dan siap pakai</li>
                    <li><span class="icon"><i class="fas fa-check-circle"></i></span>Pembayaran Simpel</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php include('penyewa/layout/footer.php')?>



<script src="penyewa/page/js/vendor/jquery-2.2.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="penyewa/page/js/vendor/bootstrap.min.js"></script>
<script src="penyewa/page/js/jquery.ajaxchimp.min.js"></script>
<script src="penyewa/page/js/jquery.nice-select.min.js"></script>
<script src="penyewa/page/js/jquery.sticky.js"></script>
<script src="penyewa/page/js/nouislider.min.js"></script>
<script src="penyewa/page/js/jquery.magnific-popup.min.js"></script>
<script src="penyewa/page/js/owl.carousel.min.js"></script>
<script src="penyewa/page/js/main.js"></script>
</body>
</html>