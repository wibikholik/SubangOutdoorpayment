<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    header('Location: ../login.php');
    exit;
}

include '../route/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $status_baru = isset($_POST['status_baru']) ? trim($_POST['status_baru']) : '';
    $target_table = isset($_POST['target_table']) ? trim($_POST['target_table']) : '';

    // Daftar status valid per tabel
    $valid_status = [
        'transaksi' => [
            'menunggu konfirmasi pembayaran',
            'Dikonfirmasi Pembayaran Silahkan AmbilBarang',
            'Ditolak Pembayaran',
            'selesai pembayaran',
            'disewa',
            'terlambat dikembalikan',
            'menunggu konfirmasi pengembalian',
            'ditolak pengembalian',
            'selesai dikembalikan',
            'batal'
        ],
        'pembayaran' => [
            'Menunggu Konfirmasi Pembayaran',
            'Dikonfirmasi Pembayaran Silahkan AmbilBarang',
            'Ditolak Pembayaran',
            'selesai pembayaran'
        ],
        'pengembalian' => [
            'menunggu konfirmasi pengembalian',
            'selesai dikembalikan',
            'ditolak pengembalian'
        ],
    ];

    // Validasi id dan nama tabel
    if ($id <= 0 || !array_key_exists($target_table, $valid_status)) {
        die('Input ID atau tabel tidak valid.');
    }

    // Validasi status baru
    if (!in_array(strtolower($status_baru), array_map('strtolower', $valid_status[$target_table]), true)) {
        die('Status baru tidak valid untuk tabel ' . htmlspecialchars($target_table));
    }

    // Tentukan kolom id sesuai tabel
    $id_column = match ($target_table) {
        'transaksi' => 'id_transaksi',
        'pembayaran' => 'id_pembayaran',
        'pengembalian' => 'id_pengembalian',
        default => ''
    };

    // Tentukan kolom status sesuai tabel
    $status_column = match ($target_table) {
        'pembayaran' => 'status_pembayaran',
        'pengembalian' => 'status_pengembalian',
        default => 'status'
    };

    // Update status utama
    $sql = "UPDATE $target_table SET $status_column = ? WHERE $id_column = ?";
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        die("Gagal prepare statement update utama: " . $koneksi->error);
    }
    $stmt->bind_param("si", $status_baru, $id);
    if (!$stmt->execute()) {
        die("Gagal update status utama: " . $stmt->error);
    }
    $stmt->close();

    // Jika update tabel transaksi, sinkronkan status ke pembayaran dan pengembalian
    if ($target_table === 'transaksi') {
        // Update pembayaran
        $sql_pembayaran = "UPDATE pembayaran SET status_pembayaran = ? WHERE id_transaksi = ?";
        $stmt_pembayaran = $koneksi->prepare($sql_pembayaran);
        if (!$stmt_pembayaran) {
            die("Gagal prepare statement update pembayaran: " . $koneksi->error);
        }
        $stmt_pembayaran->bind_param("si", $status_baru, $id);
        if (!$stmt_pembayaran->execute()) {
            die("Gagal execute update pembayaran: " . $stmt_pembayaran->error);
        }
        $stmt_pembayaran->close();

        // Update pengembalian
        $sql_pengembalian = "UPDATE pengembalian SET status_pengembalian = ? WHERE id_transaksi = ?";
        $stmt_pengembalian = $koneksi->prepare($sql_pengembalian);
        if (!$stmt_pengembalian) {
            die("Gagal prepare statement update pengembalian: " . $koneksi->error);
        }
        $stmt_pengembalian->bind_param("si", $status_baru, $id);
        if (!$stmt_pengembalian->execute()) {
            die("Gagal execute update pengembalian: " . $stmt_pengembalian->error);
        }
        $stmt_pengembalian->close();

        // Jika status baru adalah "Dikonfirmasi Pembayaran Silahkan AmbilBarang" -> kurangi stok dan kirim email konfirmasi ambil barang
        if (strtolower($status_baru) === strtolower('Dikonfirmasi Pembayaran Silahkan AmbilBarang')) {
            // Kurangi stok barang sesuai detail transaksi
            $query_items = "SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?";
            $stmt_items = $koneksi->prepare($query_items);
            if (!$stmt_items) {
                die("Gagal prepare statement ambil detail transaksi: " . $koneksi->error);
            }
            $stmt_items->bind_param("i", $id);
            if (!$stmt_items->execute()) {
                die("Gagal execute query detail transaksi: " . $stmt_items->error);
            }
            $result_items = $stmt_items->get_result();

            while ($row = $result_items->fetch_assoc()) {
                $id_barang = $row['id_barang'];
                $jumlah = $row['jumlah_barang'];

                $update_stok = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
                $stmt_update = $koneksi->prepare($update_stok);
                if (!$stmt_update) {
                    die("Gagal prepare statement update stok barang: " . $koneksi->error);
                }
                $stmt_update->bind_param("ii", $jumlah, $id_barang);
                if (!$stmt_update->execute()) {
                    die("Gagal update stok barang: " . $stmt_update->error);
                }
                $stmt_update->close();
            }
            $stmt_items->close();

            // Ambil data email, nama penyewa dan tanggal kembali
           $query_email = "SELECT p.email, p.nama_penyewa, t.tanggal_kembali 
                FROM transaksi t 
                JOIN penyewa p ON t.id_penyewa = p.id_penyewa
                WHERE t.id_transaksi = ?";
                
            $stmt_email = $koneksi->prepare($query_email);
            if (!$stmt_email) {
                die("Gagal prepare statement ambil data user: " . $koneksi->error);
            }

            $stmt_email->bind_param("i", $id);
            if (!$stmt_email->execute()) {
                die("Gagal execute query ambil data user: " . $stmt_email->error);
            }

            $result_email = $stmt_email->get_result();
            $row_email = $result_email->fetch_assoc();
            $stmt_email->close();

            if ($row_email && !empty($row_email['email']) && filter_var($row_email['email'], FILTER_VALIDATE_EMAIL)) {
                $email = $row_email['email'];
                $nama_penyewa = $row_email['nama_penyewa'];
                $tanggal_kembali = $row_email['tanggal_kembali'];

                // Kirim email konfirmasi ambil barang
               $mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = 2; // debug SMTP
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username   = 'subangoutdoortes@gmail.com';
    $mail->Password   = 'sbsn ajtg fgox otra';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
    $mail->addAddress($email, $nama_penyewa);

    $mail->isHTML(true);
    $mail->Subject = 'Barang Siap Diambil';
    $mail->Body = "
        <h3>Halo, $nama_penyewa!</h3>
        <p>Pesanan Anda telah <strong>dikonfirmasi</strong> dan barang sudah siap diambil.</p>
        <p>Silakan segera datang untuk mengambil barang sewaan Anda.</p>
        <p>Terima kasih telah menggunakan layanan kami.</p>
        <br>
        <small>Subang Outdoor Team</small>
    ";

    $mail->send();
} catch (Exception $e) {
    error_log("Gagal mengirim email ambil barang: {$mail->ErrorInfo}");
}
            }
        }

        // Jika status baru adalah "disewa", cek apakah hari ini H-1 tanggal kembali untuk kirim email pengingat
        if (strtolower($status_baru) === strtolower('disewa')) {
            // Ambil data email, nama penyewa dan tanggal kembali
            $query_email = "SELECT p.email, p.nama_penyewa, t.tanggal_kembali FROM transaksi t 
                            JOIN penyewa p ON t.id_penyewa = p.id_penyewa
                            WHERE t.id_transaksi = ?";
            $stmt_email = $koneksi->prepare($query_email);
            if (!$stmt_email) {
                die("Gagal prepare statement ambil data user: " . $koneksi->error);
            }
            $stmt_email->bind_param("i", $id);
            if (!$stmt_email->execute()) {
                die("Gagal execute query ambil data user: " . $stmt_email->error);
            }
            $result_email = $stmt_email->get_result();
            $row_email = $result_email->fetch_assoc();
            $stmt_email->close();

            if ($row_email) {
                $email = $row_email['email'];
                $nama_penyewa = $row_email['nama_penyewa'];
                $tanggal_kembali = $row_email['tanggal_kembali'];

                $tgl_kembali = new DateTime($tanggal_kembali);
$tgl_kembali->setTime(0, 0, 0); // set jam 00:00:00

$hari_ini = new DateTime();
$hari_ini->setTime(0, 0, 0); // set jam 00:00:00 juga agar akurat

$diff = (int)$hari_ini->diff($tgl_kembali)->format("%r%a");
 // selisih hari (bisa negatif)

                // Kirim email pengingat jika hari ini H-1 (1 hari sebelum tanggal kembali)
                if ($diff === 1) {
                    $mail_reminder = new PHPMailer(true);
                    try {
                        $mail_reminder->isSMTP();
                        $mail_reminder->Host = 'smtp.gmail.com';
                        $mail_reminder->SMTPAuth = true;
                        $mail_reminder->Username   = 'subangoutdoortes@gmail.com';
                        $mail_reminder->Password   = 'sbsn ajtg fgox otra';
                        $mail_reminder->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail_reminder->Port = 587;

                        $mail_reminder->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
                        $mail_reminder->addAddress($email, $nama_penyewa);

                        $mail_reminder->isHTML(true);
                        $mail_reminder->Subject = 'Pengingat Pengembalian Barang Sewa';
                        $mail_reminder->Body = "
                            <h3>Halo, $nama_penyewa!</h3>
                            <p>Ingat, tanggal pengembalian barang sewa Anda adalah besok (<strong>$tanggal_kembali</strong>).</p>
                            <p>Pastikan barang dikembalikan tepat waktu agar tidak dikenakan denda.</p>
                            <br>
                            <small>Subang Outdoor Team</small>
                        ";
                        $mail_reminder->send();
                    } catch (Exception $e) {
                        error_log("Gagal mengirim email pengingat pengembalian: {$mail_reminder->ErrorInfo}");
                    }
                }
            }
        }
    }
