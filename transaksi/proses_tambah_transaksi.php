<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

// Ambil input dari form
$id_penyewa = $_POST['id_penyewa'] ?? '';
$nama_baru = trim($_POST['nama_baru'] ?? '');
$barang_items = $_POST['barang'] ?? []; // Array barang: [ ['id_barang'=>.., 'jumlah'=>..], ... ]
$id_metode = $_POST['id_metode'] ?? '';
$tanggal_sewa = $_POST['tanggal_sewa'] ?? '';
$tanggal_kembali = $_POST['tanggal_kembali'] ?? '';

// Validasi sederhana
if (empty($barang_items) || $id_metode === '' || $tanggal_sewa === '' || $tanggal_kembali === '') {
    die("Data tidak lengkap. Silakan isi semua field yang diperlukan.");
}

// Mulai transaction
mysqli_begin_transaction($koneksi);

try {
    // Jika input penyewa baru tidak kosong, buat penyewa baru dulu
    if ($nama_baru !== '') {
        $nama_baru_escaped = mysqli_real_escape_string($koneksi, $nama_baru);
        $insert_penyewa = "INSERT INTO penyewa (nama_penyewa) VALUES ('$nama_baru_escaped')";
        if (!mysqli_query($koneksi, $insert_penyewa)) {
            throw new Exception("Gagal menyimpan penyewa baru: " . mysqli_error($koneksi));
        }
        $id_penyewa = mysqli_insert_id($koneksi);
    }

    // Jika penyewa tidak dipilih dan tidak input baru, error
    if ($id_penyewa === '') {
        throw new Exception("Penyewa harus dipilih atau diinput baru.");
    }

    // Hitung total harga sewa keseluruhan
    $total_harga_sewa = 0;
    foreach ($barang_items as $item) {
        $id_barang = $item['id_barang'] ?? '';
        $jumlah_barang = (int)($item['jumlah'] ?? 0);

        if ($id_barang === '' || $jumlah_barang < 1) {
            throw new Exception("Data barang tidak valid.");
        }

        // Ambil harga satuan dan stok barang dari database
        $query_harga = "SELECT harga_sewa, stok FROM barang WHERE id_barang = '".mysqli_real_escape_string($koneksi, $id_barang)."'";
        $result_harga = mysqli_query($koneksi, $query_harga);
        if (!$result_harga || mysqli_num_rows($result_harga) == 0) {
            throw new Exception("Barang dengan ID $id_barang tidak ditemukan.");
        }
        $row_harga = mysqli_fetch_assoc($result_harga);
        $harga_satuan = (int)$row_harga['harga_sewa'];
        $stok_sekarang = (int)$row_harga['stok'];

       if ($stok_sekarang < $jumlah_barang) {
    echo "<script>
            alert('Stok barang dengan ID $id_barang tidak mencukupi. Stok tersedia: $stok_sekarang.');
            window.history.back();
          </script>";
    exit;
}


        $total_harga_sewa += $harga_satuan * $jumlah_barang;
    }

    // Simpan ke tabel transaksi
    $insert_transaksi = "
        INSERT INTO transaksi (id_penyewa, total_harga_sewa, status, tanggal_sewa, tanggal_kembali, id_metode)
        VALUES ('".mysqli_real_escape_string($koneksi, $id_penyewa)."', '$total_harga_sewa', 'menunggu konfirmasi pembayaran', 
                '".mysqli_real_escape_string($koneksi, $tanggal_sewa)."', '".mysqli_real_escape_string($koneksi, $tanggal_kembali)."', '".mysqli_real_escape_string($koneksi, $id_metode)."')
    ";

    if (!mysqli_query($koneksi, $insert_transaksi)) {
        throw new Exception("Gagal menyimpan transaksi: " . mysqli_error($koneksi));
    }
    $id_transaksi = mysqli_insert_id($koneksi);

    // Simpan detail transaksi dan kurangi stok barang
    foreach ($barang_items as $item) {
        $id_barang = $item['id_barang'];
        $jumlah_barang = (int)$item['jumlah'];

        // Ambil harga satuan barang lagi (untuk simpan detail)
        $query_harga = "SELECT harga_sewa, stok FROM barang WHERE id_barang = '".mysqli_real_escape_string($koneksi, $id_barang)."'";
        $result_harga = mysqli_query($koneksi, $query_harga);
        $row_harga = mysqli_fetch_assoc($result_harga);
        $harga_satuan = (int)$row_harga['harga_sewa'];
        $stok_sekarang = (int)$row_harga['stok'];

       if ($stok_sekarang < $jumlah_barang) {
    echo "<script>
            alert('Stok barang dengan ID $id_barang tidak mencukupi saat simpan detail.');
            window.history.back();
          </script>";
    exit;
}


        $insert_detail = "
            INSERT INTO detail_transaksi (id_transaksi, id_barang, jumlah_barang, harga_satuan)
            VALUES ('$id_transaksi', '".mysqli_real_escape_string($koneksi, $id_barang)."', '$jumlah_barang', '$harga_satuan')
        ";

        if (!mysqli_query($koneksi, $insert_detail)) {
            throw new Exception("Gagal menyimpan detail transaksi: " . mysqli_error($koneksi));
        }

        // Kurangi stok barang di tabel barang
        $update_stok = "
            UPDATE barang 
            SET stok = stok - $jumlah_barang 
            WHERE id_barang = '".mysqli_real_escape_string($koneksi, $id_barang)."' AND stok >= $jumlah_barang
        ";
        $result_update = mysqli_query($koneksi, $update_stok);
if (!$result_update || mysqli_affected_rows($koneksi) == 0) {
    echo "<script>
            alert('Gagal mengurangi stok barang dengan ID $id_barang. Mungkin stok tidak mencukupi.');
            window.history.back();
          </script>";
    exit;
}

    }

    // Commit transaction jika semua berhasil
    mysqli_commit($koneksi);

    // Redirect ke halaman daftar transaksi dengan pesan sukses
    header("Location: transaksi.php?status=success");
    exit;

} catch (Exception $e) {
    // Rollback jika terjadi error
    mysqli_rollback($koneksi);
    die("Terjadi kesalahan: " . $e->getMessage());
}
?>
