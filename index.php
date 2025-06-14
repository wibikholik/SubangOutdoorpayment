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

<?php include('penyewa/layout/navbar1.php'); ?>

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
          <?php include('penyewa/layout/modal.php'); ?>
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
