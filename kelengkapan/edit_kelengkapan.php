<?php
session_start();
include '../route/koneksi.php';

// Cek role admin/owner
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

$error = '';

// Ambil id kelengkapan dari query string
if (!isset($_GET['id_kelengkapan'])) {
    header("Location: kelengkapan.php");
    exit;
}

$id_kelengkapan = (int) $_GET['id_kelengkapan'];

// Ambil data kelengkapan berdasarkan id
$query = "SELECT * FROM kelengkapan_barang WHERE id_kelengkapan = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_kelengkapan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Jika data tidak ditemukan
    header("Location: kelengkapan.php?pesan=gagal");
    exit;
}

$data = $result->fetch_assoc();
$stmt->close();

// Ambil data kategori untuk dropdown
$kategoriResult = $koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori");

// Proses update data jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = $_POST['id_kategori'];
    $nama_kelengkapan = trim($_POST['nama_kelengkapan']);

    if (empty($id_kategori) || empty($nama_kelengkapan)) {
        $error = "Semua field wajib diisi.";
    } else {
        $updateQuery = "UPDATE kelengkapan_barang SET id_kategori = ?, nama_kelengkapan = ? WHERE id_kelengkapan = ?";
        $stmt = $koneksi->prepare($updateQuery);
        $stmt->bind_param("isi", $id_kategori, $nama_kelengkapan, $id_kelengkapan);

        if ($stmt->execute()) {
            header("Location: kelengkapan.php?pesan=update");
            exit;
        } else {
            $error = "Gagal mengupdate data: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Kelengkapan Barang - Subang Outdoor</title>

    <!-- Font & Template -->
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,400,700" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body id="page-top">
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include '../layout/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <!-- Navbar -->
                <?php include '../layout/navbar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Edit Kelengkapan Barang</h1>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="id_kategori">Kategori</label>
                                    <select name="id_kategori" id="id_kategori" class="form-control" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php while ($kat = $kategoriResult->fetch_assoc()): ?>
                                            <option value="<?= $kat['id_kategori'] ?>" <?= ($kat['id_kategori'] == $data['id_kategori']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="nama_kelengkapan">Nama Kelengkapan</label>
                                    <input type="text" class="form-control" id="nama_kelengkapan" name="nama_kelengkapan" value="<?= htmlspecialchars($data['nama_kelengkapan']) ?>" required>
                                </div>

                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="kelengkapan.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
           
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- JS Scripts -->
    <script src="../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../assets/js/sb-admin-2.min.js"></script>

</body>

</html>
