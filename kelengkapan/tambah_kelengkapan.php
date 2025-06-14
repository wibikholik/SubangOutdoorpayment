<?php
session_start();
include '../route/koneksi.php';

// Cek role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

// Ambil data kategori untuk dropdown
$queryKategori = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
$resultKategori = mysqli_query($koneksi, $queryKategori);

// Proses submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = $_POST['id_kategori'];
    $nama_kelengkapan = trim($_POST['nama_kelengkapan']);

    // Validasi sederhana
    if (empty($id_kategori) || empty($nama_kelengkapan)) {
        $error = "Semua field wajib diisi.";
    } else {
        // Insert ke database
        $stmt = $koneksi->prepare("INSERT INTO kelengkapan_barang (id_kategori, nama_kelengkapan) VALUES (?, ?)");
        $stmt->bind_param("is", $id_kategori, $nama_kelengkapan);

        if ($stmt->execute()) {
            header("Location: kelengkapan.php?pesan=input");
            exit;
        } else {
            $error = "Gagal menambahkan data: " . $stmt->error;
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
    <title>Tambah Kelengkapan - Subang Outdoor</title>
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
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
                <h1 class="h3 mb-4 text-gray-800">Tambah Kelengkapan Barang</h1>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="id_kategori">Kategori</label>
                        <select name="id_kategori" id="id_kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php while ($row = mysqli_fetch_assoc($resultKategori)): ?>
                                <option value="<?= $row['id_kategori'] ?>" <?= (isset($_POST['id_kategori']) && $_POST['id_kategori'] == $row['id_kategori']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['nama_kategori']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nama_kelengkapan">Nama Kelengkapan</label>
                        <input type="text" name="nama_kelengkapan" id="nama_kelengkapan" class="form-control" required
                               value="<?= isset($_POST['nama_kelengkapan']) ? htmlspecialchars($_POST['nama_kelengkapan']) : '' ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="kelengkapan.php" class="btn btn-secondary">Batal</a>
                </form>

            </div>

        </div>
    </div>

</div>

<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../assets/js/sb-admin-2.min.js"></script>
</body>
</html>
