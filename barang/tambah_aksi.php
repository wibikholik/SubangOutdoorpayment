<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php'; 

$folder_upload = "barang/gambar/";

// Buat folder jika belum ada
if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0755, true);
}

// Ambil data dari form dan sanitasi dasar
$Nama_Barang = trim($_POST['nama_barang']);
$Keterangan = trim($_POST['keterangan']);
$KategoriID = $_POST['id_kategori'];
$Stok = (int) $_POST['stok'];
$Harga_Barang = (float) $_POST['harga_sewa'];
$Unggulan = isset($_POST['unggulan']) && $_POST['unggulan'] == '1' ? 1 : 0;

// Validasi sederhana
if ($Nama_Barang === '' || $Keterangan === '' || empty($KategoriID) || $Stok < 0 || $Harga_Barang < 0) {
    echo "Data tidak valid. Mohon periksa kembali inputan Anda.";
    exit;
}

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
    $file_tmp = $_FILES['gambar']['tmp_name'];
    $file_name = basename($_FILES['gambar']['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    // Cek ukuran file (batas 2MB)
    $max_size = 2 * 1024 * 1024; // 2MB dalam byte
    if ($_FILES['gambar']['size'] > $max_size) {
        echo "File gambar terlalu besar. Maksimal ukuran file adalah 2MB.";
        exit;
    }

    if (in_array($file_ext, $allowed_ext)) {
        $new_file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "", $file_name);
        $target_file = $folder_upload . $new_file_name;

        if (move_uploaded_file($file_tmp, $target_file)) {
            // Prepared statement untuk insert data
            $stmt = $koneksi->prepare("INSERT INTO barang (Nama_Barang, Keterangan, Gambar, Stok, Harga_Sewa, id_kategori, unggulan) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                echo "Prepare statement error: " . htmlspecialchars($koneksi->error);
                exit;
            }

            $stmt->bind_param("sssdisi", $Nama_Barang, $Keterangan, $new_file_name, $Stok, $Harga_Barang, $KategoriID, $Unggulan);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: barang.php?pesan=input");
                exit;
            } else {
                echo "Error saat memasukkan data: " . htmlspecialchars($stmt->error);
                $stmt->close();
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
