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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Data Pengembalian - Admin</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,400,700" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                        <?= $message ?>
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
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?= $row['id_pengembalian'] ?></td>
                                            <td><?= htmlspecialchars($row['nama_penyewa']) ?></td>
                                            <td><?= date('d-m-Y', strtotime($row['tanggal_sewa'])) ?></td>
                                            <td><?= date('d-m-Y', strtotime($row['tanggal_kembali'])) ?></td>
                                            <td><?= date('d-m-Y H:i', strtotime($row['tanggal_pengembalian'])) ?></td>
                                            <td><span class="badge badge-<?= $row['status_pengembalian'] === 'Selesai' ? 'success' : 'warning' ?>"><?= $row['status_pengembalian'] ?? '-' ?></span></td>
                                            <td>Rp<?= number_format($row['denda'] ?? 0, 0, ',', '.') ?></td>
                                            <td>
                                              <a href="detail_pengembalian.php?id_pengembalian=<?= $row['id_pengembalian'] ?>" class="btn btn-info btn-sm">Detail</a>

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
    });
</script>
</body>
</html>
