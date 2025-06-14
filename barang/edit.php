<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../login.php');
    exit;
}
include "../route/koneksi.php";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Data Barang</title>

    <!-- SB Admin 2 Template -->
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,400,700" rel="stylesheet" />
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet" />
</head>
<body id="page-top">

<div id="wrapper">

    <?php include('../layout/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">

        <div id="content">

            <?php include('../layout/navbar.php'); ?>

            <div class="container-fluid">

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Edit Barang</h1>
                    <a href="barang.php" class="btn btn-sm btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
                    </a>
                </div>

                <?php
                if (isset($_GET['id_barang'])) {
                    $id_barang = intval($_GET['id_barang']);
                    $stmt = mysqli_prepare($koneksi, "SELECT * FROM barang WHERE id_barang = ?");
                    mysqli_stmt_bind_param($stmt, "i", $id_barang);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if ($data = mysqli_fetch_assoc($result)) {
                ?>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form action="update.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="id_barang" value="<?= htmlspecialchars($data['id_barang']) ?>">

                            <div class="form-group">
                                <label>Nama Barang</label>
                                <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($data['nama_barang']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Kategori</label>
                                <select name="id_kategori" class="form-control" required>
                                    <option value="" disabled>-- Pilih Kategori --</option>
                                    <?php
                                    $kategori_query = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
                                    while ($row_kategori = mysqli_fetch_assoc($kategori_query)) {
                                        $selected = ($row_kategori['id_kategori'] == $data['id_kategori']) ? 'selected' : '';
                                        echo '<option value="'.htmlspecialchars($row_kategori['id_kategori']).'" '.$selected.'>'.htmlspecialchars($row_kategori['nama_kategori']).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" name="unggulan" value="1" class="form-check-input" id="unggulanCheck" <?= ($data['unggulan'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="unggulanCheck">Tandai sebagai Produk Unggulan</label>
                            </div>

                            <div class="form-group">
                                <label>Keterangan</label>
                                <textarea name="keterangan" class="form-control" required><?= htmlspecialchars($data['keterangan']) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Gambar</label>
                                <input type="file" name="gambar" class="form-control-file" accept="image/*" />
                                <?php if (!empty($data['gambar'])): ?>
                                    <div class="mt-2">
                                        <img src="barang/gambar/<?= htmlspecialchars($data['gambar']) ?>" alt="Gambar Barang" class="img-thumbnail" style="width: 150px;">
                                        <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah gambar.</small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>Stok Barang</label>
                                <input type="number" name="stok" class="form-control" min="0" value="<?= htmlspecialchars($data['stok']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Harga Sewa (per hari)</label>
                                <input type="number" name="harga_sewa" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($data['harga_sewa']) ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>

                <?php
                    } else {
                        echo '<div class="alert alert-danger">Data tidak ditemukan.</div>';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    echo '<div class="alert alert-warning">ID Barang tidak tersedia.</div>';
                }
                ?>

            </div>

        </div>

    </div>

</div>

<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
