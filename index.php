<?php
include 'route/koneksi.php';
$query = "SELECT * FROM barang ORDER BY id_barang DESC LIMIT 8";
$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subang Outdoor - Sewa Alat Camping</title>
    <link rel="stylesheet" href="penyewa/page/css/bootstrap.css">
    <link rel="stylesheet" href="penyewa/page/css/main.css">
    <link rel="stylesheet" href="penyewa/page/css/font-awesome.min.css">
    <link rel="stylesheet" href="penyewa/page/css/linearicons.css">
  <link rel="stylesheet" href="penyewa/page/css/owl.carousel.css">
  <link rel="stylesheet" href="penyewa/page/css/font-awesome.min.css">
  <link rel="stylesheet" href="penyewa/page/css/themify-icons.css"> 
  <link rel="stylesheet" href="penyewa/page/css/nice-select.css">
  <link rel="stylesheet" href="penyewa/page/css/nouislider.min.css">
  <link rel="stylesheet" href="penyewa/page/css/bootstrap.css">
  <link rel="stylesheet" href="penyewa/page/css/main.css">
   <link rel="shortcut icon" href="assets/img/logo.jpg">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>

<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? 'Guest';
$id_penyewa = $_SESSION['user_id'] ?? 0;

// Ambil jumlah item di keranjang
$jumlah_cart = 0;
if ($id_penyewa) {
  $query_cart = mysqli_query($koneksi, "SELECT SUM(jumlah) AS total FROM carts WHERE id_penyewa = '$id_penyewa'");
  $data_cart = mysqli_fetch_assoc($query_cart);
  $jumlah_cart = $data_cart['total'] ?? 0;
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.cart-badge {
  position: relative;
  top: -10px;
  right: -1px;
  background: red;
  color: white;
  font-size: 10px;
  font-weight: bold;
  border-radius: 50%;
  padding: 3px 6px;
  line-height: 1;
  min-width: 15px;
  text-align: center;
  z-index: 10;
}
</style>

<header class="header_area sticky-header">
  <div class="main_menu">
    <nav class="navbar navbar-expand-lg navbar-light main_box">
      <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <a class="navbar-brand logo_h" href="index.html"><img src="" alt="">SUBANG OUTDOOR</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse offset" id="navbarSupportedContent">
          <ul class="nav navbar-nav menu_nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="penyewa/page/produk.php">Product</a></li>
            <li class="nav-item"><a class="nav-link" href="penyewa/page/transaksi.php">Penyewaan</a></li>
            <li class="nav-item"><a class="nav-link" href="penyewa/page/bantuan.php">Bantuan</a></li>
          </ul>

          <ul class="nav navbar-nav navbar-right d-flex align-items-center">
            <!-- Keranjang -->
            <li class="nav-item mx-2" style="position: relative;">
              <a href="penyewa/page/keranjang.php" title="Keranjang">
                <i class="fas fa-shopping-cart fa-lg"></i>
                <?php if ($jumlah_cart > 0): ?>
                  <span class="cart-badge"><?= $jumlah_cart ?></span>
                <?php endif; ?>
              </a>
            </li>

            <!-- Dropdown Profil -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <?php if (!isset($_SESSION['user_id'])): ?>
                  <li><a class="dropdown-item" href="login.php">Login</a></li>
                <?php else: ?>
                  <li><a class="dropdown-item" href="penyewa/page/profil.php">Profil</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="prosesLogin.php?logout=true">Logout</a></li>
                <?php endif; ?>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </div>
  <div class="search_input" id="search_input_box">
    <div class="container">
      <form class="d-flex justify-content-between">
        <input type="text" class="form-control" id="search_input" placeholder="Search Here">
        <button type="submit" class="btn"></button>
        <span class="lnr lnr-cross" id="close_search" title="Close Search"></span>
      </form>
    </div>
  </div>
</header>


<!-- Hero Section -->
<section class="banner-area organic-breadcrumb">
  <div class="container">
    <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
      <div class="col-first">
        <h1>Subang Outdoor</h1>
        <nav class="d-flex align-items-center">
          <a href="#">Home</a>
        </nav>
      </div>
    </div>
  </div>
</section>

<!-- Tentang Kami -->


<!-- Produk Terbaru -->
<section class="lattest-product-area section_gap" id="produk">
  <div class="container">
    <div class="text-center mb-5">
      <h2>Produk Terbaru</h2>
      <p>Lihat koleksi terbaru kami untuk kebutuhan camping Anda</p>
    </div>
    <div class="row">
      <?php
      if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
      ?>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 shadow">
              <img src="barang/barang/gambar/<?php echo $row['gambar']; ?>" class="card-img-top" alt="<?php echo $row['nama_barang']; ?>">
              <div class="card-body">
                <h5 class="card-title"><?php echo $row['nama_barang']; ?></h5>
                <p class="card-text">Rp <?php echo number_format($row['harga_sewa'], 0, ',', '.'); ?> / hari</p>
              </div>
              <div class="card-footer text-center">
               <button type="button" class="btn btn-dark w-100" data-bs-toggle="modal" data-bs-target="#keranjangModal<?php echo $row['id_barang']; ?>">
                        Booking Sekarang
                      </button>
              </div>
            </div>
          </div>
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

      <?php
        }
      } else {
        echo "<p class='text-center'>Tidak ada barang tersedia.</p>";
      }
      ?>
    </div>
  </div>
