<?php
$role = $_SESSION['role'] ?? 'guest';
$username = $_SESSION['username'] ?? 'Guest';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
        <div class="sidebar-brand-icon">
            <img src="../assets/img/logo.jpg" alt="Logo" style="width: 50px; height: 50px; border-radius: 50%;">
        </div>
        <div class="sidebar-brand-text mx-3">Subang Outdoor</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <?php
    switch ($role) {
        case 'admin':
            ?>
            <!-- Menu untuk Admin -->
           <li class="nav-item <?php if ($currentPage == 'index_admin.php') echo 'active'; ?>">
                <a class="nav-link" href="../admin/index_admin.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'barang.php') echo 'active'; ?>">
                <a class="nav-link" href="../barang/barang.php">
                    <i class="fas fa-fw fa-boxes"></i>
                    <span>Barang</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'kategori.php') echo 'active'; ?>">
                <a class="nav-link" href="../kategori/kategori.php">
                    <i class="fas fa-fw fa-tag"></i>
                    <span>Kategori Barang</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'kelengkapan.php') echo 'active'; ?>">
                <a class="nav-link" href="../kelengkapan/kelengkapan.php">
                    <i class="fas fa-fw fa-tag"></i>
                    <span>Kelengkapan Barang</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'penyewa.php') echo 'active'; ?>">
                <a class="nav-link" href="../dataPenyewa/penyewa.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Penyewa</span>
                </a>
            </li>
              <li class="nav-item <?php if ($currentPage == 'metode.php') echo 'active'; ?>">
                <a class="nav-link" href="../metode_pembayaran/metode.php">
                    <i class="fas fa-fw fa-credit-card"></i>
                    <span>Metode Pembayaran</span>
                </a>
            </li>
              <li class="nav-item <?php if ($currentPage == 'tipe_metode.php') echo 'active'; ?>">
                <a class="nav-link" href="../tipe metode/tipe_metode.php">
                    <i class="fas fa-fw fa-credit-card"></i>
                    <span>Tipe Metode Pembayaran</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'transaksi.php') echo 'active'; ?>">
                <a class="nav-link" href="../transaksi/transaksi.php">
                    <i class="fas fa-fw fa-clipboard-list"></i>
                    <span>Transaksi</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'pembayaran.php') echo 'active'; ?>">
                <a class="nav-link" href="../pembayaran/pembayaran.php">
                    <i class="fas fa-fw fa-money-check"></i>
                    <span>Pembayaran</span>
                </a>
            </li>
           
             <li class="nav-item <?php if ($currentPage == 'pengembalian.php') echo 'active'; ?>">
                <a class="nav-link" href="../pengembalian/pengembalian.php">
                    <i class="fas fa-fw fa-undo"></i>
                    <span>Pengembalian</span>
                </a>
            </li>
            <?php
            break;

        case 'owner':
            ?>
            <!-- Menu untuk Owner -->
            <li class="nav-item <?php if ($currentPage == 'index_owner.php') echo 'active'; ?>">
                <a class="nav-link" href="../owner/index_owner.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
             <li class="nav-item <?php if ($currentPage == 'barang.php') echo 'active'; ?>">
                <a class="nav-link" href="../barang/barang.php">
                    <i class="fas fa-fw fa-boxes"></i>
                    <span>Barang</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'kategori.php') echo 'active'; ?>">
                <a class="nav-link" href="../kategori/kategori.php">
                    <i class="fas fa-fw fa-tag"></i>
                    <span>Kategori Barang</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'kelengkapan.php') echo 'active'; ?>">
                <a class="nav-link" href="../kelengkapan/kelengkapan.php">
                    <i class="fas fa-fw fa-tag"></i>
                    <span>kelengkapan Barang</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'penyewa.php') echo 'active'; ?>">
                <a class="nav-link" href="../dataPenyewa/penyewa.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Penyewa</span>
                </a>
            </li>
              <li class="nav-item <?php if ($currentPage == 'admin.php') echo 'active'; ?>">
                <a class="nav-link" href="../dataAdmin/admin.php">
                    <i class="fas fa-fw fa-user-shield"></i>
                    <span>Data Admin</span>
                </a>
            </li>
              <li class="nav-item <?php if ($currentPage == 'metode.php') echo 'active'; ?>">
                <a class="nav-link" href="../metode_pembayaran/metode.php">
                    <i class="fas fa-fw fa-credit-card"></i>
                    <span>Metode Pembayaran</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'tipe_metode.php') echo 'active'; ?>">
                <a class="nav-link" href="../tipe metode/tipe_metode.php">
                    <i class="fas fa-fw fa-credit-card"></i>
                    <span>Tipe Metode Pembayaran</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'transaksi.php') echo 'active'; ?>">
                <a class="nav-link" href="../transaksi/transaksi.php">
                    <i class="fas fa-fw fa-clipboard-list"></i>
                    <span>Transaksi</span>
                </a>
            </li>
            <li class="nav-item <?php if ($currentPage == 'pembayaran.php') echo 'active'; ?>">
                <a class="nav-link" href="../pembayaran/pembayaran.php">
                    <i class="fas fa-fw fa-money-check"></i>
                    <span>Pembayaran</span>
                </a>
            </li>
           
            <li class="nav-item <?php if ($currentPage == 'pengembalian.php') echo 'active'; ?>">
                <a class="nav-link" href="../pengembalian/pengembalian.php">
                    <i class="fas fa-fw fa-undo"></i>
                    <span>Pengembalian</span>
                </a>
            </li>
           
             <li class="nav-item <?php if ($currentPage == 'laporan.php') echo 'active'; ?>">
                <a class="nav-link" href="../laporanowner/laporan.php">
                    <i class="fas fa-fw fa-file-alt"></i>
                    <span>Laporan</span>
                </a>
            </li>
            <?php
            break;

        default:
            ?>
            <!-- Menu untuk Guest (opsional) -->
             <li class="nav-item">
                <a class="nav-link" href="../login.php">
                    <i class="fas fa-fw fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            </li>
            <?php
            break;
    }
    ?>

    <hr class="sidebar-divider my-0">
</ul>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="../assets/css/custom.css" rel="stylesheet" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    
</body>
</html>
