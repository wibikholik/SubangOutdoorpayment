<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

// Ambil data tipe metode
$tipe_metode_result = mysqli_query($koneksi, "SELECT * FROM tipe_metode ORDER BY nama_tipe ASC");
$tipe_metode_list = [];
while ($row = mysqli_fetch_assoc($tipe_metode_result)) {
    $tipe_metode_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Input Data Metode</title>

    <!-- SB Admin 2 Styles -->
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,400,700" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <?php include('../layout/sidebar.php'); ?>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Page Heading -->
                <h3 class="w3-text-blue">Input Data Metode</h3>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form action="tambah_aksi.php" method="post" enctype="multipart/form-data">

                            <div class="form-group">
                                <label for="nama_metode">Nama Metode</label>
                                <input type="text" name="nama_metode" class="form-control" id="nama_metode" required>
                            </div>

                            <div class="form-group">
                                <label for="nomor_rekening">Nomor Rekening</label>
                                <input type="text" name="nomor_rekening" class="form-control" id="nomor_rekening" required>
                            </div>

                            <div class="form-group">
                                <label for="gambar_metode">Gambar</label>
                                <input type="file" name="gambar_metode" class="form-control-file" id="gambar_metode" accept="image/*" required>
                            </div>

                            <div class="form-group">
                                <label for="atas_nama">Atas Nama</label>
                                <input type="text" name="atas_nama" class="form-control" id="atas_nama" required>
                            </div>

                            <div class="form-group">
                                <label for="id_tipe">Tipe Metode</label>
                                <select name="id_tipe" id="id_tipe" class="form-control" required>
                                    <option value="">-- Pilih Tipe Metode --</option>
                                    <?php foreach ($tipe_metode_list as $tipe): ?>
                                        <option value="<?= $tipe['id_tipe'] ?>"><?= htmlspecialchars($tipe['nama_tipe']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Tambah</button>
                        </form>
                    </div>
                </div>

            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content -->

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- SB Admin 2 Scripts -->
<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>

</body>
</html>
