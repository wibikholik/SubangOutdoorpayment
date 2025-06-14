<?php
session_start();
include '../route/koneksi.php';

// Cek role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

$username = $_SESSION['username'];

// Ambil data barang dan kategori secara dinamis
$query = "SELECT barang.*, kategori.nama_kategori 
          FROM barang 
          LEFT JOIN kategori ON barang.id_kategori = kategori.id_kategori 
          ORDER BY barang.id_barang DESC";
$result = mysqli_query($koneksi, $query);

// Pesan notifikasi
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

// Tampilan alert menggunakan JavaScript di halaman barang.php
if (isset($_GET['pesan'])) {
    $pesan = $_GET['pesan'];
    if ($pesan == 'gagalhapus') {
        $error_message = isset($_GET['error']) ? $_GET['error'] : "Gagal menghapus barang.";
        echo "<script>alert('Error: $error_message');</script>";
    } elseif ($pesan == 'hapus') {
        echo "<script>alert('Barang berhasil dihapus!');</script>";
    } elseif ($pesan == 'invalid') {
        echo "<script>alert('ID barang tidak ditemukan.');</script>";
    } elseif ($pesan == 'gagalhapusdb') {
        echo "<script>alert('Gagal menghapus data barang dari database.');</script>";
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Subang Outdoor - Barang</title>

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
                    <h1 class="h3 mb-0 text-gray-800">Barang</h1>
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
                        <a class="btn btn-primary" href="tambah_barang.php" role="button">
                            <i class="fas fa-plus"></i> Tambah Barang
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Gambar</th>
                                    <th>Stok</th>
                                    <th>Harga</th>
                                    <th>Kategori</th>
                                    <th>Keterangan</th>
                                    <th>Unggulan</th>
                                    <th>Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?= $row['id_barang'] ?></td>
                                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                            <td>
                                                <?php if (!empty($row['gambar'])): ?>
                                                    <img src="barang/gambar/<?= $row['gambar'] ?>" width="100" alt="gambar">
                                                <?php else: ?>
                                                    <em>tidak ada</em>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $row['stok'] ?></td>
                                            <td>Rp.<?= number_format($row['harga_sewa'], 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($row['nama_kategori'] ?? 'Tidak Ada') ?></td>
                                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                            <td><?= $row['unggulan'] == 1 ? 'YA' : 'TIDAK' ?></td>
                                            <td>
                                                <a class="btn btn-warning btn-sm" href="edit.php?id_barang=<?= $row['id_barang'] ?>" data-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a class="btn btn-danger btn-sm" href="hapus.php?id_barang=<?= $row['id_barang'] ?>" onclick="return confirm('Yakin ingin menghapus barang ini?')" data-toggle="tooltip" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Data barang belum tersedia.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div> </div> </div> </div> <script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>

<script src="../assets/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script>
    // Inisialisasi DataTable
    $('#dataTable').DataTable({
        "order": [[0, "desc"]]
    });

    // Inisialisasi Tooltip (untuk title pada ikon)
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>

</body>
</html>