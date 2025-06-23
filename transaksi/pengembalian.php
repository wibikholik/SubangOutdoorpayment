<?php
session_start();
include '../route/koneksi.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    header('Location: ../login.php');
    exit;
}

$id_transaksi = isset($_GET['id_transaksi']) ? intval($_GET['id_transaksi']) : 0;
if (!$id_transaksi) {
    die("ID Transaksi tidak ditemukan.");
}

// Ambil kategori barang
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

// Ambil checklist kondisi awal berdasarkan id_transaksi
$checklist_awal = [];
$q_check_awal = $koneksi->prepare("SELECT id_kelengkapan, status_awal, keterangan_awal FROM checklist WHERE id_transaksi = ?");
$q_check_awal->bind_param("i", $id_transaksi);
$q_check_awal->execute();
$res_check_awal = $q_check_awal->get_result();
while ($row = $res_check_awal->fetch_assoc()) {
    $checklist_awal[$row['id_kelengkapan']] = [
        'status_awal' => $row['status_awal'],
        'keterangan_awal' => $row['keterangan_awal']
    ];
}
$q_check_awal->close();

// Cek apakah checklist kondisi akhir sudah diisi, supaya gak dobel input
$stmt_check = $koneksi->prepare("SELECT COUNT(*) AS jumlah_akhir FROM checklist WHERE id_transaksi = ? AND status_akhir IS NOT NULL");
$stmt_check->bind_param("i", $id_transaksi);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
$checklist_akhir_exists = false;
if ($row_check = $res_check->fetch_assoc()) {
    $checklist_akhir_exists = ($row_check['jumlah_akhir'] > 0);
}
$stmt_check->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pengembalian']) && !$checklist_akhir_exists) {
    $denda = intval($_POST['denda'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');
    $status_denda = $_POST['status_denda'] ?? '';

    if (!in_array($status_denda, ['belum', 'sudah'])) {
        die("Status pembayaran denda tidak valid.");
    }

    // Upload bukti pengembalian wajib
    $bukti_pengembalian = null;
    if (isset($_FILES['bukti_pengembalian']) && $_FILES['bukti_pengembalian']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['bukti_pengembalian']['tmp_name'];
        $name = basename($_FILES['bukti_pengembalian']['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($ext, $allowed_ext)) {
            die("Format file bukti pengembalian tidak diperbolehkan. Hanya jpg, jpeg, png, pdf.");
        }

        $new_name = 'bukti_pengembalian_' . $id_transaksi . '_' . time() . '.' . $ext;
        $upload_dir = __DIR__ . '/../uploads/bukti_pengembalian/';

        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $upload_path = $upload_dir . $new_name;

        if (!move_uploaded_file($tmp_name, $upload_path)) {
            die("Gagal mengunggah file bukti pengembalian.");
        }
        $bukti_pengembalian = $new_name;
    } else {
        die("Bukti pengembalian wajib diunggah.");
    }

    $status_akhir_arr = $_POST['status_akhir'] ?? [];
    $keterangan_akhir_arr = $_POST['keterangan_akhir'] ?? [];

    foreach ($status_akhir_arr as $id_kelengkapan_post => $status_akhir_val) {
        if (!in_array($status_akhir_val, ['ada', 'tidak ada'])) {
            die("Status akhir untuk kelengkapan tidak valid.");
        }
    }

    // Tentukan status transaksi & status pengembalian
    $status_transaksi_baru = ($status_denda === 'sudah') ? 'Selesai Dikembalikan' : 'Menunggu Konfirmasi Pengembalian';

    $koneksi->begin_transaction();
    try {
        // Update checklist kondisi akhir
        foreach ($status_akhir_arr as $id_kelengkapan_post => $status_akhir_val) {
            $id_kelengkapan_post = intval($id_kelengkapan_post);
            $keterangan_akhir_val = trim($keterangan_akhir_arr[$id_kelengkapan_post] ?? '');

            $stmt_upd = $koneksi->prepare("UPDATE checklist SET status_akhir = ?, keterangan_akhir = ?, updated_at = NOW() WHERE id_transaksi = ? AND id_kelengkapan = ?");
            $stmt_upd->bind_param("ssii", $status_akhir_val, $keterangan_akhir_val, $id_transaksi, $id_kelengkapan_post);
            $stmt_upd->execute();
            $stmt_upd->close();
        }

        // Insert ke tabel pengembalian (status_pengembalian diganti)
        $stmt = $koneksi->prepare("INSERT INTO pengembalian (id_transaksi, tanggal_pengembalian, bukti_pengembalian, denda, catatan, status_pengembalian) VALUES (?, NOW(), ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $id_transaksi, $bukti_pengembalian, $denda, $catatan, $status_transaksi_baru);
        $stmt->execute();
        $stmt->close();

        // Update status transaksi
        $stmt_upd_trans = $koneksi->prepare("UPDATE transaksi SET status = ? WHERE id_transaksi = ?");
        $stmt_upd_trans->bind_param("si", $status_transaksi_baru, $id_transaksi);
        $stmt_upd_trans->execute();
        $stmt_upd_trans->close();

        // Jika status Selesai Dikembalikan, kembalikan stok barang
        if ($status_transaksi_baru === 'Selesai Dikembalikan') {
            $stmt_detail = $koneksi->prepare("SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?");
            $stmt_detail->bind_param("i", $id_transaksi);
            $stmt_detail->execute();
            $result = $stmt_detail->get_result();

            while ($row = $result->fetch_assoc()) {
                $id_barang = $row['id_barang'];
                $jumlah_dikembalikan = $row['jumlah_barang'];

                $stmt_update_stok = $koneksi->prepare("UPDATE barang SET stok = stok + ? WHERE id_barang = ?");
                $stmt_update_stok->bind_param("ii", $jumlah_dikembalikan, $id_barang);
                $stmt_update_stok->execute();
                $stmt_update_stok->close();
            }

            $stmt_detail->close();
        }

        $koneksi->commit();
        echo "<script>alert('Pengembalian berhasil disimpan.'); window.location.href='transaksi.php';</script>";
        exit;
    } catch (Exception $e) {
        $koneksi->rollback();
        die("Gagal menyimpan pengembalian: " . $e->getMessage());
    }
}

    
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Pengembalian Barang - Transaksi #<?= htmlspecialchars($id_transaksi) ?></title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet" />
    <style>
        .table-perbandingan td, .table-perbandingan th {
            vertical-align: middle;
            text-align: center;
        }
    </style>
</head>
<body id="page-top">

<div id="wrapper">

    <?php include '../layout/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include '../layout/navbar.php'; ?>

            <div class="container-fluid">

                <h1 class="h3 mb-4 text-gray-800">Form Pengembalian Barang - Transaksi #<?= htmlspecialchars($id_transaksi) ?></h1>

                <?php if ($checklist_akhir_exists): ?>
                    <div class="alert alert-success">Checklist kondisi akhir sudah diisi.</div>
                <?php else: ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data">

                                <table class="table table-bordered table-perbandingan">
                                    <thead>
                                        <tr>
                                            <th>Nama Kelengkapan</th>
                                            <th>Status Awal</th>
                                            <th>Keterangan Awal</th>
                                            <th>Status Akhir (Input)</th>
                                            <th>Keterangan Akhir (Input)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result_kelengkapan)): ?>
                                            <?php 
                                                $id_kel = $row['id_kelengkapan'];
                                                $status_awal = $checklist_awal[$id_kel]['status_awal'] ?? '-';
                                                $ket_awal = $checklist_awal[$id_kel]['keterangan_awal'] ?? '-';
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['nama_kelengkapan']) ?></td>
                                                <td><?= htmlspecialchars($status_awal) ?></td>
                                                <td><?= htmlspecialchars($ket_awal) ?></td>
                                                <td>
                                                    <select name="status_akhir[<?= $id_kel ?>]" class="form-control" required>
                                                        <option value="">-- Pilih Status --</option>
                                                        <option value="ada">Ada</option>
                                                        <option value="tidak ada">Tidak Ada</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="keterangan_akhir[<?= $id_kel ?>]" class="form-control" placeholder="Contoh: sobek, rusak ringan..." />
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>

                                <div class="form-group">
                                    <label for="denda">Denda (Rp):</label>
                                    <input type="number" name="denda" id="denda" class="form-control" value="0" min="0" required />
                                </div>

                                <div class="form-group">
                                    <label for="status_denda">Status Pembayaran Denda:</label>
                                    <select class="form-control" id="status_denda" name="status_denda" required>
                                        <option value="">-- Pilih Status --</option>
                                        <option value="belum">Belum Dibayar</option>
                                        <option value="sudah">Sudah Dibayar</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="bukti_pengembalian">Upload Bukti Pengembalian (jpg, png, pdf):</label>
                                    <input type="file" name="bukti_pengembalian" id="bukti_pengembalian" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf" required />
                                </div>

                                <div class="form-group">
                                    <label for="catatan">Catatan (Opsional):</label>
                                    <textarea name="catatan" id="catatan" class="form-control"></textarea>
                                </div>

                                <button type="submit" name="submit_pengembalian" class="btn btn-primary">Simpan Pengembalian</button>
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
