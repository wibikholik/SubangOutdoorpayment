<?php
session_start();
include '../../route/koneksi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}
$id_penyewa = $_SESSION['user_id'];

// Query data penyewa
$sql = "SELECT nama_penyewa, alamat, no_hp, email FROM penyewa WHERE id_penyewa = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $id_penyewa);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data profil tidak ditemukan.");
}
$user = $result->fetch_assoc();

// Tangkap pesan dari proses update
$pesan = "";
$alert_class = "";
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'sukses') {
        $pesan = 'Profil berhasil diperbarui.';
        $alert_class = 'alert-success';
    } elseif ($_GET['status'] == 'gagal') {
        $pesan = 'Gagal memperbarui profil. Silakan coba lagi.';
        $alert_class = 'alert-danger';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Profil Saya - Subang Outdoor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/linearicons.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/nouislider.min.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="shortcut icon" href="../../assets/img/logo.jpg">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .section_gap { padding: 60px 0; }
        .profile-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.07);
            border: none;
            padding: 30px 40px; /* Padding lebih besar */
        }
        .profile-form .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        .profile-form .form-control {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .profile-form .form-control:focus {
            border-color: #fab700;
            box-shadow: 0 0 0 0.2rem rgba(250, 183, 0, 0.25);
        }
        .primary-btn {
            background-color: #fab700; border: none; color: #fff !important; padding: 12px 30px;
            border-radius: 50px; font-weight: 700; cursor: pointer; text-decoration: none;
            display: inline-block; transition: all 0.3s ease; text-align: center;
        }
        .primary-btn:hover { background-color: #e0a800; transform: translateY(-2px); }
    </style>
</head>
<body>

<?php include("../layout/navbar1.php"); ?>

<section class="banner-area organic-breadcrumb">
    <div class="container">
        <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
            <div class="col-first">
                <h1>Profil Saya</h1>
                <nav class="d-flex align-items-center">
                    <a href="/subangoutdoor/index.php">Home<span class="lnr lnr-arrow-right"></span></a>
                    <a href="#">Profil</a>
                </nav>
            </div>
        </div>
    </div>
</section>

<div class="container section_gap">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="profile-card">
                <h3 class="mb-4 font-weight-bold"><i class="fas fa-user-edit mr-2"></i>Edit Profil</h3>

                <?php if ($pesan): ?>
                    <div class="alert <?= $alert_class ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($pesan) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <form action="../controller/update_profil.php" method="POST" class="profile-form needs-validation" novalidate>
                    <input type="hidden" name="id_penyewa" value="<?= $id_penyewa ?>">

                    <div class="form-group mb-3">
                        <label for="nama_penyewa" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama_penyewa" name="nama_penyewa" required value="<?= htmlspecialchars($user['nama_penyewa']) ?>">
                        <div class="invalid-feedback">Nama wajib diisi.</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= htmlspecialchars($user['alamat']) ?></textarea>
                        <div class="invalid-feedback">Alamat wajib diisi.</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="no_hp" class="form-label">No. HP</label>
                        <input type="tel" class="form-control" id="no_hp" name="no_hp" required pattern="[0-9+\-\s]{7,20}" value="<?= htmlspecialchars($user['no_hp']) ?>">
                        <div class="invalid-feedback">No. HP wajib diisi dan hanya boleh berisi angka.</div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>">
                    </div>

                    <button type="submit" class="primary-btn">
                     Simpan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../layout/footer.php"); ?>

<script src="js/vendor/jquery-2.2.4.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
  <script src="js/vendor/bootstrap.min.js"></script>
  <script src="js/jquery.ajaxchimp.min.js"></script>
  <script src="js/jquery.nice-select.min.js"></script>
  <script src="js/jquery.sticky.js"></script>
  <script src="js/nouislider.min.js"></script>
  <script src="js/jquery.magnific-popup.min.js"></script>
  <script src="js/owl.carousel.min.js"></script>
  <script src="js/gmaps.min.js"></script>
  <script src="js/main.js"></script>
<script>
// Validasi Form Bootstrap
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>
</body>
</html>