<?php
session_start();

// Batasi akses hanya untuk admin dan owner
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

include '../route/koneksi.php';

// Handle pesan notifikasi
$message = '';
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] == "input") {
        $message = "✅ Data berhasil ditambahkan.";
    } elseif ($_GET['pesan'] == "hapus") {
        $message = "✅ Data berhasil dihapus.";
    } elseif ($_GET['pesan'] == "update") {
        $message = "✅ Data berhasil diupdate.";
    }
}

// Ambil data metode pembayaran beserta tipe metode dengan JOIN
$query = "SELECT mp.*, tm.nama_tipe AS tipe_metode 
          FROM metode_pembayaran mp
          LEFT JOIN tipe_metode tm ON mp.id_tipe = tm.id_tipe
          ORDER BY mp.id_metode DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subang Outdoor - Metode Pembayaran</title>

    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-0 text-gray-800">Data Metode Pembayaran</h1>
                    </div>

                    <?php if ($message != ''): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <a class="btn btn-primary" href="tambah_metode.php" role="button">
                                <i class="fas fa-plus"></i> Tambah Metode
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                   <thead class="thead-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Metode</th>
                                            <th>Nomor Rekening</th>
                                            <th>Gambar Metode</th>
                                            <th>Atas Nama</th>
                                            <th>Tipe Metode</th> <!-- Tambahan kolom tipe metode -->
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                                <tr>
                                                    <td><?= $row['id_metode'] ?></td>
                                                    <td><?= htmlspecialchars($row['nama_metode']) ?></td>
                                                    <td><?= htmlspecialchars($row['nomor_rekening']) ?></td>
                                                    <td>
                                                        <?php if (!empty($row['gambar_metode'])): ?>
                                                            <img src="metode/gambar/<?= htmlspecialchars($row['gambar_metode']) ?>" width="100" alt="gambar_metode">
                                                        <?php else: ?>
                                                            <em>tidak ada</em>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['atas_nama']) ?></td>
                                                    <td><?= htmlspecialchars($row['tipe_metode'] ?? 'Tidak diketahui') ?></td> <!-- Tampilkan tipe_metode -->
                                                    <td>
                                                        <a class="btn btn-warning btn-sm" href="edit.php?id_metode=<?= $row['id_metode'] ?>" data-toggle="tooltip" title="Edit">
                                                            <i class="fas fa-pencil-alt"></i>
                                                        </a>
                                                        <a class="btn btn-danger btn-sm" href="hapus.php?id_metode=<?= $row['id_metode'] ?>" onclick="return confirm('Yakin ingin menghapus data ini?')" data-toggle="tooltip" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Belum ada data metode pembayaran.</td>
                                            </tr>
                                        <?php endif; ?>
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

            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>
