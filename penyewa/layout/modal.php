<!-- modal.php -->
<div class="modal fade" id="keranjangModal<?php echo $row['id_barang']; ?>" tabindex="-1" aria-labelledby="keranjangLabel<?php echo $row['id_barang']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="keranjangLabel<?php echo $row['id_barang']; ?>">Tambah ke Keranjang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong><?php echo $row['nama_barang']; ?></strong></p>
        <p>Harga Sewa: Rp.<?php echo number_format($row['harga_sewa'], 0, ',', '.'); ?></p>
        <p>Stok tersedia: <?php echo $row['stok']; ?></p>
        <form method="POST" action="../controller/tambah_keranjang.php">
          <input type="hidden" name="id_barang" value="<?php echo $row['id_barang']; ?>">
          <input type="number" name="jumlah" class="form-control mb-2" placeholder="Jumlah" min="1" max="<?php echo $row['stok']; ?>" required>
          <button type="submit" class="btn btn-dark">Tambah ke Keranjang</button>
        </form>
      </div>
    </div>
  </div>
</div>
