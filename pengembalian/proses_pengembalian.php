<?php
session_start();
include '../route/koneksi.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Midtrans\Snap;

require '../vendor/autoload.php'; // autoload PHPMailer dan Midtrans

// Midtrans Config
\Midtrans\Config::$serverKey = 'SB-Mid-server-uoPEq3SC9p0gqrxbhowIBB_I';
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header("Location: ../login.php?message=access_denied");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak diizinkan.");
}

// Ambil data dari form
$id_pengembalian = intval($_POST['id_pengembalian']);
$status_pengembalian = $_POST['status_pengembalian'];
$denda = intval($_POST['denda']);
$catatan = mysqli_real_escape_string($koneksi, $_POST['catatan']);

// Update data pengembalian
$query_update = "UPDATE pengembalian 
          SET status_pengembalian = '$status_pengembalian',
              denda = $denda,
              catatan = '$catatan'
          WHERE id_pengembalian = $id_pengembalian";

if (!mysqli_query($koneksi, $query_update)) {
    die("Gagal mengupdate data pengembalian: " . mysqli_error($koneksi));
}

// Ambil id_transaksi
$query_id_transaksi = "SELECT id_transaksi FROM pengembalian WHERE id_pengembalian = $id_pengembalian";
$result = mysqli_query($koneksi, $query_id_transaksi);
$row = mysqli_fetch_assoc($result);
$id_transaksi = $row['id_transaksi'];

// Ambil data email, nama penyewa, dan tanggal kembali
$query_email = "SELECT p.email, p.nama_penyewa, t.tanggal_kembali 
                FROM transaksi t 
                JOIN penyewa p ON t.id_penyewa = p.id_penyewa
                WHERE t.id_transaksi = ?";
                
$stmt_email = $koneksi->prepare($query_email);
if (!$stmt_email) {
    die("Gagal prepare statement ambil data user: " . $koneksi->error);
}

$stmt_email->bind_param("i", $id_transaksi);
if (!$stmt_email->execute()) {
    die("Gagal execute query ambil data user: " . $stmt_email->error);
}

$result_email = $stmt_email->get_result();
$row_email = $result_email->fetch_assoc();
$stmt_email->close();

$email = $row_email['email'];
$nama_penyewa = $row_email['nama_penyewa'];
$tanggal_kembali = $row_email['tanggal_kembali'];

// Kirim email ke penyewa
if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username   = 'subangoutdoortes@gmail.com'; // ubah sesuai emailmu
        $mail->Password   = 'sbsn ajtg fgox otra'; // ubah ke app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
        $mail->addAddress($email, $nama_penyewa);

        $mail->isHTML(true);

        if ($denda > 0) {
            $mail->Subject = 'Konfirmasi Pengembalian dan Denda';
            $mail->Body = "
                <h3>Halo, $nama_penyewa!</h3>
                <p>Pengembalian pesanan Anda telah <strong>$status_pengembalian</strong>.</p>
                <p>Namun terdapat denda sebesar <strong>Rp" . number_format($denda, 0, ',', '.') . "</strong> yang harus Anda bayar.</p>
                <p>Silakan segera selesaikan pembayaran denda ini melalui sistem.</p>
                <br><small>Subang Outdoor Team</small>
            ";
        } else {
            $mail->Subject = 'Terima Kasih atas Pengembalian Barang';
            $mail->Body = "
                <h3>Halo, $nama_penyewa!</h3>
                <p>Pengembalian pesanan Anda telah <strong>$status_pengembalian</strong>.</p>
                <p>Terima kasih telah mengembalikan barang tepat waktu dan dalam kondisi baik.</p>
                <br><small>Subang Outdoor Team</small>
            ";
        }

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }
}

// Jika ada denda, buat Snap Token dan simpan ke pengembalian
if ($denda > 0) {
    $order_id = "DENDA-" . $id_pengembalian . "-" . time();
    $params = [
        'transaction_details' => [
            'order_id' => $order_id,
            'gross_amount' => $denda,
        ],
        'customer_details' => [
            'first_name' => $nama_penyewa,
            'email' => $email,
        ],
        'item_details' => [[
            'id' => "denda-" . $id_pengembalian,
            'price' => $denda,
            'quantity' => 1,
            'name' => 'Pembayaran Denda Pengembalian',
        ]]
    ];

    try {
        $snapToken = Snap::getSnapToken($params);

        // Simpan Snap Token ke pengembalian
        $stmtSnap = $koneksi->prepare("UPDATE pengembalian SET snap_token = ? WHERE id_pengembalian = ?");
        $stmtSnap->bind_param("si", $snapToken, $id_pengembalian);
        $stmtSnap->execute();
        $stmtSnap->close();
    } catch (Exception $e) {
        error_log("Gagal generate Snap Token: " . $e->getMessage());
    }
}

// Update status transaksi
$status_transaksi_baru = null;
if ($status_pengembalian === 'Selesai Dikembalikan') {
    $status_transaksi_baru = 'Selesai Dikembalikan';
} elseif ($status_pengembalian === 'Ditolak Pengembalian') {
    $status_transaksi_baru = 'Menunggu pembayaran denda';
}

if ($status_transaksi_baru !== null) {
    $stmt_update_transaksi = $koneksi->prepare("UPDATE transaksi SET status = ? WHERE id_transaksi = ?");
    $stmt_update_transaksi->bind_param("si", $status_transaksi_baru, $id_transaksi);
    $stmt_update_transaksi->execute();
    $stmt_update_transaksi->close();
}

// Redirect
header("Location: detail_pengembalian.php?id_pengembalian=$id_pengembalian&message=success");
exit;
