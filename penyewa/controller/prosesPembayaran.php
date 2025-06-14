<?php
include '../../route/koneksi.php';
session_start();

require '../../vendor/autoload.php';  // Autoload PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Pastikan hanya menerima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak valid.");
}

// Cek session login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

$id_penyewa = $_SESSION['user_id'];
$id_transaksi = $_POST['id_transaksi'] ?? null;

if (!$id_transaksi || !$id_penyewa) {
    die("Data tidak lengkap.");
}

// Ambil data transaksi milik penyewa
$stmt = $koneksi->prepare("SELECT id_metode, tanggal_sewa, tanggal_kembali FROM transaksi WHERE id_transaksi = ? AND id_penyewa = ?");
if (!$stmt) {
    die("Gagal prepare query: " . $koneksi->error);
}
$stmt->bind_param("ii", $id_transaksi, $id_penyewa);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Transaksi tidak ditemukan.");
}
$data = $result->fetch_assoc();
$id_metode = $data['id_metode'];
$tanggal_sewa = htmlspecialchars($data['tanggal_sewa'] ?? '-');
$tanggal_kembali = htmlspecialchars($data['tanggal_kembali'] ?? '-');
$stmt->close();

// Validasi file upload bukti pembayaran
if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
    die("Gagal mengunggah bukti pembayaran.");
}

$file = $_FILES['bukti_pembayaran'];
$allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Cek ekstensi file
if (!in_array($ext, $allowed_ext)) {
    die("Format file tidak diizinkan. Hanya JPG, JPEG, PNG, dan PDF yang diperbolehkan.");
}

// Cek ukuran file maksimal 5 MB
$max_size = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $max_size) {
    die("Ukuran file terlalu besar. Maksimal 5MB.");
}

// Folder upload
$upload_dir = '../../uploads/bukti/';
if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
    die("Gagal membuat folder upload.");
}

// Buat nama file unik
$new_filename = 'bukti_' . $id_transaksi . '_' . time() . '.' . $ext;
$target_file = $upload_dir . $new_filename;

// Pindahkan file ke folder upload
if (!move_uploaded_file($file['tmp_name'], $target_file)) {
    die("Gagal menyimpan file ke server.");
}

$tanggal_pembayaran = date('Y-m-d H:i:s');
$status_pembayaran = 'Menunggu Konfirmasi Pembayaran';

// Update status transaksi
$update_status = $koneksi->prepare("UPDATE transaksi SET status = ? WHERE id_transaksi = ?");
if (!$update_status) {
    die("Gagal prepare update status: " . $koneksi->error);
}
$update_status->bind_param("si", $status_pembayaran, $id_transaksi);
$update_status->execute();
$update_status->close();

// Simpan data pembayaran ke tabel pembayaran
$stmt_insert = $koneksi->prepare("
    INSERT INTO pembayaran (id_transaksi, id_metode, tanggal_pembayaran, bukti_pembayaran, status_pembayaran)
    VALUES (?, ?, ?, ?, ?)
");
if (!$stmt_insert) {
    die("Gagal prepare insert pembayaran: " . $koneksi->error);
}
$stmt_insert->bind_param("iisss", $id_transaksi, $id_metode, $tanggal_pembayaran, $new_filename, $status_pembayaran);

if ($stmt_insert->execute()) {
    // Kirim notifikasi email
    $mail = new PHPMailer(true);

    try {
        // Konfigurasi SMTP
        $mail->SMTPDebug = 0;  // Nonaktifkan debug saat produksi
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'subangoutdoortes@gmail.com';  // Ganti sesuai email pengirim Anda
        $mail->Password   = 'sbsn ajtg fgox otra';      // Ganti dengan app password Gmail yang valid
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');

        // Ambil email admin
        $emails = [];
        $admin_result = $koneksi->query("SELECT email FROM admin");
        if ($admin_result) {
            while ($row = $admin_result->fetch_assoc()) {
                if (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $row['email'];
                }
            }
            $admin_result->free();
        } else {
            die("Query admin gagal: " . $koneksi->error);
        }

        // Ambil email owner
        $owner_result = $koneksi->query("SELECT email FROM owner");
        if ($owner_result) {
            while ($row = $owner_result->fetch_assoc()) {
                if (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $row['email'];
                }
            }
            $owner_result->free();
        } else {
            die("Query owner gagal: " . $koneksi->error);
        }

        if (empty($emails)) {
            die("Tidak ada email admin atau owner yang valid ditemukan.");
        }

        // Tambahkan penerima email
        foreach ($emails as $email) {
            $mail->addAddress($email);
        }

        // Isi email
        $mail->isHTML(true);
        $mail->Subject = 'Pembayaran Baru dari Penyewa';
        $mail->Body    = "
            <h4>Penyewa telah meng-upload pembayaran, silakan konfirmasi</h4>
            <p><strong>ID Transaksi:</strong> {$id_transaksi}</p>
            <p><strong>Tanggal Sewa:</strong> {$tanggal_sewa}</p>
            <p><strong>Tanggal Kembali:</strong> {$tanggal_kembali}</p>
            <p><strong>Status Saat Ini:</strong> {$status_pembayaran}</p>
            <p>Silakan login untuk verifikasi dan pemrosesan lebih lanjut.</p>
        ";

        $mail->send();

    } catch (Exception $e) {
        error_log("Gagal mengirim email notifikasi: " . $mail->ErrorInfo);
        die("Gagal mengirim email notifikasi: " . $mail->ErrorInfo);
    }

    echo "<script>alert('Bukti berhasil diupload. Menunggu konfirmasi admin.'); window.location.href='../page/transaksi.php';</script>";
} else {
    echo "Gagal menyimpan data pembayaran: " . $stmt_insert->error;
}

$stmt_insert->close();
$koneksi->close();
?>
