<?php
require_once __DIR__ . '/../vendor/autoload.php';
include '../route/koneksi.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function kirimInvoicePengembalian($koneksi, $id_transaksi, $denda)
{
    // Ambil data transaksi dan penyewa
    $query = "SELECT t.id_transaksi, t.tanggal_sewa, t.tanggal_kembali, t.total_harga_sewa, t.id_metode,
                     u.nama_penyewa AS nama, u.email,
                     m.nama_metode
              FROM transaksi t
              JOIN penyewa u ON t.id_penyewa = u.id_penyewa
              JOIN metode_pembayaran m ON t.id_metode = m.id_metode
              WHERE t.id_transaksi = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id_transaksi);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $nama = $data['nama'];
    $email = $data['email'];
    $tanggal_kembali = date('d-m-Y', strtotime($data['tanggal_kembali']));
    $total_sewa = $data['total_harga_sewa'];
    $total_bayar = $total_sewa + $denda;
    $metode_pembayaran = $data['nama_metode'];

    $total_sewa_format = number_format($total_sewa, 0, ',', '.');
    $denda_format = number_format($denda, 0, ',', '.');
    $total_bayar_format = number_format($total_bayar, 0, ',', '.');

    // Ambil detail barang
    $query_detail = "SELECT b.nama_barang, d.jumlah_barang, d.harga_satuan
                     FROM detail_transaksi d
                     JOIN barang b ON d.id_barang = b.id_barang
                     WHERE d.id_transaksi = ?";
    $stmt = $koneksi->prepare($query_detail);
    $stmt->bind_param("i", $id_transaksi);
    $stmt->execute();
    $result_detail = $stmt->get_result();

    $list_barang = "";
    while ($row = $result_detail->fetch_assoc()) {
        $nama_barang = $row['nama_barang'];
        $jumlah = $row['jumlah_barang'];
        $harga = number_format($row['harga_satuan'], 0, ',', '.');
        $total_per_item = number_format($row['harga_satuan'] * $jumlah, 0, ',', '.');
        $list_barang .= "<tr><td>$nama_barang</td><td>Rp$harga</td><td>$jumlah</td><td>Rp$total_per_item</td></tr>";
    }
    $stmt->close();

    // Kirim email invoice
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
        $mail->Subject = "Invoice Sewa Barang #$id_transaksi";

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
                            <strong>NO INVOICE:</strong> #$id_transaksi / " . date('d/m/Y') . "
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
