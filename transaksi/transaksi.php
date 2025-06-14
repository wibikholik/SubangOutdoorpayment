<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

// Update otomatis status terlambat dikembalikan
$today_str = date('Y-m-d');
$update_query = "
    UPDATE transaksi 
    SET status = 'terlambat dikembalikan' 
    WHERE status IN ('disewa', 'di ambil barang') 
      AND tanggal_kembali < '$today_str'
";
mysqli_query($koneksi, $update_query);

// Ambil semua transaksi lengkap dengan nama metode dan nama penyewa
$query_transaksi = "
    SELECT t.*, mp.nama_metode, p.nama_penyewa
    FROM transaksi t
    JOIN metode_pembayaran mp ON t.id_metode = mp.id_metode
    JOIN penyewa p ON t.id_penyewa = p.id_penyewa
    ORDER BY t.id_transaksi DESC
";
$result_transaksi = mysqli_query($koneksi, $query_transaksi);
if (!$result_transaksi) {
    die("Query gagal: " . mysqli_error($koneksi));
}

// Ambil semua detail transaksi sekaligus agar lebih efisien
$detail_query = "
    SELECT dt.id_transaksi, dt.jumlah_barang, b.nama_barang, dt.harga_satuan
    FROM detail_transaksi dt
    JOIN barang b ON dt.id_barang = b.id_barang
";
$result_detail = mysqli_query($koneksi, $detail_query);
$details_by_transaksi = [];
while ($row = mysqli_fetch_assoc($result_detail)) {
    $details_by_transaksi[$row['id_transaksi']][] = $row;
}

// Fungsi untuk badge bootstrap berdasarkan status transaksi
function getBadgeClass($status) {
    $status = strtolower(trim($status));
    switch ($status) {
        case 'selesai dikembalikan':
        case 'transaksi selesai':
            return 'success';
        case 'disewa':
            return 'info';
        case 'di ambil barang':
            return 'warning';
        case 'terlambat dikembalikan':
        case 'ditolak pengembalian':
            return 'danger';
        case 'menunggu konfirmasi pembayaran':
        case 'menunggu konfirmasi pengembalian':
            return 'warning';
        case 'dikonfirmasi pembayaran silahkan ambilbarang':
            return 'primary';
        case 'batal':
            return 'secondary';
        default:
            error_log("Status tidak dikenal: $status"); // catat di log server
            return 'secondary';
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Subang Outdoor - Data Transaksi</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
<div id="wrapper">

    <?php include '../layout/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include '../layout/navbar.php'; ?>

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Data Transaksi</h1>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">transaksi berhasil diperbarui.</div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">Terjadi kesalahan saat memperbarui status.</div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID Transaksi</th>
                                        <th>Nama Penyewa</th>
                                        <th>Detail Barang</th>
                                        <th>Total Harga</th>
                                        <th>Status</th>
                                        <th>Tanggal Sewa</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Metode Pembayaran</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($transaksi = mysqli_fetch_assoc($result_transaksi)) : ?>
                                        <?php
                                        $id_transaksi = $transaksi['id_transaksi'];
                                        $status = strtolower(trim($transaksi['status']));
                                        $badge_class = getBadgeClass($status);
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($transaksi['id_transaksi']); ?></td>
                                            <td><?= htmlspecialchars($transaksi['nama_penyewa']); ?></td>
                                            <td>
                                                <ul class="mb-0">
                                                    <?php 
                                                    if (isset($details_by_transaksi[$id_transaksi])): 
                                                        foreach ($details_by_transaksi[$id_transaksi] as $detail): ?>
                                                        <li>
                                                            <?= htmlspecialchars($detail['nama_barang']); ?> 
                                                            (x<?= htmlspecialchars($detail['jumlah_barang']); ?>) 
                                                            - Rp <?= number_format($detail['harga_satuan'], 0, ',', '.'); ?>
                                                        </li>
                                                    <?php 
                                                        endforeach; 
                                                    endif; 
                                                    ?>
                                                </ul>
                                            </td>
                                            <td>Rp <?= number_format($transaksi['total_harga_sewa'], 0, ',', '.'); ?></td>
                                            <td><span class="badge badge-<?= $badge_class; ?>"><?= ucfirst($transaksi['status']); ?></span></td>
                                            <td><?= htmlspecialchars($transaksi['tanggal_sewa']); ?></td>
                                            <td><?= htmlspecialchars($transaksi['tanggal_kembali']); ?></td>
                                            <td><?= htmlspecialchars($transaksi['nama_metode']); ?></td>
                                           <td>
                                                <form method="POST" action="update_status.php" class="mb-2">
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($transaksi['id_transaksi']); ?>">
                                                    <input type="hidden" name="target_table" value="transaksi">
                                                    <select name="status_baru" onchange="this.form.submit()" class="form-control form-control-sm">
                                                        <?php
                                                        $status_options = [
                                                            'menunggu konfirmasi pembayaran' => 'Menunggu Konfirmasi Pembayaran',
                                                            'Dikonfirmasi Pembayaran Silahkan AmbilBarang' => 'Dikonfirmasi (Silahkan Ambil Barang)',
                                                             'ditolak pembayaran' => 'Ditolak Pembayaran',
                                                            'disewa' => 'Disewa',
                                                            'terlambat dikembalikan' => 'Terlambat Dikembalikan',
                                                            'menunggu konfirmasi pengembalian' => 'Menunggu Konfirmasi Pengembalian',
                                                            'ditolak pengembalian' => 'Ditolak Pengembalian',
                                                            'selesai dikembalikan' => 'Selesai Dikembalikan',
                                                            'batal' => 'Batal'
                                                        ];
                                                        foreach ($status_options as $key => $label) {
                                                            $selected = ($status === strtolower($key)) ? 'selected' : '';
                                                            echo "<option value=\"$key\" $selected>$label</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </form>

                                                <form method="POST" action="hapus.php" onsubmit="return confirm('Yakin ingin menghapus transaksi ini?')">
                                                    <input type="hidden" name="id_transaksi" value="<?= htmlspecialchars($transaksi['id_transaksi']); ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm w-100">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
