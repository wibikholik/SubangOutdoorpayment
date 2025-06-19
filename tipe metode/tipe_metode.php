<?php
// File: admin/metode/tipe_metode.php

include '../route/koneksi.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Handle create
if (isset($_POST['tambah'])) {
    $nama_tipe = trim($_POST['nama_tipe']);
    if ($nama_tipe !== '') {
        $stmt = $koneksi->prepare("INSERT INTO tipe_metode (nama_tipe) VALUES (?)");
        $stmt->bind_param("s", $nama_tipe);
        $stmt->execute();
        $stmt->close();
        header("Location: tipe_metode.php?pesan=tambah");
        exit;
    }
}

// Handle delete
if (isset($_GET['hapus'])) {
    $id_tipe = intval($_GET['hapus']);
    $stmt = $koneksi->prepare("DELETE FROM tipe_metode WHERE id_tipe = ?");
    $stmt->bind_param("i", $id_tipe);
    $stmt->execute();
    $stmt->close();
    header("Location: tipe_metode.php?pesan=hapus");
    exit;
}

// Handle update
if (isset($_POST['ubah'])) {
    $id_tipe = intval($_POST['id_tipe']);
    $nama_tipe = trim($_POST['nama_tipe']);
    if ($nama_tipe !== '') {
        $stmt = $koneksi->prepare("UPDATE tipe_metode SET nama_tipe = ? WHERE id_tipe = ?");
        $stmt->bind_param("si", $nama_tipe, $id_tipe);
        $stmt->execute();
        $stmt->close();
        header("Location: tipe_metode.php?pesan=update");
        exit;
    }
}

$result = $koneksi->query("SELECT * FROM tipe_metode ORDER BY id_tipe ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Tipe Metode</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</head>
<body id="page-top">
<div id="wrapper">
    <?php include '../layout/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include '../layout/navbar.php'; ?>
            <div class="container-fluid">
                <h1 class="h3 mb-2 text-gray-800">Kelola Tipe Metode Pembayaran</h1>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah Tipe</button>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Tipe</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; while ($row = $result->fetch_assoc()) : ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_tipe']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalUbah<?= $row['id_tipe'] ?>">Edit</button>
                                            <a href="?hapus=<?= $row['id_tipe'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin hapus?')">Hapus</a>
                                        </td>
                                    </tr>

                                    <!-- Modal Edit -->
                                    <div class="modal fade" id="modalUbah<?= $row['id_tipe'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="post" class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Ubah Tipe</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_tipe" value="<?= $row['id_tipe'] ?>">
                                                    <input type="text" name="nama_tipe" class="form-control" value="<?= htmlspecialchars($row['nama_tipe']) ?>" required>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" name="ubah" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Tambah -->
                <div class="modal fade" id="modalTambah" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="post" class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Tambah Tipe Metode</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="text" name="nama_tipe" class="form-control" placeholder="Nama tipe metode" required>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="tambah" class="btn btn-success">Tambah</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
