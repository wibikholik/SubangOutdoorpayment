<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

$folder_upload = "barang/gambar/";

if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0755, true);
}

// Ambil dan validasi input POST
$id_barang   = $_POST['id_barang'] ?? '';
$nama_barang = trim($_POST['nama_barang'] ?? '');
$keterangan  = trim($_POST['keterangan'] ?? '');
$stok        = intval($_POST['stok'] ?? 0);
$harga_sewa  = floatval($_POST['harga_sewa'] ?? 0);
$id_kategori = $_POST['id_kategori'] ?? '';
$unggulan    = isset($_POST['unggulan']) ? 1 : 0;

// Validasi sederhana
if ($id_barang == '' || $nama_barang == '' || $keterangan == '' || $stok < 0 || $harga_sewa < 0 || $id_kategori == '') {
    echo "Input data tidak valid.";
    exit;
}

// Cek apakah kategori valid (ada di database)
$stmtCekKategori = mysqli_prepare($koneksi, "SELECT 1 FROM kategori WHERE id_kategori = ?");
mysqli_stmt_bind_param($stmtCekKategori, "i", $id_kategori);
mysqli_stmt_execute($stmtCekKategori);
mysqli_stmt_store_result($stmtCekKategori);

if (mysqli_stmt_num_rows($stmtCekKategori) === 0) {
    echo "Kategori tidak valid.";
    exit;
}
mysqli_stmt_close($stmtCekKategori);

// Ambil gambar lama
$stmtGambarLama = mysqli_prepare($koneksi, "SELECT gambar FROM barang WHERE id_barang = ?");
mysqli_stmt_bind_param($stmtGambarLama, "i", $id_barang);
mysqli_stmt_execute($stmtGambarLama);
$resultGambar = mysqli_stmt_get_result($stmtGambarLama);
$dataLama = mysqli_fetch_assoc($resultGambar);
$gambar_lama = $dataLama['gambar'] ?? '';
mysqli_stmt_close($stmtGambarLama);

// Batas ukuran file (2MB)
$max_size = 2 * 1024 * 1024; // 2MB dalam byte

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
    $file_tmp  = $_FILES['gambar']['tmp_name'];
    $file_name = basename($_FILES['gambar']['name']);
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    // Validasi ekstensi file
    if (!in_array($file_ext, $allowed_ext)) {
        echo "Format gambar tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.";
        exit;
    }

    // Validasi ukuran file
    if ($_FILES['gambar']['size'] > $max_size) {
        echo "Ukuran gambar terlalu besar. Maksimal ukuran file adalah 2MB.";
        exit;
    }

    // Generate nama file baru
    $new_file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "", $file_name);
    $target_file = $folder_upload . $new_file_name;

    if (!move_uploaded_file($file_tmp, $target_file)) {
        echo "Gagal mengupload gambar baru.";
        exit;
    }

    // Hapus gambar lama jika ada
    if (!empty($gambar_lama) && file_exists($folder_upload . $gambar_lama)) {
        unlink($folder_upload . $gambar_lama);
    }

    // Update dengan gambar baru
    $stmtUpdate = mysqli_prepare($koneksi, "UPDATE barang SET nama_barang=?, keterangan=?, gambar=?, stok=?, harga_sewa=?, id_kategori=?, unggulan=? WHERE id_barang=?");
    mysqli_stmt_bind_param($stmtUpdate, "sssdisii", $nama_barang, $keterangan, $new_file_name, $stok, $harga_sewa, $id_kategori, $unggulan, $id_barang);
} else {
    // Update tanpa gambar baru
    $stmtUpdate = mysqli_prepare($koneksi, "UPDATE barang SET nama_barang=?, keterangan=?, stok=?, harga_sewa=?, id_kategori=?, unggulan=? WHERE id_barang=?");
    mysqli_stmt_bind_param($stmtUpdate, "ssdisii", $nama_barang, $keterangan, $stok, $harga_sewa, $id_kategori, $unggulan, $id_barang);
}

if (mysqli_stmt_execute($stmtUpdate)) {
    mysqli_stmt_close($stmtUpdate);
    header("Location: barang.php?pesan=update");
    exit;
} else {
    echo "Gagal update data: " . mysqli_error($koneksi);
}
?>
