<?php
session_start();
include '../route/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

$username = $_SESSION['username'];

$query = "SELECT pengembalian.*, penyewa.nama_penyewa, transaksi.tanggal_sewa, transaksi.tanggal_kembali 
          FROM pengembalian
          LEFT JOIN transaksi ON pengembalian.id_transaksi = transaksi.id_transaksi
          LEFT JOIN penyewa ON transaksi.id_penyewa = penyewa.id_penyewa
          ORDER BY pengembalian.id_pengembalian DESC";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Query error: " . mysqli_error($koneksi));
}

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == "sukses") {
        $message = "Pengembalian berhasil dikonfirmasi.";
    } elseif ($_GET['msg'] == "gagal") {
        $message = "Gagal memproses pengembalian.";
    }
}

function getBadgeClass($status) {
    switch (strtolower($status)) {
        case 'selesai':
        case 'lunas':
            return 'success';
        case 'menunggu konfirmasi pembayaran':
        case 'menunggu konfirmasi pembayaran denda':
        case 'menunggu konfirmasi pengembalian':
            return 'warning';
        case 'ditolak pembayaran':
        case 'ditolak pengembalian':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Pengembalian - Admin</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,400,700" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../../assets/img/logo.jpg">
</head>
<body id="page-top">
<div id="wrapper">
    <?php include '../layout/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include '../layout/navbar.php'; ?>

            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Data Pengembalian</h1>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama Penyewa</th>
                                        <th>Tgl Sewa</th>
                                        <th>Tgl Kembali</th>
                                        <th>Tgl Pengembalian</th>
                                        <th>Status</th>
                                        <th>Denda</th>
                                        <th>Bukti Pembayaran</th>
                                        <th>Bukti Pengembalian</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?= $row['id_pengembalian'] ?></td>
                                            <td><?= htmlspecialchars($row['nama_penyewa']) ?></td>
                                            <td><?= !empty($row['tanggal_sewa']) ? date('d-m-Y', strtotime($row['tanggal_sewa'])) : '-' ?></td>
                                            <td><?= !empty($row['tanggal_kembali']) ? date('d-m-Y', strtotime($row['tanggal_kembali'])) : '-' ?></td>
                                            <td><?= !empty($row['tanggal_pengembalian']) ? date('d-m-Y H:i', strtotime($row['tanggal_pengembalian'])) : '-' ?></td>
                                            <td>
                                                <span class="badge badge-<?= getBadgeClass($row['status_pengembalian']) ?>">
                                                    <?= htmlspecialchars($row['status_pengembalian'] ?? '-') ?>
                                                </span>
                                            </td>
                                            <td>Rp<?= number_format($row['denda'] ?? 0, 0, ',', '.') ?></td>
                                            <td>
                                                <?php if (!empty($row['bukti_pembayaran'])): ?>
                                                    <button class="btn btn-sm btn-primary btn-bukti-pembayaran" 
                                                        data-toggle="modal" 
                                                        data-target="#modalBuktiPembayaran" 
                                                        data-bukti="<?= htmlspecialchars($row['bukti_pembayaran']) ?>">
                                                        <i class="fas fa-file-image"></i> Lihat Bukti
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['bukti_pengembalian'])): ?>
                                                    <button class="btn btn-sm btn-primary btn-bukti-pengembalian" 
                                                        data-toggle="modal" 
                                                        data-target="#modalBuktiPengembalian" 
                                                        data-bukti="<?= htmlspecialchars($row['bukti_pengembalian']) ?>">
                                                        <i class="fas fa-file-image"></i> Lihat Bukti
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="detail_pengembalian.php?id_pengembalian=<?= $row['id_pengembalian'] ?>" class="btn btn-info btn-sm">Detail</a>
                                                <a href="delete_pengembalian.php?id_pengembalian=<?= $row['id_pengembalian'] ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('Yakin ingin menghapus data ini?');">
                                                    Hapus
                                                </a>
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

<!-- Modal Bukti Pembayaran -->
<div class="modal fade" id="modalBuktiPembayaran" tabindex="-1" role="dialog" aria-labelledby="modalBuktiPembayaranLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:600px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalBuktiPembayaranLabel">Bukti Pembayaran</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center" id="modalBuktiPembayaranBody" style="min-height:200px;">
        <!-- Gambar bukti pembayaran akan dimasukkan di sini -->
      </div>
    </div>
  </div>
</div>

<!-- Modal Bukti Pengembalian -->
<div class="modal fade" id="modalBuktiPengembalian" tabindex="-1" role="dialog" aria-labelledby="modalBuktiPengembalianLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:600px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalBuktiPengembalianLabel">Bukti Pengembalian</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center" id="modalBuktiPengembalianBody" style="min-height:200px;">
        <!-- Gambar bukti pengembalian akan dimasukkan di sini -->
      </div>
    </div>
  </div>
</div>

<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>
<script src="../assets/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "order": [[0, "desc"]]
        });

        // Modal Bukti Pembayaran
        $('.btn-bukti-pembayaran').on('click', function() {
            const bukti = $(this).data('bukti');
            const modalBody = $('#modalBuktiPembayaranBody');
            const ext = bukti.split('.').pop().toLowerCase();

            modalBody.html('');
            if (['jpg','jpeg','png','gif'].includes(ext)) {
                modalBody.html(`<img src='../uploads/bukti_pembayaran/${bukti}' alt='Bukti Pembayaran' style='max-width:100%; height:auto; border:1px solid #ddd; padding:5px;'>`);
            } else if (ext === 'pdf') {
                modalBody.html(`<embed src='../uploads/bukti_pembayaran/${bukti}' type='application/pdf' width='100%' height='400px' />`);
            } else {
                modalBody.text('Tidak dapat menampilkan file ini.');
            }
        });

        // Modal Bukti Pengembalian
        $('.btn-bukti-pengembalian').on('click', function() {
            const bukti = $(this).data('bukti');
            const modalBody = $('#modalBuktiPengembalianBody');
            const ext = bukti.split('.').pop().toLowerCase();

            modalBody.html('');
            if (['jpg','jpeg','png','gif'].includes(ext)) {
                modalBody.html(`<img src='../uploads/bukti_pengembalian/${bukti}' alt='Bukti Pengembalian' style='max-width:100%; height:auto; border:1px solid #ddd; padding:5px;'>`);
            } else if (ext === 'pdf') {
                modalBody.html(`<embed src='../uploads/bukti_pengembalian/${bukti}' type='application/pdf' width='100%' height='400px' />`);
            } else {
                modalBody.text('Tidak dapat menampilkan file ini.');
            }
        });
    });
</script>
</body>
</html>
