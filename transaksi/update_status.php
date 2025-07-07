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
            'selesai dikembalikan',
            'batal'
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
        // Sinkron ke pembayaran
        $sql_pembayaran = "UPDATE pembayaran SET status_pembayaran = ? WHERE id_transaksi = ?";
        $stmt_pembayaran = $koneksi->prepare($sql_pembayaran);
        if ($stmt_pembayaran) {
            $stmt_pembayaran->bind_param("si", $status_baru, $id);
            $stmt_pembayaran->execute();
            $stmt_pembayaran->close();
        }

        // === KURANGI STOK SAAT DIKONFIRMASI PEMBAYARAN ===
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

        // === KEMBALIKAN STOK JIKA BATAL ===
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

        // === KEMBALIKAN STOK JIKA SELESAI DIKEMBALIKAN ===
       if (strtolower($status_baru) === 'selesai dikembalikan') {
    // 1. Update stok barang
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

    // 2. Ambil data transaksi dan penyewa, termasuk metode pembayaran
    $query_invoice = "SELECT t.id_transaksi, t.tanggal_sewa, t.tanggal_kembali, t.total_harga_sewa, t.denda, 
                             p.nama_penyewa, p.email, m.nama_metode 
                      FROM transaksi t
                      JOIN penyewa p ON t.id_penyewa = p.id_penyewa
                      JOIN metode_pembayaran m ON t.id_metode = m.id_metode
                      WHERE t.id_transaksi = ?";
    $stmt_invoice = $koneksi->prepare($query_invoice);
    $stmt_invoice->bind_param("i", $id);
    $stmt_invoice->execute();
    $result_invoice = $stmt_invoice->get_result()->fetch_assoc();
    $stmt_invoice->close();

    $nama = $result_invoice['nama_penyewa'];
    $email = $result_invoice['email'];
    $tanggal_sewa = date('d-m-Y', strtotime($result_invoice['tanggal_sewa']));
    $tanggal_kembali = date('d-m-Y', strtotime($result_invoice['tanggal_kembali']));
    $denda = $result_invoice['denda'];
    $total_harga_sewa = $result_invoice['total_harga_sewa'];
    $metode_pembayaran = $result_invoice['nama_metode'];

    // 3. Ambil detail barang
    $query_detail = "SELECT b.nama_barang, d.jumlah_barang, d.harga_satuan 
                     FROM detail_transaksi d 
                     JOIN barang b ON d.id_barang = b.id_barang 
                     WHERE d.id_transaksi = ?";
    $stmt_detail = $koneksi->prepare($query_detail);
    $stmt_detail->bind_param("i", $id);
    $stmt_detail->execute();
    $result_detail = $stmt_detail->get_result();

    $list_barang = "";
    while ($row = $result_detail->fetch_assoc()) {
        $nama_barang = $row['nama_barang'];
        $jumlah = $row['jumlah_barang'];
        $harga_satuan = $row['harga_satuan'];
        $harga = number_format($harga_satuan, 0, ',', '.');
        $total_item = $harga_satuan * $jumlah;
        $total_item_format = number_format($total_item, 0, ',', '.');

        $list_barang .= "
            <tr>
                <td>$nama_barang</td>
                <td>Rp $harga</td>
                <td>$jumlah</td>
                <td>Rp $total_item_format</td>
            </tr>";
    }
    $stmt_detail->close();

    $total_sewa_format = number_format($total_harga_sewa, 0, ',', '.');
    $denda_format = number_format($denda, 0, ',', '.');
    $total_bayar = $total_harga_sewa + $denda;
    $total_bayar_format = number_format($total_bayar, 0, ',', '.');

    // 4. Kirim invoice via email
    require '../vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'subangoutdoortes@gmail.com';
        $mail->Password = 'sbsn ajtg fgox otra';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('subangoutdoortes@gmail.com', 'Subang Outdoor');
        $mail->addAddress($email, $nama);
        $mail->isHTML(true);
        $mail->Subject = "Invoice Sewa Barang #$id";

        $mail->Body = "
            <style>
                body { font-family: Arial, sans-serif; color: #000; }
                .invoice-box { width: 100%; border: 1px solid #eee; padding: 20px; }
                table { width: 100%; border-collapse: collapse; }
                td, th { padding: 8px; border: 1px solid #ccc; }
                th { background: #f2f2f2; }
                .no-border td { border: none; }
                .signature { margin-top: 40px; text-align: right; }
            </style>

            <div class='invoice-box'>
                <table class='no-border'>
                    <tr>
                        <td><h2>INVOICE</h2></td>
                        <td style='text-align: right;'>
                            <strong>SUBANG OUTDOOR</strong><br>
                            Alat Camping & Adventure
                        </td>
                    </tr>
                </table>

                <br>

                <table class='no-border'>
                    <tr>
                        <td>
                            <strong>KEPADA:</strong><br>
                            $nama<br>
                            $email
                        </td>
                        <td style='text-align: right;'>
                            <strong>TANGGAL:</strong> $tanggal_kembali<br>
                            <strong>NO INVOICE:</strong> #$id / " . date('d/m/Y') . "
                        </td>
                    </tr>
                </table>

                <br>

                <table>
                    <tr>
                        <th>KETERANGAN</th>
                        <th>HARGA</th>
                        <th>JML</th>
                        <th>TOTAL</th>
                    </tr>
                    $list_barang
                </table>

                <br>

                <table>
                    <tr>
                        <td colspan='3'><strong>SUB TOTAL</strong></td>
                        <td>Rp $total_sewa_format</td>
                    </tr>
                    <tr>
                        <td colspan='3'><strong>DENDA</strong></td>
                        <td>Rp $denda_format</td>
                    </tr>
                    <tr>
                        <td colspan='3'><strong>METODE PEMBAYARAN</strong></td>
                        <td>$metode_pembayaran</td>
                    </tr>
                    <tr>
                        <td colspan='3'><strong>TOTAL BAYAR</strong></td>
                        <td><strong>Rp $total_bayar_format</strong></td>
                    </tr>
                </table>

                <br><br>

                <div class='signature'>
                    <p>Hormat Kami,</p>
                    <br><br>
                    <strong>Subang Outdoor Team</strong>
                </div>

                <p><small>Terima kasih atas kepercayaan Anda menyewa di Subang Outdoor.</small></p>
            </div>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Gagal mengirim email invoice: " . $mail->ErrorInfo);
    }
}

        // === PENGINGAT H-1 KEMBALI ===
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
                        error_log(message: "Gagal kirim pengingat: {$mail->ErrorInfo}");
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
