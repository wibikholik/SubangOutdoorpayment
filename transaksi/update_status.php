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

    $valid_status = [
        'transaksi' => [
            'menunggu konfirmasi pembayaran',
            'dikonfirmasi pembayaran silahkan ambilbarang',
            'ditolak pembayaran',
            'selesai pembayaran',
            'disewa',
            'terlambat dikembalikan',
            'batal' // Tambahan penting!
        ],
        'pembayaran' => [
            'menunggu konfirmasi pembayaran',
            'dikonfirmasi pembayaran silahkan ambilbarang',
            'ditolak pembayaran',
            'selesai pembayaran'
        ]
    ];

    if ($id <= 0 || !array_key_exists($target_table, $valid_status)) {
        die('Input ID atau nama tabel tidak valid.');
    }

    if (!in_array(strtolower($status_baru), array_map('strtolower', $valid_status[$target_table]), true)) {
        die('Status baru tidak valid untuk tabel ' . htmlspecialchars($target_table));
    }

    $id_column = $target_table === 'transaksi' ? 'id_transaksi' : 'id_pembayaran';
    $status_column = $target_table === 'pembayaran' ? 'status_pembayaran' : 'status';

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

    if ($target_table === 'transaksi') {
        // Sinkronkan ke pembayaran
        $sql_pembayaran = "UPDATE pembayaran SET status_pembayaran = ? WHERE id_transaksi = ?";
        $stmt_pembayaran = $koneksi->prepare($sql_pembayaran);
        if ($stmt_pembayaran) {
            $stmt_pembayaran->bind_param("si", $status_baru, $id);
            $stmt_pembayaran->execute();
            $stmt_pembayaran->close();
        }

        // === STOK DIKURANGI ===
        if (strtolower($status_baru) === 'dikonfirmasi pembayaran silahkan ambilbarang') {
            $query_items = "SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?";
            $stmt_items = $koneksi->prepare($query_items);
            $stmt_items->bind_param("i", $id);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();

            while ($row = $result_items->fetch_assoc()) {
                $id_barang = $row['id_barang'];
                $jumlah = $row['jumlah_barang'];
                $update_stok = "UPDATE barang SET stok = stok - ? WHERE id_barang = ?";
                $stmt_update = $koneksi->prepare($update_stok);
                $stmt_update->bind_param("ii", $jumlah, $id_barang);
                $stmt_update->execute();
                $stmt_update->close();
            }
            $stmt_items->close();

            // Kirim email konfirmasi
            $query_email = "SELECT p.email, p.nama_penyewa FROM transaksi t JOIN penyewa p ON t.id_penyewa = p.id_penyewa WHERE t.id_transaksi = ?";
            $stmt_email = $koneksi->prepare($query_email);
            $stmt_email->bind_param("i", $id);
            $stmt_email->execute();
            $result_email = $stmt_email->get_result();
            $row_email = $result_email->fetch_assoc();
            $stmt_email->close();

            if ($row_email && filter_var($row_email['email'], FILTER_VALIDATE_EMAIL)) {
                $email = $row_email['email'];
                $nama = $row_email['nama_penyewa'];

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'subangoutdoortes@gmail.com';
                    $mail->Password = 'sbsn ajtg fgox otra';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
                    $mail->addAddress($email, $nama);
                    $mail->isHTML(true);
                    $mail->Subject = 'Barang Siap Diambil';
                    $mail->Body = "
                        <h3>Halo, $nama!</h3>
                        <p>Pesanan Anda telah <strong>dikonfirmasi</strong> dan barang sudah siap diambil.</p>
                        <p>Silakan segera datang untuk mengambil barang sewaan Anda.</p>
                        <br><small>Subang Outdoor Team</small>
                    ";
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Gagal kirim email: {$mail->ErrorInfo}");
                }
            }
        }

        // === STOK DIKEMBALIKAN ===
        if (strtolower($status_baru) === 'batal') {
            $query_items = "SELECT id_barang, jumlah_barang FROM detail_transaksi WHERE id_transaksi = ?";
            $stmt_items = $koneksi->prepare($query_items);
            $stmt_items->bind_param("i", $id);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();

            while ($row = $result_items->fetch_assoc()) {
                $id_barang = $row['id_barang'];
                $jumlah = $row['jumlah_barang'];
                $update_stok = "UPDATE barang SET stok = stok + ? WHERE id_barang = ?";
                $stmt_update = $koneksi->prepare($update_stok);
                $stmt_update->bind_param("ii", $jumlah, $id_barang);
                $stmt_update->execute();
                $stmt_update->close();
            }
            $stmt_items->close();
        }

        // === PENGINGAT KEMBALI H-1 ===
        if (strtolower($status_baru) === 'disewa') {
            $query = "SELECT p.email, p.nama_penyewa, t.tanggal_kembali FROM transaksi t JOIN penyewa p ON t.id_penyewa = p.id_penyewa WHERE t.id_transaksi = ?";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            if ($data) {
                $email = $data['email'];
                $nama = $data['nama_penyewa'];
                $tanggal_kembali = new DateTime($data['tanggal_kembali']);
                $hari_ini = new DateTime();

                $tanggal_kembali->setTime(0, 0, 0);
                $hari_ini->setTime(0, 0, 0);

                $selisih = (int)$hari_ini->diff($tanggal_kembali)->format('%r%a');
                if ($selisih === 1) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'subangoutdoortes@gmail.com';
                        $mail->Password = 'sbsn ajtg fgox otra';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
                        $mail->addAddress($email, $nama);
                        $mail->isHTML(true);
                        $mail->Subject = 'Pengingat Pengembalian Barang';
                        $mail->Body = "
                            <h3>Halo, $nama!</h3>
                            <p>Jangan lupa, barang sewaan Anda harus dikembalikan besok (<strong>{$tanggal_kembali->format('d-m-Y')}</strong>).</p>
                            <p>Pastikan untuk mengembalikannya tepat waktu agar tidak terkena denda.</p>
                            <br><small>Subang Outdoor Team</small>
                        ";
                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Gagal kirim pengingat: {$mail->ErrorInfo}");
                    }
                }
            }
        }
    }

    header('Location: transaksi.php?status=success');
    exit;
} else {
    die('Akses tidak diizinkan.');
}
