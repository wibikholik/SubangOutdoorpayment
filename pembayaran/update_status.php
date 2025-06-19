<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Akses tidak diizinkan.');
}

$id_pembayaran = isset($_POST['id_pembayaran']) ? (int) $_POST['id_pembayaran'] : 0;
$status_baru = trim($_POST['status_baru'] ?? '');

if ($id_pembayaran <= 0 || $status_baru === '') {
    die('ID atau status tidak valid.');
}

// Ambil data transaksi berdasarkan id_pembayaran
$stmt = $koneksi->prepare("
    SELECT b.id_transaksi, p.email, p.nama_penyewa
    FROM pembayaran b
    JOIN transaksi t ON b.id_transaksi = t.id_transaksi
    JOIN penyewa p ON t.id_penyewa = p.id_penyewa
    WHERE b.id_pembayaran = ?
");
$stmt->bind_param("i", $id_pembayaran);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die('Pembayaran tidak ditemukan.');
}

$id_transaksi = $data['id_transaksi'];
$email = $data['email'];
$nama = $data['nama_penyewa'];

// 1. Update status pembayaran
$stmt = $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = ? WHERE id_pembayaran = ?");
$stmt->bind_param("si", $status_baru, $id_pembayaran);
$stmt->execute();
$stmt->close();

// 2. Jika status dikonfirmasi, update transaksi & kurangi stok & kirim email
if (strtolower($status_baru) === strtolower('dikonfirmasi pembayaran silahkan ambilbarang')) {
    // Update status transaksi
    $stmt = $koneksi->prepare("UPDATE transaksi SET status = ? WHERE id_transaksi = ?");
    $stmt->bind_param("si", $status_baru, $id_transaksi);
    $stmt->execute();
    $stmt->close();

    // Kurangi stok dari detail_transaksi
    $stmt = $koneksi->prepare("SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?");
    $stmt->bind_param("i", $id_transaksi);
    $stmt->execute();
    $result_barang = $stmt->get_result();

    while ($row = $result_barang->fetch_assoc()) {
        $stmt_update = $koneksi->prepare("UPDATE barang SET stok = stok - ? WHERE id_barang = ?");
        $stmt_update->bind_param("ii", $row['jumlah_barang'], $row['id_barang']);
        $stmt_update->execute();
        $stmt_update->close();
    }
    $stmt->close();

    // Kirim email ke penyewa
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'subangoutdoortes@gmail.com';
            $mail->Password = 'sbsn ajtg fgox otra'; // Ganti dengan app password yang aman
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
            $mail->addAddress($email, $nama);
            $mail->isHTML(true);
            $mail->Subject = 'Pesanan Anda Telah Dikonfirmasi';
            $mail->Body = "
                <h3>Halo, $nama!</h3>
                <p>Pembayaran Anda telah <strong>berhasil dikonfirmasi</strong>.</p>
                <p>Barang sewaan sudah <strong>siap diambil</strong> di lokasi kami.</p>
                <p>Silakan ambil barang sesuai jadwal penyewaan yang telah Anda pilih.</p>
                <br>
                <small>Salam hangat,<br>Tim Subang Outdoor</small>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log("Gagal mengirim email: " . $mail->ErrorInfo);
        }
    }
}

$_SESSION['notification'] = "Status pembayaran berhasil diperbarui.";
header('Location: pembayaran.php');
exit;
