<?php
session_start();

// Cek apakah pengguna sudah login dan memiliki peran admin/owner
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

$query = "
SELECT 
    b.id_pembayaran,
    t.id_transaksi,
    p.nama_penyewa,
    t.tanggal_sewa,
    t.total_harga_sewa,
    b.bukti_pembayaran,
    b.status_pembayaran,
    b.tanggal_pembayaran
FROM pembayaran b
JOIN transaksi t ON b.id_transaksi = t.id_transaksi
JOIN penyewa p ON t.id_penyewa = p.id_penyewa
ORDER BY b.id_pembayaran DESC
";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Query pembayaran gagal: " . mysqli_error($koneksi));
}

$pembayaranList = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pembayaranList[] = $row;
}

// Menangani pesan notifikasi jika ada
$notification_message = '';
if (isset($_SESSION['notification'])) {
    $notification_message = $_SESSION['notification'];
    unset($_SESSION['notification']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Daftar Pembayaran - Subang Outdoor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css" />
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet" />
    <link href="../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">

    <?php include('../layout/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include('../layout/navbar.php'); ?>

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Daftar Pembayaran</h1>

                <?php if ($notification_message): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($notification_message) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID Bayar</th>
                                        <th>ID Transaksi</th>
                                        <th>Nama Penyewa</th>
                                        <th>Tanggal Sewa</th>
                                        <th>Total</th>
                                        <th>Bukti</th>
                                        <th>Status</th>
                                        <th style="width: 120px;">Aksi</th> </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pembayaranList as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id_pembayaran']) ?></td>
                                        <td><?= htmlspecialchars($row['id_transaksi']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_penyewa']) ?></td>
                                        <td><?= date('d M Y', strtotime($row['tanggal_sewa'])) ?></td>
                                        <td>Rp <?= number_format($row['total_harga_sewa'], 0, ',', '.') ?></td>
                                        <td>
                                            <?php if ($row['bukti_pembayaran']): ?>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-toggle="modal" 
                                                    data-target="#modalBukti"
                                                    data-img="../uploads/bukti/<?= htmlspecialchars($row['bukti_pembayaran']) ?>"
                                                    data-toggle="tooltip" title="Lihat Bukti">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                                $status = strtolower($row['status_pembayaran']);
                                                $badge_class = 'secondary';
                                                switch ($status) {
                                                    case 'menunggu konfirmasi pembayaran': $badge_class = 'warning'; break;
                                                    case 'dikonfirmasi pembayaran silahkan ambilbarang': $badge_class = 'info'; break;
                                                    case 'ditolak pembayaran': $badge_class = 'danger'; break;
                                                    case 'selesai': $badge_class = 'success'; break;
                                                    case 'batal': $badge_class = 'dark'; break;
                                                }
                                            ?>
                                            <span class="badge badge-<?= $badge_class ?>">
                                                <?= ucwords(str_replace('_', ' ', $status)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (strtolower($row['status_pembayaran']) == 'menunggu konfirmasi pembayaran'): ?>
                                                <a href="update_status.php?id=<?= $row['id_pembayaran'] ?>&status=dikonfirmasi" class="btn btn-sm btn-success" data-toggle="tooltip" title="Konfirmasi Pembayaran">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="update_status.php?id=<?= $row['id_pembayaran'] ?>&status=ditolak" class="btn btn-sm btn-warning" data-toggle="tooltip" title="Tolak Pembayaran">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="hapus_pembayaran.php?id=<?= $row['id_pembayaran'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data pembayaran ini secara permanen?')" data-toggle="tooltip" title="Hapus Permanen">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalBukti" tabindex="-1" aria-labelledby="modalBuktiLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalBuktiLabel">Bukti Pembayaran</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body text-center">
                                <img src="" id="imgBukti" class="img-fluid" alt="Bukti Pembayaran" />
                            </div>
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
<script src="../assets/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function(){
    // Inisialisasi DataTable
    $('#dataTable').DataTable({
        "order": [[0, "desc"]],
        "columnDefs": [
            { "orderable": false, "targets": [5, 7] } // Kolom "Bukti" dan "Aksi" tidak bisa diurutkan
        ]
    });

    // Inisialisasi Tooltip
    $('[data-toggle="tooltip"]').tooltip();

    // Fungsi untuk menampilkan gambar bukti di modal
    $('#modalBukti').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var imgSrc = button.data('img');
        var modal = $(this);
        modal.find('.modal-body #imgBukti').attr('src', imgSrc);
    });
});
</script>

</body>
</html>