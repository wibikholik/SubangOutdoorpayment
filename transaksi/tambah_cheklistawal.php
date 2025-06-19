<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

$id_transaksi = isset($_GET['id_transaksi']) ? intval($_GET['id_transaksi']) : 0;
if (!$id_transaksi) {
    die("ID Transaksi tidak ditemukan.");
}

// Cek status transaksi, harus "dikonfirmasi pembayaran silahkan ambilbarang"
$stmt = $koneksi->prepare("SELECT status FROM transaksi WHERE id_transaksi = ? LIMIT 1");
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Transaksi tidak ditemukan.");
}
$data_trans = $res->fetch_assoc();
$stmt->close();

if (strtolower(trim($data_trans['status'])) !== 'dikonfirmasi pembayaran silahkan ambilbarang') {
    die("Transaksi tidak dalam status yang valid untuk checklist.");
}

// Cek apakah checklist sudah ada
$stmt_check = $koneksi->prepare("SELECT COUNT(*) AS jumlah FROM checklist WHERE id_transaksi = ?");
$stmt_check->bind_param("i", $id_transaksi);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
$checklist_exists = false;
if ($row_check = $res_check->fetch_assoc()) {
    $checklist_exists = ($row_check['jumlah'] > 0);
}
$stmt_check->close();

// Ambil kategori barang dari transaksi (ambil 1 kategori barang dari detail transaksi)
$stmt_kat = $koneksi->prepare("
    SELECT b.id_kategori 
    FROM detail_transaksi dt
    JOIN barang b ON dt.id_barang = b.id_barang
    WHERE dt.id_transaksi = ?
    LIMIT 1
");
$stmt_kat->bind_param("i", $id_transaksi);
$stmt_kat->execute();
$res_kat = $stmt_kat->get_result();
if ($res_kat->num_rows === 0) {
    die("Kategori barang tidak ditemukan.");
}
$data_kat = $res_kat->fetch_assoc();
$id_kategori = intval($data_kat['id_kategori']);
$stmt_kat->close();

// Ambil kelengkapan barang sesuai kategori
$result_kelengkapan = mysqli_query($koneksi, "SELECT * FROM kelengkapan_barang WHERE id_kategori = $id_kategori ORDER BY nama_kelengkapan");
if (!$result_kelengkapan) {
    die("Gagal mengambil data kelengkapan: " . mysqli_error($koneksi));
}

// Ambil semua nama barang dari transaksi (untuk tampil di checklist)
$stmt_barang = $koneksi->prepare("
    SELECT b.id_barang, b.nama_barang, b.id_kategori 
    FROM detail_transaksi dt
    JOIN barang b ON dt.id_barang = b.id_barang
    WHERE dt.id_transaksi = ?
");
$stmt_barang->bind_param("i", $id_transaksi);
$stmt_barang->execute();
$result_barang = $stmt_barang->get_result();

$barang_per_kategori = [];
while ($row_barang = $result_barang->fetch_assoc()) {
    $barang_per_kategori[$row_barang['id_kategori']][] = $row_barang['nama_barang'];
}
$stmt_barang->close();

// Proses simpan checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_checklist']) && !$checklist_exists) {
    if (!empty($_POST['status_awal']) && is_array($_POST['status_awal'])) {
        foreach ($_POST['status_awal'] as $id_kelengkapan_post => $status_awal) {
            $id_kelengkapan_post = intval($id_kelengkapan_post);
            $keterangan = trim($_POST['keterangan'][$id_kelengkapan_post] ?? '');

            $stmt_ins = $koneksi->prepare("INSERT INTO checklist (id_transaksi, id_kelengkapan, status_awal, keterangan_awal, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt_ins->bind_param("iiss", $id_transaksi, $id_kelengkapan_post, $status_awal, $keterangan);
            if (!$stmt_ins->execute()) {
                die("Error menyimpan checklist: " . $stmt_ins->error);
            }
            $stmt_ins->close();
        }

        // Update status checklist pada transaksi (opsional, jika ada kolomnya)
        $stmt_upd = $koneksi->prepare("UPDATE transaksi SET status_checklist = 1 WHERE id_transaksi = ?");
        $stmt_upd->bind_param("i", $id_transaksi);
        $stmt_upd->execute();
        $stmt_upd->close();

        echo "<script>alert('Checklist kondisi awal berhasil disimpan.'); window.location.href='transaksi.php';</script>";
        exit;
    } else {
        echo "<script>alert('Checklist tidak boleh kosong.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Checklist Kondisi Awal - Admin</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet" />
</head>
<body id="page-top">

<div id="wrapper">

    <?php include '../layout/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include '../layout/navbar.php'; ?>

            <div class="container-fluid">

                <h1 class="h3 mb-4 text-gray-800">Checklist Kondisi Awal Barang - Transaksi #<?= htmlspecialchars($id_transaksi) ?></h1>

                <?php if ($checklist_exists): ?>
                    <div class="alert alert-success">Checklist sudah diisi. Terima kasih!</div>
                <?php else: ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="id_transaksi" value="<?= $id_transaksi ?>">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th>Nama Kelengkapan</th>
                                            <th>Status Awal</th>
                                            <th>Keterangan (Opsional)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result_kelengkapan)): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                        $id_kat = $row['id_kategori'];
                                                        if (isset($barang_per_kategori[$id_kat])) {
                                                            echo htmlspecialchars(implode(', ', $barang_per_kategori[$id_kat]));
                                                        } else {
                                                            echo "-";
                                                        }
                                                    ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['nama_kelengkapan']) ?></td>
                                                <td>
                                                    <select name="status_awal[<?= $row['id_kelengkapan'] ?>]" class="form-control" required>
                                                        <option value="">-- Pilih Status --</option>
                                                        <option value="ada">Ada</option>
                                                        <option value="tidak ada">Tidak Ada</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="keterangan[<?= $row['id_kelengkapan'] ?>]" class="form-control" placeholder="Contoh: sobek, rusak ringan..." />
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                                <button type="submit" name="submit_checklist" class="btn btn-primary">Simpan Checklist</button>
                                <a href="transaksi.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>

</div>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>

</body>
</html>
