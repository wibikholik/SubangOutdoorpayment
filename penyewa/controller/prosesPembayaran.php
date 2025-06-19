<?php
session_start();
include '../../route/koneksi.php';
require '../../vendor/autoload.php';  // Autoload PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Validasi request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Akses tidak valid.'); window.location.href='../../index.php';</script>";
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = $_SESSION['user_id'];
$id_transaksi = $_POST['id_transaksi'] ?? null;
$id_metode = $_POST['id_metode'] ?? null;

if (!$id_transaksi || !$id_metode) {
    echo "<script>alert('Data tidak lengkap.'); window.history.back();</script>";
    exit;
}

// Cek apakah transaksi ini milik penyewa
$stmt = $koneksi->prepare("SELECT tanggal_sewa, tanggal_kembali FROM transaksi WHERE id_transaksi = ? AND id_penyewa = ?");
$stmt->bind_param("ii", $id_transaksi, $id_penyewa);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Transaksi tidak ditemukan atau bukan milik Anda.'); window.location.href='../page/transaksi.php';</script>";
    exit;
}
$data = $result->fetch_assoc();
$tanggal_sewa = $data['tanggal_sewa'];
$tanggal_kembali = $data['tanggal_kembali'];
$stmt->close();

// Validasi file upload
if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
    echo "<script>alert('Gagal mengunggah bukti pembayaran.'); window.history.back();</script>";
    exit;
}

$file = $_FILES['bukti_pembayaran'];
$allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$max_size = 5 * 1024 * 1024;

if (!in_array($ext, $allowed_ext)) {
    echo "<script>alert('Format file tidak diizinkan. Hanya JPG, PNG, PDF.'); window.history.back();</script>";
    exit;
}
if ($file['size'] > $max_size) {
    echo "<script>alert('Ukuran file terlalu besar. Maksimal 5MB.'); window.history.back();</script>";
    exit;
}

// Upload bukti
$upload_dir = '../../uploads/bukti/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
$new_filename = 'bukti_' . $id_transaksi . '_' . time() . '.' . $ext;
$target_file = $upload_dir . $new_filename;

if (!move_uploaded_file($file['tmp_name'], $target_file)) {
    echo "<script>alert('Gagal menyimpan file.'); window.history.back();</script>";
    exit;
}

// Simpan ke pembayaran & update transaksi
$tanggal_pembayaran = date('Y-m-d H:i:s');
$status_pembayaran = 'Menunggu Konfirmasi Pembayaran';

// Update metode dan status di transaksi
$stmt_update = $koneksi->prepare("UPDATE transaksi SET id_metode = ?, status = ? WHERE id_transaksi = ?");
$stmt_update->bind_param("isi", $id_metode, $status_pembayaran, $id_transaksi);
$stmt_update->execute();
$stmt_update->close();

// Insert ke tabel pembayaran
$stmt_insert = $koneksi->prepare("
    INSERT INTO pembayaran (id_transaksi, id_metode, tanggal_pembayaran, bukti_pembayaran, status_pembayaran)
    VALUES (?, ?, ?, ?, ?)
");
$stmt_insert->bind_param("iisss", $id_transaksi, $id_metode, $tanggal_pembayaran, $new_filename, $status_pembayaran);
$stmt_insert->execute();
$stmt_insert->close();

// Kirim notifikasi email ke admin dan owner
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'subangoutdoortes@gmail.com';
    $mail->Password   = 'sbsn ajtg fgox otra'; // App password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
    $mail->isHTML(true);
    $mail->Subject = 'Pembayaran Baru dari Penyewa';
    $mail->Body = "
        <h4>Pembayaran Baru Diupload</h4>
        <p><strong>ID Transaksi:</strong>$id_transaksi</p>
        <p><strong>Tanggal Sewa:</strong> $tanggal_sewa</p>
        <p><strong>Tanggal Kembali:</strong> $tanggal_kembali</p>
        <p>Status: <strong>$status_pembayaran</strong></p>
        <p>Silakan login ke sistem untuk melakukan verifikasi.</p>
    ";

    $emails = [];
    $result_admin = $koneksi->query("SELECT email FROM admin");
    while ($row = $result_admin->fetch_assoc()) {
        if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $emails[] = $row['email'];
        }
    }

    $result_owner = $koneksi->query("SELECT email FROM owner");
    while ($row = $result_owner->fetch_assoc()) {
        if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $emails[] = $row['email'];
        }
    }

    foreach ($emails as $email) {
        $mail->addAddress($email);
    }

    $mail->send();
} catch (Exception $e) {
    error_log("Email error: " . $mail->ErrorInfo);
}

echo "<script>alert('Bukti berhasil diupload. Menunggu konfirmasi admin.'); window.location.href='../page/transaksi.php';</script>";
exit;
?>
