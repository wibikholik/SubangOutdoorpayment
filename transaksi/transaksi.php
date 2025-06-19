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

// Ambil semua transaksi lengkap
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

// Ambil semua detail transaksi
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

// Fungsi badge warna berdasarkan status
function getBadgeClass($status) {
    $status = strtolower(trim($status));
    return match ($status) {
        'selesai dikembalikan', 'transaksi selesai' => 'success',
        'disewa' => 'info',
        'di ambil barang' => 'warning',
        'terlambat dikembalikan', 'ditolak pengembalian' => 'danger',
        'menunggu konfirmasi pembayaran', 'menunggu konfirmasi pengembalian' => 'warning',
        'dikonfirmasi pembayaran silahkan ambilbarang' => 'primary',
        'batal' => 'secondary',
        default => 'secondary',
    };
}

// Daftar status yang boleh diubah
$allowed_status_updates = [
    'menunggu konfirmasi pesanan' => 'Menunggu Konfirmasi Pesanan',
    'menunggu konfirmasi pembayaran' => 'Menunggu Konfirmasi Pembayaran',
    'dikonfirmasi pembayaran silahkan ambilbarang' => 'Dikonfirmasi (Silahkan Ambil Barang)',
    'ditolak pembayaran' => 'Ditolak Pembayaran',
    'disewa' => 'Disewa',
    'terlambat dikembalikan' => 'Terlambat Dikembalikan',
    'selesai dikembalikan' => 'Selesai Dikembalikan'
];

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Subang Outdoor - Data Transaksi</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
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

                <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                    <div class="alert alert-success">Transaksi berhasil diperbarui.</div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                                            <div class="card-header py-3">
                            <a class="btn btn-primary" href="tambah_transaksi.php" role="button">
                                <i class="fas fa-plus"></i> Tambah Transaksi
                            </a>
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
                                <?php while ($transaksi = mysqli_fetch_assoc($result_transaksi)): ?>
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
                                                    <?php endforeach;
                                                endif; ?>
                                            </ul>
                                        </td>
                                        <td>Rp <?= number_format($transaksi['total_harga_sewa'], 0, ',', '.'); ?></td>
                                        <td><span class="badge badge-<?= $badge_class; ?>"><?= ucfirst($transaksi['status']); ?></span></td>
                                        <td><?= htmlspecialchars($transaksi['tanggal_sewa']); ?></td>
                                        <td><?= htmlspecialchars($transaksi['tanggal_kembali']); ?></td>
                                        <td><?= htmlspecialchars($transaksi['nama_metode']); ?></td>
                                        <td>
                                             
    
                                            <?php if (array_key_exists($status, array_change_key_case($allowed_status_updates, CASE_LOWER))): ?>
                                                <form method="POST" action="update_status.php" class="mb-2">
                                                    <input type="hidden" name="id" value="<?= $transaksi['id_transaksi']; ?>">
                                                    <input type="hidden" name="target_table" value="transaksi">
                                                    <select name="status_baru" onchange="this.form.submit()" class="form-control form-control-sm">
                                                        <?php foreach ($allowed_status_updates as $key => $label):
                                                            $selected = ($status === strtolower($key)) ? 'selected' : '';
                                                            echo "<option value=\"$key\" $selected>$label</option>";
                                                        endforeach; ?>
                                                    </select>
                                                </form>
                                            <?php else: ?>
                                                <small class="text-muted">Status tidak dapat diubah</small>
                                            <?php endif; ?>
                                            <?php if ($status === 'dikonfirmasi pembayaran silahkan ambilbarang' || $status === 'dikonfirmasi (silahkan ambil barang)'): ?>
        <a href="tambah_cheklistawal.php?id_transaksi=<?= $transaksi['id_transaksi']; ?>" 
           class="btn btn-success btn-sm mb-2 w-100" title="Checklist Kondisi Awal">
           <i class="fas fa-clipboard-check"></i> Checklist Awal
        </a>
    <?php endif; ?>
 <?php if (in_array($status, ['disewa', 'terlambat dikembalikan'])): ?>
        <a href="pengembalian.php?id_transaksi=<?= $transaksi['id_transaksi']; ?>" class="btn btn-warning btn-sm mb-1 w-100">
            <i class="fas fa-undo"></i> Pengembalian
        </a>
    <?php endif; ?>
                                            <form method="POST" action="hapus.php" onsubmit="return confirm('Yakin ingin menghapus transaksi ini?')">
                                                <input type="hidden" name="id_transaksi" value="<?= $transaksi['id_transaksi']; ?>">
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