// Jika status baru adalah "selesai dikembalikan", kirim email ucapan terima kasih
if (strtolower($status_baru) === strtolower('Selesai Dikembalikan')) {
    // Ambil data email dan nama penyewa
    $query_email_thanks = "SELECT p.email, p.nama_penyewa 
                           FROM transaksi t 
                           JOIN penyewa p ON t.id_penyewa = p.id_penyewa
                           WHERE t.id_transaksi = ?";
    $stmt_email_thanks = $koneksi->prepare($query_email_thanks);
    if (!$stmt_email_thanks) {
        die("Gagal prepare statement ambil data user untuk ucapan terima kasih: " . $koneksi->error);
    }
    $stmt_email_thanks->bind_param("i", $id);
    if (!$stmt_email_thanks->execute()) {
        die("Gagal execute query ambil data user untuk ucapan terima kasih: " . $stmt_email_thanks->error);
    }
    $result_email_thanks = $stmt_email_thanks->get_result();
    $row_email_thanks = $result_email_thanks->fetch_assoc();
    $stmt_email_thanks->close();

    if ($row_email_thanks && !empty($row_email_thanks['email']) && filter_var($row_email_thanks['email'], FILTER_VALIDATE_EMAIL)) {
        $email_thanks = $row_email_thanks['email'];
        $nama_penyewa_thanks = $row_email_thanks['nama_penyewa'];

        $mail_thanks = new PHPMailer(true);
        try {
            $mail_thanks->isSMTP();
            $mail_thanks->Host = 'smtp.gmail.com';
            $mail_thanks->SMTPAuth = true;
            $mail_thanks->Username   = 'subangoutdoortes@gmail.com';
            $mail_thanks->Password   = 'sbsn ajtg fgox otra';
            $mail_thanks->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail_thanks->Port = 587;

            $mail_thanks->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
            $mail_thanks->addAddress($email_thanks, $nama_penyewa_thanks);

            $mail_thanks->isHTML(true);
            $mail_thanks->Subject = 'Terima Kasih Telah Menggunakan Layanan Kami';
            $mail_thanks->Body = "
                <h3>Halo, $nama_penyewa_thanks!</h3>
                <p>Terima kasih telah mengembalikan barang sewa tepat waktu.</p>
                <p>Kami sangat menghargai kepercayaan dan kerjasama Anda.</p>
                <p>Semoga kami dapat melayani Anda kembali di lain kesempatan.</p>
                <br>
                <small>Subang Outdoor Team</small>
            ";

            $mail_thanks->send();
        } catch (Exception $e) {
            error_log("Gagal mengirim email ucapan terima kasih: {$mail_thanks->ErrorInfo}");
        }
    }
}

    // Redirect kembali ke halaman transaksi dengan pesan sukses
    header('Location: transaksi.php?status=success');
    exit;
} else {
    die('Akses tidak diizinkan.');
}
    