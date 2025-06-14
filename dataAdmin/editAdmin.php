<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}
include "../route/koneksi.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Admin</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,400,700" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">

<div id="wrapper">
    <?php include('../layout/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include('../layout/navbar.php'); ?>

            <div class="container-fluid">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-user-edit"></i> Edit Data Admin</h1>
                    <a href="admin.php" class="btn btn-sm btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
                    </a>
                </div>

                <?php if (isset($_GET['pesan']) && $_GET['pesan'] === "update"): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> Data berhasil diupdate.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php
                if (isset($_GET['id_admin'])):
                    $id_admin = intval($_GET['id_admin']);
                    $stmt = mysqli_prepare($koneksi, "SELECT * FROM admin WHERE id_admin = ?");
                    mysqli_stmt_bind_param($stmt, "i", $id_admin);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if ($data = mysqli_fetch_assoc($result)):
                ?>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form action="update.php" method="post">
                            <input type="hidden" name="id_admin" value="<?= $data['id_admin']; ?>">

                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($data['username']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Nama Admin</label>
                                <input type="text" name="nama_admin" class="form-control" value="<?= htmlspecialchars($data['nama_admin']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Alamat</label>
                                <input type="text" name="alamat" class="form-control" value="<?= htmlspecialchars($data['alamat']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>No HP</label>
                                <input type="tel" name="no_hp" class="form-control" value="<?= htmlspecialchars($data['no_hp']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Password</label>
                                <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($data['password']); ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                        </form>
                    </div>
                </div>

                <?php
                    else:
                        echo '<div class="alert alert-danger">Data tidak ditemukan.</div>';
                    endif;
                    mysqli_stmt_close($stmt);
                else:
                    echo '<div class="alert alert-warning">ID Admin tidak tersedia.</div>';
                endif;
                ?>
            </div>

        </div>
    </div>
</div>

<!-- JS Resources -->
<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>

</body>
</html>
