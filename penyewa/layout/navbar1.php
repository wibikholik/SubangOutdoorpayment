<?php
// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../route/koneksi.php';

$username = $_SESSION['username'] ?? 'Guest';
$id_penyewa = $_SESSION['user_id'] ?? 0;
$currentPage = basename($_SERVER['PHP_SELF']);

// Jumlah item di keranjang
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

<style>
/* Font dan warna dasar */
body {
    font-family: 'Inter', sans-serif;
    background-color: #f8f9fa;
}

.navbar-brand.site-brand {
    color: #fab700 !important;
    font-weight: 700;
    font-size: 20px;
    letter-spacing: 1px;
}
.navbar-brand.site-brand:hover {
    color: #e0a800 !important;
}

.nav-item.active .nav-link {
    color: #fab700 !important;
    font-weight: bold;
}

/* Keranjang badge */
.cart-badge {
    position: absolute;
    top: -5px;
    right: -10px;
    background: #e74a3b;
    color: white;
    font-size: 11px;
    font-weight: 700;
    border-radius: 50%;
    height: 20px;
    width: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
    z-index: 10;
}

/* Dropdown user */
.user-dropdown-toggle i {
    margin-right: 6px;
}

.user-dropdown-toggle span {
    margin-left: 3px;
}

/* Jarak padding navbar */
.main_menu .navbar .nav-link,
.main_menu .navbar .navbar-brand {
    padding-top: 1.2rem;
    padding-bottom: 1.2rem;
}
</style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<header class="header_area sticky-header">
    <div class="main_menu">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand site-brand" href="../../index.php">
                    <img src="" alt="">SUBANG OUTDOOR
                </a>

                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse offset" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
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

                        <!-- Keranjang -->
                        <li class="nav-item mx-2">
                            <a href="../page/keranjang.php" class="nav-link position-relative" title="Keranjang">
                                <i class="fas fa-shopping-cart fa-lg"></i>
                                <?php if ($jumlah_cart > 0): ?>
                                    <span class="cart-badge"><?= $jumlah_cart ?></span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <!-- Profil -->
                        <li class="nav-item dropdown mx-2">
                            <a class="nav-link dropdown-toggle d-flex align-items-center user-dropdown-toggle" href="#" id="navbarDropdown"
                               role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
