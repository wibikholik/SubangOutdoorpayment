<?php
include '../../route/koneksi.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu.'); window.location.href='../../login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_transaksi = isset($_POST['id_transaksi']) ? intval($_POST['id_transaksi']) : 0;
    $id_checklist_arr = $_POST['id_checklist'] ?? [];
    $status_akhir_arr = $_POST['status_akhir'] ?? [];
    $keterangan_akhir_arr = $_POST['keterangan_akhir'] ?? [];

    if ($id_transaksi <= 0) {
        echo "<script>alert('ID transaksi tidak valid.'); history.back();</script>";
        exit;
    }

    if (count($id_checklist_arr) !== count($status_akhir_arr) || count($id_checklist_arr) !== count($keterangan_akhir_arr)) {
        echo "<script>alert('Data checklist tidak lengkap.'); history.back();</script>";
        exit;
    }

    mysqli_begin_transaction($koneksi);

    try {
        // 1. Update checklist
        $stmt = $koneksi->prepare("UPDATE checklist SET status_akhir = ?, keterangan_akhir = ? WHERE id_checklist = ?");
        if (!$stmt) throw new Exception("Prepare checklist error: " . $koneksi->error);

        for ($i = 0; $i < count($id_checklist_arr); $i++) {
            $id_checklist = intval($id_checklist_arr[$i]);
            $status_akhir = $status_akhir_arr[$i];
            $keterangan_akhir = $keterangan_akhir_arr[$i];

            $stmt->bind_param("ssi", $status_akhir, $keterangan_akhir, $id_checklist);
            if (!$stmt->execute()) {
                throw new Exception("Gagal update checklist ID $id_checklist: " . $stmt->error);
            }
        }
        $stmt->close();

        // 2. Update status transaksi
        $new_status = "Menunggu Konfirmasi Pengembalian";
        $stmt2 = $koneksi->prepare("UPDATE transaksi SET status = ? WHERE id_transaksi = ?");
        if (!$stmt2) throw new Exception("Prepare transaksi error: " . $koneksi->error);

        $stmt2->bind_param("si", $new_status, $id_transaksi);
        if (!$stmt2->execute()) {
            throw new Exception("Gagal update status transaksi: " . $stmt2->error);
        }
        $stmt2->close();

        // 3. Ambil tanggal kembali dan total harga sewa untuk hitung denda
        $query_trans = "SELECT tanggal_kembali, total_harga_sewa FROM transaksi WHERE id_transaksi = ?";
        $stmt3 = $koneksi->prepare($query_trans);
        if (!$stmt3) throw new Exception("Prepare select transaksi error: " . $koneksi->error);

        $stmt3->bind_param("i", $id_transaksi);
        $stmt3->execute();
        $stmt3->bind_result($tanggal_kembali, $total_harga_sewa);
        $stmt3->fetch();
        $stmt3->close();

        $tgl_kembali = new DateTime($tanggal_kembali);
        $tgl_sekarang = new DateTime();
        $terlambat = $tgl_sekarang > $tgl_kembali ? $tgl_sekarang->diff($tgl_kembali)->days : 0;
        $denda = $terlambat > 0 ? $total_harga_sewa * $terlambat : 0;

        // 4. Insert ke tabel pengembalian
        $status_pengembalian = "Menunggu Konfirmasi Pengembalian";
        $catatan = ""; // Bisa diganti jika ada input
        $stmt4 = $koneksi->prepare("INSERT INTO pengembalian (id_transaksi, denda, status_pengembalian, catatan) VALUES (?, ?, ?, ?)");
        if (!$stmt4) throw new Exception("Prepare insert pengembalian error: " . $koneksi->error);

        $stmt4->bind_param("idss", $id_transaksi, $denda, $status_pengembalian, $catatan);
        if (!$stmt4->execute()) {
            throw new Exception("Gagal menyimpan data pengembalian: " . $stmt4->error);
        }
        $stmt4->close();

        mysqli_commit($koneksi);

        echo "<script>alert('Pengembalian berhasil disimpan.'); window.location.href = '../page/transaksi.php?id_transaksi=$id_transaksi';</script>";
        exit;

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo "<script>alert('Terjadi kesalahan: " . addslashes($e->getMessage()) . "'); history.back();</script>";
        exit;
    }

} else {
    echo "<script>alert('Metode request tidak valid.'); history.back();</script>";
    exit;
}
