<?php
// 1. Memulai Session (HARUS PALING PERTAMA)
session_start();
// 2. Koneksi ke Database
include 'route/koneksi.php';

// 3. Logika untuk Navbar (mengambil data user dan keranjang)
$username = $_SESSION['username'] ?? 'Guest';
$id_penyewa = $_SESSION['user_id'] ?? 0;
$currentPage = basename($_SERVER['PHP_SELF']);// untuk mengetahui nama file saat ini

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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Subang Outdoor - Sewa Alat Camping</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    />
    <link rel="stylesheet" href="penyewa/page/css/linearicons.css" />
    <link rel="stylesheet" href="penyewa/page/css/themify-icons.css" />
    <link rel="stylesheet" href="penyewa/page/css/bootstrap.css" />
    <link rel="stylesheet" href="penyewa/page/css/owl.carousel.css" />
    <link rel="stylesheet" href="penyewa/page/css/nice-select.css" />
    <link rel="stylesheet" href="penyewa/page/css/nouislider.min.css" />
    <link rel="stylesheet" href="penyewa/page/css/main.css" />
    <link rel="shortcut icon" href="assets/img/logo.jpg" />
    <style>
      body {
        font-family: 'Inter', sans-serif;
        background-color: #f8f9fa;
      }
      .section_gap {
        padding: 60px 0;
      }
      .section-title h2 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 1rem;
        color: #222;
      }
      .section-title p {
        color: #6c757d;
        font-size: 16px;
      }
      /* Gaya Navbar */
      .cart-icon-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
      }
      .cart-badge {
        position: absolute;
        top: 0;
        right: 0;
        transform: translate(50%, -60%);
        height: 22px;
        width: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #e74a3b;
        color: white;
        font-size: 11px;
        font-weight: 700;
        border-radius: 50%;
        border: 2px solid white;
        z-index: 10;
        padding: 0;
      }
      .header_area .navbar .nav .nav-item.active .nav-link {
        color: #fab700 !important;
        font-weight: bold;
      }
      .user-dropdown-toggle i {
        margin-right: 8px;
      }
      .navbar-brand.site-brand {
        color: #fab700 !important;
        font-weight: 700;
        font-size: 20px;
        letter-spacing: 1px;
        transition: color 0.3s ease;
      }
      .navbar-brand.site-brand:hover {
        color: #e0a800 !important;
      }
      /* Gaya Konten Halaman */
      .product-card {
        background: #fff;
        border-radius: 12px;
        border: none;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.07);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
      }
      .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
      }
      .product-card .card-img-top {
        height: 250px;
        object-fit: cover;
      }
      .product-card .card-body {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
      }
      .product-card .card-title {
        font-weight: 600;
        font-size: 16px;
        flex-grow: 1;
        margin-bottom: 10px;
      }
      .product-card .card-text {
        font-size: 18px;
        color: #fab700;
        font-weight: 700;
        margin-top: auto;
      }
      .product-card .card-footer {
        padding: 0 20px 20px 20px;
        background: transparent;
        border-top: none;
      }
      .primary-btn {
        background-color: #fab700;
        border: none;
        color: #fff !important;
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        width: 100%;
        text-align: center;
      }
      .primary-btn:hover {
        background-color: #e0a800;
        color: #fff !important;
      }
      .main_menu .navbar .nav-link {
    padding-top: 1rem;    /* Defaultnya bisa jadi 0.5rem, kita perbesar */
    padding-bottom: 1rem;
  }
  .main_menu .navbar .navbar-brand {
    padding-top: 2rem;
    padding-bottom: 2rem;
  }
  .user-dropdown-toggle span {
    margin-left: 3px; /* Sesuaikan jarak sesuai selera */
  }
  .single-feature {
  padding: 20px;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  transition: all 0.3s ease;
}
.single-feature:hover {
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
  transform: translateY(-5px);
}
.feature-icon img {
  max-height: 50px;
}
.feature-icon i {
  font-size: 2rem !important; /* pastikan semua pakai ukuran sama */
  width: 2.5rem;              /* seragam lebarnya */
  height: 2.5rem;
  line-height: 2.5rem;
  display: inline-block;
  color: #fab700;
  text-align: center;
}

