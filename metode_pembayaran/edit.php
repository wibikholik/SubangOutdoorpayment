<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Data Metode</title>

    <!-- SB Admin 2 Styles -->
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,400,700" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">

    <!-- Sidebar -->
    <?php include '../layout/sidebar.php'; ?>
    <!-- End of Sidebar -->

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <!-- Navbar -->
            <?php include '../layout/navbar.php'; ?>
            <!-- End of Navbar -->

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Edit Data Metode</h1>

                <?php
                include "../route/koneksi.php";

                if (isset($_GET['id_metode'])) {
                    $id_metode = intval($_GET['id_metode']);
                    $stmt = mysqli_prepare($koneksi, "SELECT * FROM metode_pembayaran WHERE id_metode = ?");
                    mysqli_stmt_bind_param($stmt, "i", $id_metode);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if ($data = mysqli_fetch_assoc($result)) {
                ?>
                    <form action="update.php" method="post" enctype="multipart/form-data" class="card shadow p-4">
                        <input type="hidden" name="id_metode" value="<?= $data['id_metode'] ?>">

                        <div class="form-group">
                            <label for="nama_metode">Nama Metode</label>
                            <input type="text" name="nama_metode" class="form-control" id="nama_metode" value="<?= htmlspecialchars($data['nama_metode']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="nomor_rekening">Nomor Rekening</label>
                            <input type="text" name="nomor_rekening" class="form-control" id="nomor_rekening" value="<?= htmlspecialchars($data['nomor_rekening']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="gambar">Gambar</label>
                            <input type="file" name="gambar" class="form-control-file" accept="image/*" id="gambar">
                            <?php if (!empty($data['gambar_metode'])) : ?>
                                <div class="mt-2">
                                    <img src="image/metode/<?= htmlspecialchars($data['gambar_metode']) ?>" alt="Gambar metode" style="width: 100px; height: auto;">
                                    <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah gambar.</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="atas_nama">Atas Nama</label>
                            <input type="text" name="atas_nama" class="form-control" id="atas_nama" value="<?= htmlspecialchars($data['atas_nama']) ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                <?php
                    } else {
                        echo "<div class='alert alert-danger'>Data tidak ditemukan.</div>";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    echo "<div class='alert alert-warning'>ID metode tidak ditemukan.</div>";
                }
                ?>
            </div>

        </div>
    </div>
</div>
</body>
</html>