</section>

<!-- Fitur -->
<section class="features-area section_gap">
  <div class="container">
    <div class="row text-center">
      <div class="col-md-3">
        <div class="single-features">
          <img src="penyewa/page/img/features/icon1.jpg" alt="" class="mb-2" width="40">
          <h6>Peralatan Lengkap</h6>
          <p>Berbagai pilihan tenda, matras, kompor, dll.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="single-features">
          <img src="penyewa/page/img/features/f-icon2.png" alt="" class="mb-2" width="40">
          <h6>Pengembalian Mudah</h6>
          <p>Proses pengembalian simpel dan fleksibel.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="single-features">
          <img src="penyewa/page/img/features/f-icon3.png" alt="" class="mb-2" width="40">
          <h6>24/7 Support</h6>
          <p>Kami siap membantu kapan saja.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="single-features">
          <img src="penyewa/page/img/features/f-icon4.png" alt="" class="mb-2" width="40">
          <h6>Pembayaran Simpel</h6>
          <p>Bisa transfer bank atau Bayar langsung.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="about-area section_gap" id="about">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <img src="layout/gunung.jpg" class="img-fluid rounded shadow" alt="Tentang Kami">
      </div>
      <div class="col-lg-6">
        <h2 class="mb-4">Tentang Kami</h2>
        <p>Subang Outdoor adalah layanan penyewaan alat camping terlengkap di Subang. Kami menyediakan tenda, matras, kompor , dan perlengkapan lainnya dengan harga terjangkau dan proses yang mudah.</p>
        <ul>
          <li>✔ Proses pemesanan cepat</li>
          <li>✔ Lokasi strategis</li>
          <li>✔ Alat selalu bersih dan siap pakai</li>
          <li>✔ Pembayaran Simpel</li>
        </ul>
      </div>
    </div>
  </div>
</section>


<!-- Footer -->
<?php include('penyewa/layout/footer.php')?>

<!-- Script -->
<script src="penyewa/page/js/vendor/jquery-2.2.4.min.js"></script>
<script src="penyewa/page/https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="penyewa/page/js/vendor/bootstrap.min.js"></script>
<script src="penyewa/page/js/jquery.ajaxchimp.min.js"></script>
<script src="penyewa/page/js/jquery.nice-select.min.js"></script>
<script src="penyewa/page/js/jquery.sticky.js"></script>
<script src="penyewa/page/js/nouislider.min.js"></script>
<script src="penyewa/page/js/jquery.magnific-popup.min.js"></script>
<script src="penyewa/page/js/owl.carousel.min.js"></script>
<script src="penyewa/page/js/gmaps.min.js"></script>
<script src="penyewa/page/js/main.js"></script>

</body>
</html>