.single-feature:hover .feature-icon i {
  transform: scale(1.1);
}
    </style>
</head>
<body>

<header class="header_area sticky-header">
  <div class="main_menu">
    <nav class="navbar navbar-expand-lg navbar-light main_box">
      <div class="container">
        <a class="navbar-brand site-brand" href="index.php">SUBANG OUTDOOR</a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="icon-bar"></span><span class="icon-bar"></span
          ><span class="icon-bar"></span>
        </button>
        <div class="collapse navbar-collapse offset" id="navbarSupportedContent">
          <ul class="nav navbar-nav menu_nav ml-auto">
            <li
              class="nav-item mx-2 <?= $currentPage == 'index.php' ? 'active' : '' ?>"
            >
              <a class="nav-link" href="index.php">Home</a>
            </li>
            <li
              class="nav-item mx-2 <?= $currentPage == 'produk.php' ? 'active' : '' ?>"
            >
              <a class="nav-link" href="penyewa/page/produk.php">Produk</a>
            </li>
            <li
              class="nav-item mx-2 <?= in_array($currentPage, ['transaksi.php', 'pembayaran.php', 'booking.php']) ? 'active' : '' ?>"
            >
              <a class="nav-link" href="penyewa/page/transaksi.php">Penyewaan</a>
            </li>
            <li
              class="nav-item mx-2 <?= $currentPage == 'bantuan.php' ? 'active' : '' ?>"
            >
              <a class="nav-link" href="penyewa/page/bantuan.php">Bantuan</a>
            </li>
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
                <a
                  class="nav-link dropdown-toggle d-flex align-items-center user-dropdown-toggle"
                  href="#"
                  id="navbarDropdown"
                  role="button"
                  data-bs-toggle="dropdown"
                  aria-haspopup="true"
                  aria-expanded="false"
                >
                  <i class="fas fa-user-circle fa-lg"></i>
                  <span><?= htmlspecialchars($username) ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                  <?php if (!isset($_SESSION['user_id'])): ?>
                  <a class="dropdown-item" href="login.php">Login</a>
                  <?php else: ?>
                  <a class="dropdown-item" href="penyewa/page/profil.php">Profil</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="prosesLogin.php?logout=true">Logout</a>
                  <?php endif; ?>
                </div>
            </li>
          </ul> </div>
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
         <div class="col-lg-3 col-sm-6 col-6 mb-4 d-flex">

            <div class="product-card">
             
                <img src="barang/barang/gambar/<?= htmlspecialchars($row['gambar']); ?>" class="card-img-top" alt="<?= htmlspecialchars($row['nama_barang']); ?>" />
            
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($row['nama_barang']); ?></h5>
                <p class="card-text">Rp <?= number_format($row['harga_sewa'], 0, ',', '.'); ?> / hari</p>
              </div>
              <div class="card-footer">
                <button type="button" class="primary-btn" data-bs-toggle="modal" data-bs-target="#keranjangModal<?= $row['id_barang']; ?>">
                Booking Sekarang
                </button>
              </div>
            </div>
          </div>
<!-- tentang kami -->
 
          <!-- Modal -->
          <div
            class="modal fade"
            id="keranjangModal<?= $row['id_barang']; ?>"
            tabindex="-1"
            aria-labelledby="keranjangLabel<?= $row['id_barang']; ?>"
            aria-hidden="true"
          >
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="keranjangLabel<?= $row['id_barang']; ?>">Tambah ke Keranjang</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <p><strong><?= htmlspecialchars($row['nama_barang']); ?></strong></p>
                  <p>Harga Sewa: Rp.<?= number_format($row['harga_sewa'], 0, ',', '.'); ?></p>
                  <p>Stok tersedia: <?= $row['stok']; ?></p>
                  <form method="POST" action="penyewa/controller/tambah_keranjang.php">
                    <input type="hidden" name="id_barang" value="<?= $row['id_barang']; ?>" />
                    <input
                      type="number"
                      name="jumlah"
                      class="form-control mb-2"
                      placeholder="Jumlah"
                      min="1"
                      max="<?= $row['stok']; ?>"
                      required
                    />
                    <button type="submit" class="btn btn-dark w-100">Tambah ke Keranjang</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12">
          <p class="text-center alert alert-info">Tidak ada barang tersedia saat ini.</p>
        </div>
      <?php endif; ?>
    </div>
    <div class="text-center mt-4">
      <a href="penyewa/page/produk.php" class="primary-btn" style="width: auto;">Lihat Semua Produk</a>
    </div>
  </div>
