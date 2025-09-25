<?php
include("koneksi.php");
$reset_query = mysqli_query($koneksi, "
    UPDATE tb_paket p
    LEFT JOIN (
        SELECT id_paket, MAX(persentase_diskon) as max_diskon
        FROM tb_promo
        WHERE NOW() BETWEEN tanggal_mulai AND tanggal_selesai
        GROUP BY id_paket
    ) pr ON p.id_paket = pr.id_paket
    SET p.harga_diskon = IF(pr.max_diskon IS NOT NULL, p.harga * (1 - pr.max_diskon / 100), p.harga)
");
if (!$reset_query) {
    error_log("Gagal reset harga diskon: " . mysqli_error($koneksi));
}
mysqli_close($koneksi);
?>