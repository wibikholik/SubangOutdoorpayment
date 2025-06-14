<?php
include '../../route/koneksi.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = $_SESSION['user_id'];
$id_transaksi = isset($_GET['id_transaksi']) ? intval($_GET['id_transaksi']) : 0;

if (!$id_transaksi) {
    die("ID Transaksi tidak ditemukan.");
}

// Cek apakah checklist sudah ada
$stmt_check = $koneksi->prepare("SELECT COUNT(*) as jumlah FROM checklist WHERE id_transaksi = ?");
$stmt_check->bind_param("i", $id_transaksi);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$row_check = $result_check->fetch_assoc();
$checklist_exists = ($row_check['jumlah'] > 0);
$stmt_check->close();

// Proses penyimpanan checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_checklist']) && !$checklist_exists) {
    $id_transaksi_post = intval($_POST['id_transaksi']);

    if (!empty($_POST['status_awal']) && is_array($_POST['status_awal'])) {
        foreach ($_POST['status_awal'] as $id_kelengkapan => $status_awal) {
            $id_kelengkapan = intval($id_kelengkapan);
            $keterangan = $_POST['keterangan'][$id_kelengkapan] ?? '';

            $stmt = $koneksi->prepare("INSERT INTO checklist (id_transaksi, id_kelengkapan, status_awal, keterangan_awal, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("iiss", $id_transaksi_post, $id_kelengkapan, $status_awal, $keterangan);
                if (!$stmt->execute()) {
                    echo "Error insert checklist: " . $stmt->error;
                    $stmt->close();
                    exit;
                }
                $stmt->close();
            } else {
                echo "Error prepare statement: " . $koneksi->error;
                exit;
            }
        }

        // Update status checklist ke transaksi
        $update_status = $koneksi->prepare("UPDATE transaksi SET status_checklist = 1 WHERE id_transaksi = ?");
        $update_status->bind_param("i", $id_transaksi_post);
        if (!$update_status->execute()) {
            echo "Error update status checklist: " . $update_status->error;
            $update_status->close();
            exit;
        }
        $update_status->close();

        echo "<script>alert('Checklist berhasil disimpan.'); window.location.href='transaksi.php';</script>";
        exit;
    } else {
        echo "<script>alert('Checklist tidak boleh kosong.');</script>";
    }
}

// Ambil kategori barang dari transaksi
$stmt_kategori = $koneksi->prepare("
    SELECT b.id_kategori 
    FROM detail_transaksi dt
    JOIN barang b ON dt.id_barang = b.id_barang
    WHERE dt.id_transaksi = ?
    LIMIT 1
");
$stmt_kategori->bind_param("i", $id_transaksi);
$stmt_kategori->execute();
$result_kategori = $stmt_kategori->get_result();

$id_kategori = 0;
if ($row = $result_kategori->fetch_assoc()) {
    $id_kategori = intval($row['id_kategori']);
}
$stmt_kategori->close();

if (!$id_kategori) {
    die("Kategori barang tidak ditemukan.");
}

// Ambil data kelengkapan berdasarkan kategori
$result_kelengkapan = mysqli_query($koneksi, "SELECT * FROM kelengkapan_barang WHERE id_kategori = $id_kategori");
if (!$result_kelengkapan) {
    die("Gagal mengambil data kelengkapan: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Checklist Pengambilan Barang</title>

    <!-- Link CSS yang kamu pakai, jangan dihapus -->
    <link rel="stylesheet" href="css/linearicons.css" />
    <link rel="stylesheet" href="css/owl.carousel.css" />
    <link rel="stylesheet" href="css/themify-icons.css" />
    <link rel="stylesheet" href="css/font-awesome.min.css" />
    <link rel="stylesheet" href="css/nice-select.css" />
    <link rel="stylesheet" href="css/nouislider.min.css" />
    <link rel="stylesheet" href="css/bootstrap.css" />
    <link rel="stylesheet" href="css/main.css" />
     <link rel="shortcut icon" href="../../assets/img/logo.jpg">
    <!-- CSS lain kalau ada -->
</head>

<body>

    <?php include("../layout/navbar1.php"); ?>

    <section class="banner-area organic-breadcrumb">
        <div class="container">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Subang Outdoor</h1>
                    <nav class="d-flex align-items-center">
                        <a href="#">Form Kondisi Awal Barang</a>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <div class="container mt-5">
        <h3>Checklist Pengambilan - Transaksi #<?= htmlspecialchars($id_transaksi) ?></h3>

        <?php if ($checklist_exists): ?>
            <div class="alert alert-success">Checklist sudah diisi. Terima kasih!</div>
        <?php else: ?>
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id_transaksi=' . $id_transaksi ?>">
                <input type="hidden" name="id_transaksi" value="<?= $id_transaksi ?>">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nama Kelengkapan</th>
                            <th>Status Awal</th>
                            <th>Keterangan (Opsional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_kelengkapan)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama_kelengkapan']) ?></td>
                                <td>
                                    <select name="status_awal[<?= $row['id_kelengkapan'] ?>]" class="form-control" required>
                                        <option value="">-- Pilih Status --</option>
                                        <option value="ada">Ada</option>
                                        <option value="tidak ada">Tidak Ada</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="keterangan[<?= $row['id_kelengkapan'] ?>]" class="form-control" placeholder="Contoh: sobek, rusak ringan...">
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <button type="submit" name="submit_checklist" class="btn btn-primary">Simpan Checklist</button>
            </form>
        <?php endif; ?>
    </div>

    <br>

    <?php include('../layout/footer.php'); ?>

    <!-- Script JS tetap utuh seperti biasa -->
    <script src="js/vendor/bootstrap.min.js"></script>
    <script src="js/jquery.ajaxchimp.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/nouislider.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <!--gmaps Js-->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjCGmQ0Uq4exrzdcL6rvxywDDOvfAu6eE"></script>
    <script src="js/gmaps.min.js"></script>
    <script src="js/main.js"></script>

</body>

</html>