</section>
<!-- Fitur -->
<!-- ======= Features Section ======= -->
<section class="feature-area section_gap">
  <div class="container">
    <div class="row justify-content-center mb-4">
      <div class="col-lg-12 text-center">
        <div class="section-title">
          <h2>Kenapa Memilih Kami?</h2>
          <p>Keunggulan layanan Subang Outdoor untuk pengalaman camping terbaik</p>
        </div>
      </div>
    </div>
    <div class="row">
      <!-- Peralatan Lengkap -->
      <div class="col-lg-3 col-md-6 mb-4 text-center">
        <div class="single-feature shadow-sm p-4 rounded bg-white h-100">
          <div class="feature-icon mb-3 text-warning">
            <i class="fas fa-campground fa-3x"></i>
          </div>
          <h6 class="font-weight-bold">Peralatan Lengkap</h6>
          <p>Berbagai pilihan tenda, matras, kompor, dan perlengkapan lainnya.</p>
        </div>
      </div>
      <!-- Pengembalian Mudah -->
      <div class="col-lg-3 col-md-6 mb-4 text-center">
        <div class="single-feature shadow-sm p-4 rounded bg-white h-100">
          <div class="feature-icon mb-3 text-warning">
            <i class="fas fa-undo-alt fa-3x"></i>
          </div>
          <h6 class="font-weight-bold">Pengembalian Mudah</h6>
          <p>Proses pengembalian fleksibel dan tanpa ribet.</p>
        </div>
      </div>
      <!-- 24/7 Support -->
      <div class="col-lg-3 col-md-6 mb-4 text-center">
        <div class="single-feature shadow-sm p-4 rounded bg-white h-100">
          <div class="feature-icon mb-3 text-warning">
            <i class="fas fa-headset fa-3x"></i>
          </div>
          <h6 class="font-weight-bold">24/7 Support</h6>
          <p>Layanan pelanggan yang siap membantu kapan saja.</p>
        </div>
      </div>
      <!-- Pembayaran Simpel -->
      <div class="col-lg-3 col-md-6 mb-4 text-center">
        <div class="single-feature shadow-sm p-4 rounded bg-white h-100">
          <div class="feature-icon mb-3 text-warning">
            <i class="fas fa-credit-card fa-3x"></i>
          </div>
          <h6 class="font-weight-bold">Pembayaran Simpel</h6>
          <p>Bisa melalui transfer bank atau bayar langsung saat ambil.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======= About Section ======= -->
<section class="about-area section_gap" id="about">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 mb-4 mb-lg-0 d-none d-lg-flex justify-content-center">
        <!-- Ikon besar sebagai pengganti gambar -->
        <i class="fas fa-info-circle fa-8x text-warning"></i>
      </div>
      <div class="col-lg-6">
        <div class="section-title">
          <h2 class="mb-4">Tentang Kami</h2>
          <p class="text-muted">
            Subang Outdoor adalah penyedia layanan penyewaan perlengkapan camping terpercaya di Subang. Kami menghadirkan berbagai alat camping berkualitas, mulai dari tenda, matras, kompor, hingga perlengkapan pendukung lainnya, dengan harga yang kompetitif serta kemudahan proses pemesanan.
          </p>
        </div>
        <ul class="list-unstyled mt-4" style="font-size: 1.1rem; line-height: 1.75; color: #444;">
          <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Proses pemesanan yang cepat dan mudah</li>
          <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Lokasi strategis dengan akses mudah dijangkau</li>
          <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Perlengkapan selalu dalam kondisi bersih dan siap pakai</li>
          <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Sistem pembayaran yang simpel dan fleksibel</li>
        </ul>
      </div>
    </div>
  </div>
</section>



<?php include('penyewa/layout/footer.php') ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
