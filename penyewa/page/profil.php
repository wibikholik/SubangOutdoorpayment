<?php
session_start();
include("../../route/koneksi.php");

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
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'sukses') {
        $pesan = '<div class="alert alert-success" role="alert">Profil berhasil diperbarui.</div>';
    } elseif ($_GET['status'] == 'gagal') {
        $pesan = '<div class="alert alert-danger" role="alert">Gagal memperbarui profil. Silakan coba lagi.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Profil Saya - Subang Outdoor</title>
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css" />
  <link rel="stylesheet" href="css/bootstrap.css">
  <link rel="stylesheet" href="css/main.css">
   <link rel="shortcut icon" href="../../assets/img/logo.jpg">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php include("../layout/navbar1.php"); ?>

<section class="banner-area organic-breadcrumb">
  <div class="container">
    <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
      <div class="col-first">
        <h1>Subang Outdoor</h1>
        <nav class="d-flex align-items-center">
          <a href="#">Profil Saya</a>
        </nav>
      </div>
    </div>
  </div>
</section>

<div class="container mt-5" style="max-width: 700px;">
  <h3 class="mb-4"><i class="bi bi-person-circle me-2"></i> Profil Penyewa</h3>

  <?= $pesan ?>

  <form action="../controller/update_profil.php" method="POST" novalidate>
    <input type="hidden" name="id_penyewa" value="<?= $id_penyewa ?>">

    <div class="mb-3">
      <label for="nama_penyewa" class="form-label">
        <i class="bi bi-person-fill me-1"></i> Nama Lengkap
      </label>
      <input
        type="text"
        class="form-control"
        id="nama_penyewa"
        name="nama_penyewa"
        required
        value="<?= htmlspecialchars($user['nama_penyewa']) ?>"
      />
      <div class="invalid-feedback">Nama wajib diisi.</div>
    </div>

    <div class="mb-3">
      <label for="alamat" class="form-label">
        <i class="bi bi-geo-alt-fill me-1"></i> Alamat
      </label>
      <textarea
        class="form-control"
        id="alamat"
        name="alamat"
        rows="3"
        required
      ><?= htmlspecialchars($user['alamat']) ?></textarea>
      <div class="invalid-feedback">Alamat wajib diisi.</div>
    </div>

    <div class="mb-3">
      <label for="no_hp" class="form-label">
        <i class="bi bi-telephone-fill me-1"></i> No. HP
      </label>
      <input
        type="tel"
        class="form-control"
        id="no_hp"
        name="no_hp"
        required
        pattern="[0-9+\-\s]{7,20}"
        value="<?= htmlspecialchars($user['no_hp']) ?>"
      />
      <div class="invalid-feedback">No. HP wajib diisi dan hanya angka, spasi, tanda + atau -.</div>
    </div>

    <div class="mb-3">
      <label for="email" class="form-label">
        <i class="bi bi-envelope-fill me-1"></i> Email
      </label>
      <input
        type="email"
        class="form-control"
        id="email"
        name="email"
        required
        value="<?= htmlspecialchars($user['email']) ?>"
      />
      <div class="invalid-feedback">Email wajib diisi dengan format valid.</div>
    </div>

    <button type="submit" class="btn btn-primary">
      <i class="bi bi-save me-1"></i> Simpan Perubahan
    </button>
  </form>
</div>
<br>
<?php include("../layout/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Bootstrap form validation
(() => {
  'use strict';
  const forms = document.querySelectorAll('form');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
})();
</script>
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
</body>
</html>
