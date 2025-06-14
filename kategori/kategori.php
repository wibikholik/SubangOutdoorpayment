<?php
session_start();
include '../route/koneksi.php';

// Cek role, hanya admin dan owner yang boleh akses
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

$username = $_SESSION['username'];
$query = "SELECT * FROM kategori ORDER BY id_kategori DESC"; // Tampilkan dari terbaru
$result = mysqli_query($koneksi, $query);

// Notifikasi pesan
$message = '';
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] == "input") {
        $message = "✅ Data berhasil ditambahkan.";
    } elseif ($_GET['pesan'] == "hapus") {
        $message = "✅ Data berhasil dihapus.";
    } elseif ($_GET['pesan'] == "update") {
        $message = "✅ Data berhasil diupdate.";
    } elseif ($_GET['pesan'] == "gagal") {
        $message = "❌ Terjadi kesalahan saat memproses data.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Subang Outdoor - Kategori</title>

    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
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
                        <h1 class="h3 mb-0 text-gray-800">Kategori Barang</h1>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <a class="btn btn-primary" href="tambah_kategori.php" role="button">
                                <i class="fas fa-plus"></i> Tambah Kategori
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Kategori</th>
                                            <th style="width: 100px;">Aksi</th> </tr>
                                    </thead>
                                    <tbody>
                                       <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                           <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                               <tr>
                                                   <td><?= $row['id_kategori'] ?></td>
                                                   <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                                   <td>
                                                       <a class="btn btn-warning btn-sm" href="edit.php?id_kategori=<?= $row['id_kategori'] ?>" data-toggle="tooltip" title="Edit">
                                                           <i class="fas fa-edit"></i>
                                                       </a>
                                                       <a class="btn btn-danger btn-sm" href="hapus.php?id_kategori=<?= $row['id_kategori'] ?>" onclick="return confirm('Yakin ingin menghapus kategori ini? Semua barang dalam kategori ini juga akan terpengaruh.')" data-toggle="tooltip" title="Hapus">
                                                           <i class="fas fa-trash"></i>
                                                       </a>
                                                   </td>
                                               </tr>
                                           <?php endwhile; ?>
                                       <?php else: ?>
                                           <tr><td colspan="3" class="text-center">Tidak ada data kategori.</td></tr>
                                       <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> </div> </div>
        </div>

    </div>

    <script src="../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../assets/js/sb-admin-2.min.js"></script>

    <script src="../assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        // Inisialisasi DataTable
        $('#dataTable').DataTable({
            "order": [[0, "desc"]], // kolom ke-0 = ID, urutkan descending
            "columnDefs": [
                { "orderable": false, "targets": 2 } // Kolom Aksi (ke-2) tidak bisa di-sort
            ]
        });

        // Inisialisasi Tooltip (untuk title pada ikon)
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
</body>

</html>