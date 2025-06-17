<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Tambah Data Admin</title>

    <!-- Custom fonts for this template -->
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,400,700" rel="stylesheet" />

    <!-- Custom styles for this template -->
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet" />
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include '../layout/sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Navbar -->
                <?php include '../layout/navbar.php'; ?>
                <!-- End Navbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-user-plus"></i> Tambah Admin Baru</h1>
                        <a href="admin.php" class="btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
                        </a>
                    </div>

                    <!-- Form Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Form Input Admin</h6>
                        </div>
                        <div class="card-body">
                            <form action="tambah_aksi.php" method="post" id="formTambahAdmin">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" name="username" class="form-control" id="username" required />
                                </div>
                                <div class="form-group">
                                    <label for="nama_admin">Nama Admin</label>
                                    <input type="text" name="nama_admin" class="form-control" id="nama_admin" required />
                                </div>
                                <div class="form-group">
                                    <label for="alamat">Alamat</label>
                                    <input type="text" name="alamat" class="form-control" id="alamat" required />
                                </div>
                                <div class="form-group">
                                    <label for="no_hp">No HP</label>
                                    <input type="number" name="no_hp" class="form-control" id="no_hp" pattern="[0-9]+" title="Hanya angka yang diperbolehkan" required />
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" class="form-control" id="email" required />
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" name="password" class="form-control" id="password" minlength="8" required />
                                    <div class="form-check mt-2">
                                        <input type="checkbox" class="form-check-input" id="showPassword" onclick="togglePassword()" />
                                        <label class="form-check-label" for="showPassword">Tampilkan Password</label>
                                    </div>
                                    <small class="form-text text-muted">Password minimal 8 karakter.</small>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
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

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <!-- Bootstrap core JavaScript -->
    <script src="../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript -->
    <script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages -->
    <script src="../assets/js/sb-admin-2.min.js"></script>

    <!-- Show Password Script -->
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const type = passwordInput.type === "password" ? "text" : "password";
            passwordInput.type = type;
        }

        // Validasi tambahan saat submit form
        document.getElementById("formTambahAdmin").addEventListener("submit", function (e) {
            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;
            const no_hp = document.getElementById("no_hp").value;

            // Validasi email mengandung @
            if (!email.includes("@")) {
                alert("Email harus mengandung karakter '@' dan format yang valid.");
                e.preventDefault();
                return;
            }

            // Validasi panjang password minimal 9 karakter
            if (password.length < 8) {
                alert("Password harus lebih dari 8 karakter.");
                e.preventDefault();
                return;
            }

            // Validasi nomor HP hanya angka
            if (!/^\d+$/.test(no_hp)) {
                alert("No HP hanya boleh mengandung angka.");
                e.preventDefault();
                return;
            }
        });
    </script>

</body>
</html>
