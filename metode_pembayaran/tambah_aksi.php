<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php");
    exit;
}

include '../route/koneksi.php'; 

$folder_upload = "metode/gambar/";

// Pastikan folder ada
if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0755, true);
}

// Ambil data dari form
$Nama_Metode     = mysqli_real_escape_string($koneksi, $_POST['nama_metode']);
$Nomor_Rekening  = mysqli_real_escape_string($koneksi, $_POST['nomor_rekening']);
$Atas_Nama       = mysqli_real_escape_string($koneksi, $_POST['atas_nama']);

if (isset($_FILES['gambar_metode']) && $_FILES['gambar_metode']['error'] === 0) {
    $file_tmp = $_FILES['gambar_metode']['tmp_name'];
    $file_name = basename($_FILES['gambar_metode']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

    if (in_array($file_ext, $allowed_ext)) {
        $new_file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "", $file_name);
        $target_file = $folder_upload . $new_file_name;

        if (move_uploaded_file($file_tmp, $target_file)) {
            $query = "INSERT INTO metode_pembayaran (nama_metode, nomor_rekening, gambar_metode, atas_nama) 
                      VALUES ('$Nama_Metode', '$Nomor_Rekening', '$new_file_name', '$Atas_Nama')";

            if (mysqli_query($koneksi, $query)) {
                header("Location: metode.php?pesan=input");
                exit;
            } else {
                echo "Error saat memasukkan data ke database: " . mysqli_error($koneksi);
                exit;
            }

        } else {
            echo "Gagal mengupload gambar.";
            exit;
        }
    } else {
        echo "Format gambar tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.";
        exit;
    }
} else {
    echo "Gambar belum diupload atau terjadi kesalahan saat upload.";
    exit;
}
?>