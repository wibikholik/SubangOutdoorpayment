<?php
// Letakkan koneksi dan session start di atas
include '../../route/koneksi.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="../../assets/img/logo.jpg">
    <title>Produk - Subang Outdoor</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/linearicons.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/nouislider.min.css">
    <link rel="stylesheet" href="css/main.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .filter-bar {
            background: #ffffff; padding: 15px 20px; border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.07); margin-bottom: 30px;
        }
        .nice-select { border-radius: 50px; border-color: #e0e0e0; font-weight: 600; }
        .product-card {
            background: #fff; border-radius: 12px; border: none; overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.07); transition: all 0.3s ease;
            display: flex; flex-direction: column; height: 100%;
        }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }
        .product-card .product-img-container { height: 250px; overflow: hidden; }
        .product-card .product-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .product-card .product-details { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .product-card .product-details h6 { font-weight: 600; font-size: 16px; margin-bottom: 10px; flex-grow: 1; }
        .product-card .price { margin-top: auto; margin-bottom: 15px; }
        .product-card .price h6 { font-size: 18px; color: #fab700; font-weight: 700; margin-bottom: 0; }
        .primary-btn {
            background-color: #fab700; border: none; color: #fff !important; padding: 10px 20px;
            border-radius: 50px; font-weight: 700; cursor: pointer; text-decoration: none;
            display: inline-block; transition: all 0.3s ease; width: 100%; text-align: center;
        }
        .primary-btn:hover { background-color: #e0a800; color: #fff !important; }
        .sidebar-widget {
            background: #fff; padding: 25px; border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.07);
        }
        .sidebar-widget h3 { font-size: 20px; font-weight: 700; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .unggulan-item { display: flex; align-items: center; margin-bottom: 20px; text-decoration: none; }
        .unggulan-item:last-child { margin-bottom: 0; }
        .unggulan-item img { width: 60px; height: 60px; object-fit: cover; margin-right: 15px; border-radius: 8px; }
        .unggulan-item .info .nama-barang { font-weight: 600; color: #333; display: block; transition: color 0.3s ease; }
        .unggulan-item:hover .info .nama-barang { color: #fab700; }
        .unggulan-item .info .harga { font-weight: bold; color: #6c757d; }
        .sticky-sidebar { position: -webkit-sticky; position: sticky; top: 110px; }
    </style>
</head>

<body id="category">
    <?php include("../layout/navbar1.php"); ?>

    <section class="banner-area organic-breadcrumb">
        <div class="container">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Daftar Produk</h1>
                    <nav class="d-flex align-items-center">
                        <a href="/subangoutdoor/index.php">Home<span class="lnr lnr-arrow-right"></span></a>
                        <a href="#">Produk</a>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-lg-8 col-xl-9">
                <?php
                // Logika PHP untuk mengambil data produk
                $kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
                $show = isset($_GET['show']) ? (int)$_GET['show'] : 12;
                if (!in_array($show, [12, 24, 36])) { $show = 12; }

                $kategoriResult = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

                $query_barang = "SELECT * FROM barang ";
                if ($kategori > 0) { $query_barang .= "WHERE id_kategori = ? "; }
                $query_barang .= "LIMIT ?";
                
                $stmt = $koneksi->prepare($query_barang);
                if (!$stmt) { die("Prepare failed: " . $koneksi->error); }
                if ($kategori > 0) { $stmt->bind_param("ii", $kategori, $show); } 
                else { $stmt->bind_param("i", $show); }
                $stmt->execute();
                $result = $stmt->get_result();
                ?>
                
                <div class="filter-bar d-flex flex-wrap align-items-center justify-content-start">
                    <form method="GET" class="d-flex align-items-center" action="produk.php">
                        <div class="sorting mr-3">
                           <select name="kategori" onchange="this.form.submit()">
                                <option value="0">Semua Kategori</option>
                                <?php mysqli_data_seek($kategoriResult, 0); while ($kat = mysqli_fetch_assoc($kategoriResult)) : ?>
                                <option value="<?= $kat['id_kategori']; ?>" <?= ($kategori == $kat['id_kategori']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="sorting">
                            <select name="show" onchange="this.form.submit()">
                                <option value="12" <?= ($show == 12) ? 'selected' : ''; ?>>Tampil 12</option>
                                <option value="24" <?= ($show == 24) ? 'selected' : ''; ?>>Tampil 24</option>
                                <option value="36" <?= ($show == 36) ? 'selected' : ''; ?>>Tampil 36</option>
                            </select>
                        </div>
                    </form>
                </div>

                <section class="lattest-product-area category-list">
                    <div class="row">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="product-card">
                                        <div class="product-img-container">
                                         
                                                <img class="img-fluid" src="../../barang/barang/gambar/<?= htmlspecialchars($row['gambar']); ?>" alt="<?= htmlspecialchars($row['nama_barang']); ?>">
                                            
                                        </div>
                                        <div class="product-details">
                                            <h6><?= htmlspecialchars($row['nama_barang']); ?></h6>
                                             <h6>Stok: <?= htmlspecialchars($row['stok']); ?></h6>
                                            <div class="price">
                                                <h6>Rp <?= number_format($row['harga_sewa'], 0, ',', '.'); ?>/hari</h6>
                                            </div>
                                            <button type="button" class="primary-btn" data-toggle="modal" data-target="#keranjangModal<?= $row['id_barang']; ?>">
                                                <i class="fas fa-cart-plus mr-2"></i>Booking Sekarang
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12"><p class='text-center alert alert-warning'>Tidak ada produk yang ditemukan.</p></div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div class="col-lg-4 col-xl-3">
                <div class="sticky-sidebar">
                    <div class="sidebar-widget">
                        <h3>Produk Unggulan</h3>
                        <?php
                        $unggulanQuery = "SELECT * FROM barang WHERE unggulan = 1 LIMIT 5";
                        $unggulanResult = mysqli_query($koneksi, $unggulanQuery);
                        if (mysqli_num_rows($unggulanResult) > 0):
                            while ($row = mysqli_fetch_assoc($unggulanResult)): ?>
                                <a href="detail_produk.php?id=<?= $row['id_barang'] ?>" class="unggulan-item">
                                    <img src="../../barang/barang/gambar/<?= htmlspecialchars($row['gambar']); ?>" alt="<?= htmlspecialchars($row['nama_barang']); ?>">
                                    <div class="info">
                                        <span class="nama-barang"><?= htmlspecialchars($row['nama_barang']); ?></span>
                                        <p class="harga mb-0">Rp <?= number_format($row['harga_sewa'], 0, ',', '.'); ?></p>
                                    </div>
                                </a>
                            <?php endwhile;
                        else:
                            echo "<p class='text-muted small'>Tidak ada produk unggulan saat ini.</p>";
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    mysqli_data_seek($result, 0);
    if ($result->num_rows > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            if (file_exists('../layout/modal.php')) {
                include('../layout/modal.php');
            }
        }
    }
    ?>

    <?php include("../layout/footer.php"); ?>

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
    <script>
        $(document).ready(function() {
            $('select').niceSelect();
        });
    </script>
</body>
</html>