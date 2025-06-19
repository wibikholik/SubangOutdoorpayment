<?php
// Pastikan session sudah start di file utama yang memanggil navbar ini
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Path ke file koneksi disesuaikan dengan struktur asli Anda
include __DIR__ . '/../../route/koneksi.php';

$username = $_SESSION['username'] ?? 'Guest';
$id_penyewa = $_SESSION['user_id'] ?? 0;
// Logika untuk mendeteksi halaman aktif
$currentPage = basename($_SERVER['PHP_SELF']);

// Ambil jumlah item di keranjang dengan cara yang aman
$jumlah_cart = 0;
if ($id_penyewa) {
    $stmt = mysqli_prepare($koneksi, "SELECT SUM(jumlah) AS total FROM carts WHERE id_penyewa = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
        mysqli_stmt_execute($stmt);
        $result_cart = mysqli_stmt_get_result($stmt);
        $data_cart = mysqli_fetch_assoc($result_cart);
        $jumlah_cart = $data_cart['total'] ?? 0;
        mysqli_stmt_close($stmt);
    }
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
  body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .section_gap { padding: 60px 0; }
        .section-title h2 { font-size: 36px; font-weight: 700; margin-bottom: 1rem; color: #222; }
        .section-title p { color: #6c757d; font-size: 16px; }
    /* Wrapper untuk posisi notifikasi yang akurat */
    .cart-icon-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
    }
    /* Aturan CSS presisi untuk notifikasi keranjang */
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
    /* Gaya untuk menu yang aktif */
    .header_area .navbar .nav .nav-item.active .nav-link {
        color: #fab700 !important;
        font-weight: bold;
    }
    /* Jarak ikon pada dropdown profil */
    .user-dropdown-toggle i {
        margin-right: 8px;
    }
    /* Warna brand navbar */
    .navbar-brand.site-brand {
        color: #fab700 !important;
        font-weight: 700;
        font-size: 20px;
        letter-spacing: 1px;
    }
    .navbar-brand.site-brand:hover {
        color: #e0a800 !important;
    }
</style>

<header class="header_area sticky-header">
    <div class="main_menu">
        <nav class="navbar navbar-expand-lg navbar-light main_box">
            <div class="container">
                <a class="navbar-brand site-brand" href="index.html"><img src="" alt="">SUBANG OUTDOOR</a>
                
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
                </button>

                <div class="collapse navbar-collapse offset" id="navbarSupportedContent">
                    <ul class="nav navbar-nav menu_nav ml-auto">
                        <li class="nav-item mx-2 <?php if ($currentPage == 'index.php') echo 'active'; ?>">
                            <a class="nav-link" href="../../index.php">Home</a>
                        </li>
                        <li class="nav-item mx-2 <?php if ($currentPage == 'produk.php') echo 'active'; ?>">
                            <a class="nav-link" href="../page/produk.php">Produk</a>
                        </li>
                        <li class="nav-item mx-2 <?php if (in_array($currentPage, ['transaksi.php', 'pembayaran.php', 'booking.php'])) echo 'active'; ?>">
                            <a class="nav-link" href="../page/transaksi.php">Penyewaan</a>
                        </li>
                        <li class="nav-item mx-2 <?php if ($currentPage == 'bantuan.php') echo 'active'; ?>">
                            <a class="nav-link" href="../page/bantuan.php">Bantuan</a>
                        </li>
                    </ul>
                    <ul class="nav navbar-nav navbar-right d-flex align-items-center">
                        <li class="nav-item mx-2">
                            <a href="../page/keranjang.php" class="nav-link" title="Keranjang">
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
                                    <a class="dropdown-item" href="../../login.php">Login</a>
                                <?php else: ?>
                                    <a class="dropdown-item" href="../page/profil.php">Profil</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../../prosesLogin.php?logout=true">Logout</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</header>