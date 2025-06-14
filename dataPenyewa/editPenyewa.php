<?php
session_start();

// Batasi akses hanya untuk admin dan owner
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

include "../route/koneksi.php";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Subang Outdoor - Edit Penyewa</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font & CSS -->
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .password-container {
            position: relative;
        }

        .password-container i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>

<body id="page-top">

    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('../layout/sidebar.php'); ?>
        <!-- End of Sidebar -->

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <?php include('../layout/navbar.php'); ?>
                <!-- End of Topbar -->

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><i></i> Edit Penyewa</h1>
                        <a href="penyewa.php" class="btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
                        </a>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <!-- Menampilkan pesan error jika password terlalu pendek -->
                            <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'password_terlalu_pendek'): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    Password harus memiliki minimal 8 karakter.
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_GET['pesan'])): ?>
                                <?php if ($_GET['pesan'] == 'duplikat_email'): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        Email sudah terdaftar oleh penyewa lain.
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php elseif ($_GET['pesan'] == 'duplikat_no_hp'): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        No HP sudah terdaftar oleh penyewa lain.
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php
                            if (isset($_GET['id_penyewa'])) {
                                $id_penyewa = intval($_GET['id_penyewa']);
                                $stmt = mysqli_prepare($koneksi, "SELECT * FROM penyewa WHERE id_penyewa = ?");
                                mysqli_stmt_bind_param($stmt, "i", $id_penyewa);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);

                                if ($data = mysqli_fetch_assoc($result)) {
                            ?>
                                    <form action="update.php" method="post" enctype="multipart/form-data" autocomplete="off">
                                        <input type="hidden" name="id_penyewa" value="<?= $data['id_penyewa']; ?>">

                                        <div class="form-group">
                                            <label for="namapenyewa">Nama Penyewa</label>
                                            <input type="text" class="form-control" id="namapenyewa" name="namapenyewa" value="<?= htmlspecialchars($data['nama_penyewa']); ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="alamat">Alamat</label>
                                            <input type="text" class="form-control" id="alamat" name="alamat" value="<?= htmlspecialchars($data['alamat']); ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="no_hp">No HP</label>
                                            <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= htmlspecialchars($data['no_hp']); ?>" pattern="\d*" minlength="10" maxlength="15" required oninput="this.value=this.value.replace(/[^0-9]/g,'');">
                                        </div>

                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($data['email']); ?>" required>
                                        </div>

                                        <div class="form-group password-container">
                                            <label for="password">Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="password" name="password" value="<?= htmlspecialchars($data['password']); ?>" required minlength="8">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="text-muted">Password minimal 8 karakter.</small>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </form>
                            <?php
                                } else {
                                    echo '<div class="alert alert-danger">Data penyewa tidak ditemukan.</div>';
                                }
                                mysqli_stmt_close($stmt);
                            } else {
                                echo '<div class="alert alert-warning">ID Penyewa tidak tersedia.</div>';
                            }
                            ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../assets/js/sb-admin-2.min.js"></script>
    <script>
        // Toggle show/hide password
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>

</html>
