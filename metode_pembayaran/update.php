<?php
include '../route/koneksi.php';

// Path upload gambar
$folder_upload = "metode/gambar/";
if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0755, true);
}

$id_metode     = $_POST['id_metode'];
$nama_metode   = $_POST['nama_metode'];
$nomor_rekening = $_POST['nomor_rekening'];
$atas_nama     = $_POST['atas_nama'];
$new_file_name = null; // Default jika tidak upload gambar

// Ambil gambar lama dari database
$query = mysqli_query($koneksi, "SELECT gambar_metode FROM metode_pembayaran WHERE id_metode='$id_metode'");
$data_lama = mysqli_fetch_assoc($query);
$gambar_lama = $data_lama['gambar_metode'] ?? null;

// Cek apakah ada file gambar baru yang diupload
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
    $file_tmp = $_FILES['gambar']['tmp_name'];
    $file_name = basename($_FILES['gambar']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($file_ext, $allowed_ext)) {
        $new_file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "", $file_name);
        $target_file = $folder_upload . $new_file_name;

        if (move_uploaded_file($file_tmp, $target_file)) {
            // Hapus gambar lama jika ada
            if (!empty($gambar_lama) && file_exists($folder_upload . $gambar_lama)) {
                unlink($folder_upload . $gambar_lama);
            }
        } else {
            echo "Gagal mengupload gambar baru.";
            exit;
        }
    } else {
        echo "Format gambar tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.";
        exit;
    }
}

// Bangun query UPDATE
if ($new_file_name) {
    $update_query = "UPDATE metode_pembayaran SET 
        nama_metode='$nama_metode',
        nomor_rekening='$nomor_rekening',
        gambar_metode='$new_file_name',
        atas_nama='$atas_nama'
        WHERE id_metode='$id_metode'";
} else {
    $update_query = "UPDATE metode_pembayaran SET 
        nama_metode='$nama_metode',
        nomor_rekening='$nomor_rekening',
        atas_nama='$atas_nama'
        WHERE id_metode='$id_metode'";
}

mysqli_query($koneksi, $update_query) or die(mysqli_error($koneksi));
header("location:metode.php?pesan=update");
exit;
?>