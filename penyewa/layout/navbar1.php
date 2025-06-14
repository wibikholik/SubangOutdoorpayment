<?php
include __DIR__ . '/../../route/koneksi.php';

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
            <li class="nav-item"><a class="nav-link" href="/subangoutdoor/index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="/subangoutdoor/penyewa/page/produk.php">Product</a></li>
            <li class="nav-item"><a class="nav-link" href="/subangoutdoor/penyewa/page/transaksi.php">Penyewaan</a></li>
            <li class="nav-item"><a class="nav-link" href="/subangoutdoor/penyewa/page/bantuan.php">Bantuan</a></li>
          </ul>

          <ul class="nav navbar-nav navbar-right d-flex align-items-center">
            <!-- Keranjang -->
            <li class="nav-item mx-2" style="position: relative;">
              <a href="/subangoutdoor/penyewa/page/keranjang.php" title="Keranjang">
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
                  <li><a class="dropdown-item" href="/subangoutdoor/login.php">Login</a></li>
                <?php else: ?>
                  <li><a class="dropdown-item" href="/subangoutdoor/penyewa/page/profil.php">Profil</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="/subangoutdoor/prosesLogin.php?logout=true">Logout</a></li>
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
