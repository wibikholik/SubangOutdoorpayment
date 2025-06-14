<?php
session_start();
include '../../route/koneksi.php';

require '../../vendor/autoload.php';  // PHPMailer & Midtrans autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Midtrans\Config;
use Midtrans\Snap;

// Cek user login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = $_SESSION['user_id'];

// Cek metode request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Akses tidak diizinkan.'); window.location.href='../page/produk.php';</script>";
    exit;
}

// Ambil data POST dengan sanitasi sederhana
$id_metode = $_POST['id_metode'] ?? null;
$selected_items = $_POST['items'] ?? []; // array [cart_id => jumlah]
$tanggal_sewa = $_POST['tanggal_sewa'] ?? null;
$tanggal_kembali = $_POST['tanggal_kembali'] ?? null;

// Validasi wajib
if (!$id_metode || empty($selected_items) || !$tanggal_sewa || !$tanggal_kembali) {
    echo "<script>alert('Data tidak lengkap. Silakan pilih barang, metode pembayaran, dan isi tanggal sewa & kembali.'); window.history.back();</script>";
    exit;
}

// Validasi tanggal sewa dan kembali
$ts_sewa = strtotime($tanggal_sewa);
$ts_kembali = strtotime($tanggal_kembali);
if (!$ts_sewa || !$ts_kembali || $ts_kembali <= $ts_sewa) {
    echo "<script>alert('Tanggal kembali harus lebih dari tanggal sewa.'); window.history.back();</script>";
    exit;
}
$lama_sewa = ceil(($ts_kembali - $ts_sewa) / (60 * 60 * 24));

// Siapkan placeholders untuk prepared statement
$selected_ids = array_keys($selected_items);
$selected_ids_int = array_map('intval', $selected_ids);
$ids_placeholders = implode(',', array_fill(0, count($selected_ids_int), '?'));

// Ambil data carts dan harga dari DB (validasi kepemilikan dan data)
$sql = "SELECT c.id AS cart_id, c.id_barang, c.jumlah, b.harga_sewa AS harga 
        FROM carts c
        JOIN barang b ON c.id_barang = b.id_barang
        WHERE c.id_penyewa = ? AND c.id IN ($ids_placeholders)";
$stmt = mysqli_prepare($koneksi, $sql);
$params = array_merge([$id_penyewa], $selected_ids_int);
$types = str_repeat('i', count($params));
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items_db = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items_db[$row['cart_id']] = $row;
}

// Validasi semua data cart ada
if (count($items_db) !== count($selected_ids_int)) {
    echo "<script>alert('Data keranjang tidak valid.'); window.history.back();</script>";
    exit;
}

// Ambil nama metode pembayaran
$sqlMetode = "SELECT nama_metode FROM metode_pembayaran WHERE id_metode = ?";
$stmtMetode = mysqli_prepare($koneksi, $sqlMetode);
mysqli_stmt_bind_param($stmtMetode, "i", $id_metode);
mysqli_stmt_execute($stmtMetode);
$resultMetode = mysqli_stmt_get_result($stmtMetode);
$metodeData = mysqli_fetch_assoc($resultMetode);
if (!$metodeData) {
    echo "<script>alert('Metode pembayaran tidak ditemukan.'); window.history.back();</script>";
    exit;
}
$nama_metode = strtolower(trim($metodeData['nama_metode']));

// Mulai transaksi DB
mysqli_begin_transaction($koneksi);

