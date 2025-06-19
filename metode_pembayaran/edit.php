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

    <?php include '../layout/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include '../layout/navbar.php'; ?>

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Edit Data Metode</h1>

                <?php
                include "../route/koneksi.php";

                // Ambil data tipe metode dari tabel tipe_metode
                $tipe_metode_result = mysqli_query($koneksi, "SELECT * FROM tipe_metode ORDER BY nama_tipe ASC");
                $tipe_metode_list = [];
                while ($row_tipe = mysqli_fetch_assoc($tipe_metode_result)) {
                    $tipe_metode_list[] = $row_tipe; // simpan array tipe metode
                }

                if (isset($_GET['id_metode'])) {
                    $id_metode = intval($_GET['id_metode']);
                    $stmt = mysqli_prepare($koneksi, "SELECT * FROM metode_pembayaran WHERE id_metode = ?");
                    mysqli_stmt_bind_param($stmt, "i", $id_metode);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if ($data = mysqli_fetch_assoc($result)) {
                        // Ini sekarang id_tipe yang tersimpan
                        $current_id_tipe = $data['id_tipe'];
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
                            <label for="id_tipe">Tipe Metode</label>
                            <select name="id_tipe" id="id_tipe" class="form-control" required>
                                <option value="">-- Pilih Tipe Metode --</option>
                                <?php foreach ($tipe_metode_list as $tipe): ?>
                                    <option value="<?= $tipe['id_tipe'] ?>" <?= ($current_id_tipe == $tipe['id_tipe']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tipe['nama_tipe']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="gambar_metode">Gambar</label>
                            <input type="file" name="gambar_metode" class="form-control-file" accept="image/*" id="gambar_metode">
                            <?php if (!empty($data['gambar_metode'])) : ?>
                                <div class="mt-2">
                                    <img src="metode/gambar/<?= htmlspecialchars($data['gambar_metode']) ?>" alt="Gambar metode" style="width: 100px; height: auto;">
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