try {
    // Hitung total harga
    $total_harga = 0;
    foreach ($selected_ids_int as $cart_id) {
        $item = $items_db[$cart_id];
        $jumlah_pilih = (int)$selected_items[$cart_id];
        if ($jumlah_pilih <= 0 || $jumlah_pilih > $item['jumlah']) {
            throw new Exception("Jumlah barang tidak valid untuk cart ID: $cart_id");
        }
        $total_harga += $item['harga'] * $jumlah_pilih * $lama_sewa;
    }

    // Tentukan status awal transaksi
    if ($nama_metode === 'bayar langsung') {
        $status_transaksi = 'menunggu konfirmasi pesanan';
    } else {
        $status_transaksi = 'belumbayar';
    }

    // Insert transaksi
    $stmtTransaksi = mysqli_prepare($koneksi, "INSERT INTO transaksi (id_penyewa, total_harga_sewa, status, id_metode, tanggal_sewa, tanggal_kembali) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmtTransaksi, "idsiss", $id_penyewa, $total_harga, $status_transaksi, $id_metode, $tanggal_sewa, $tanggal_kembali);
    if (!mysqli_stmt_execute($stmtTransaksi)) {
        throw new Exception("Gagal menyimpan transaksi.");
    }
    $id_transaksi = mysqli_insert_id($koneksi);

    // Insert detail transaksi
    $stmtDetail = mysqli_prepare($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_barang, jumlah_barang, harga_satuan) VALUES (?, ?, ?, ?)");
    foreach ($selected_ids_int as $cart_id) {
        $item = $items_db[$cart_id];
        $id_barang = (int) $item['id_barang'];
        $jumlah = (int) $selected_items[$cart_id];
        $harga_per_hari = (float) $item['harga'];

        mysqli_stmt_bind_param($stmtDetail, "iiid", $id_transaksi, $id_barang, $jumlah, $harga_per_hari);
        if (!mysqli_stmt_execute($stmtDetail)) {
            throw new Exception("Gagal menyimpan detail transaksi untuk barang ID: $id_barang");
        }
    }

    // Hapus item dari carts
    $hapusSQL = "DELETE FROM carts WHERE id_penyewa = ? AND id IN ($ids_placeholders)";
    $hapusStmt = mysqli_prepare($koneksi, $hapusSQL);
    $hapusParams = array_merge([$id_penyewa], $selected_ids_int);
    $hapusTypes = str_repeat('i', count($hapusParams));
    mysqli_stmt_bind_param($hapusStmt, $hapusTypes, ...$hapusParams);
    if (!mysqli_stmt_execute($hapusStmt)) {
        throw new Exception("Gagal menghapus item dari keranjang.");
    }

    // Proses bayar langsung: commit dan kirim email
    if ($nama_metode === 'bayar langsung') {
        mysqli_commit($koneksi);

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'subangoutdoortes@gmail.com';  // ganti dengan email kamu
            $mail->Password   = 'sbsn ajtg fgox otra';          // ganti dengan password aplikasi
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');

            // Kirim ke admin dan owner
            $emails = [];
            $admin_result = $koneksi->query("SELECT email FROM admin");
            if ($admin_result) {
                while ($row = $admin_result->fetch_assoc()) {
                    if (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                        $emails[] = $row['email'];
                    }
                }
            }
            $owner_result = $koneksi->query("SELECT email FROM owner");
            if ($owner_result) {
                while ($row = $owner_result->fetch_assoc()) {
                    if (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                        $emails[] = $row['email'];
                    }
                }
            }

            if (!empty($emails)) {
                foreach ($emails as $email) {
                    $mail->addAddress($email);
                }
                $mail->isHTML(true);
                $mail->Subject = 'Pesanan Baru Menunggu Konfirmasi Pesanan';
                $mail->Body    = "
                    <h4>Pesanan Baru dari Penyewa dengan metode pembayaran bayar langsung</h4>
                    <p><strong>ID Transaksi:</strong> {$id_transaksi}</p>
                    <p>Status saat ini: <strong>{$status_transaksi}</strong></p>
                    <p>Silakan login untuk verifikasi dan proses lebih lanjut.</p>
                ";
                $mail->send();
            }
        } catch (Exception $e) {
            error_log("Gagal mengirim email notifikasi: " . $mail->ErrorInfo);
        }

        header("Location: ../page/transaksi.php");
        exit;
    }

    // Jika bukan bayar langsung, proses Midtrans snap token
    Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I'; // ganti dengan server key Midtrans kamu
    Config::$isProduction = false;
    Config::$isSanitized = true;
    Config::$is3ds = true;

    $transaction_details = [
        'order_id' => "TRX-$id_transaksi",  // lebih baik buat prefix supaya unik dan jelas
        'gross_amount' => $total_harga,
    ];

    $item_details = [];
    foreach ($selected_ids_int as $cart_id) {
        $item = $items_db[$cart_id];
        $jumlah = (int) $selected_items[$cart_id];
        $item_details[] = [
            'id' => (string) $cart_id,
            'price' => (float) $item['harga'],
            'quantity' => $jumlah * $lama_sewa,
            'name' => "Barang ID " . $item['id_barang'],
        ];
    }

    $params = [
        'transaction_details' => $transaction_details,
        'item_details' => $item_details,
        'customer_details' => [
            'id' => $id_penyewa,
            // Bisa tambahkan 'email' & 'phone' jika tersedia dari session/database
        ],
    ];

    try {
        $snapToken = Snap::getSnapToken($params);
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo "<script>alert('Gagal generate token Midtrans: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }

    // Simpan snap token ke transaksi
    $stmtUpdateToken = mysqli_prepare($koneksi, "UPDATE transaksi SET snap_token = ? WHERE id_transaksi = ?");
    mysqli_stmt_bind_param($stmtUpdateToken, "si", $snapToken, $id_transaksi);
    mysqli_stmt_execute($stmtUpdateToken);

    mysqli_commit($koneksi);

    // Redirect ke halaman pembayaran Midtrans
    header("Location: ../page/pembayaran.php?id_transaksi=$id_transaksi&token=$snapToken");
    exit;

} catch (Exception $ex) {
    mysqli_rollback($koneksi);
    echo "<script>alert('Terjadi kesalahan: " . $ex->getMessage() . "'); window.history.back();</script>";
    exit;
}
