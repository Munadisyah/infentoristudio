<?php
include("koneksi.php");
session_start();

if (!isset($_SESSION['level']) || $_SESSION['level'] != "admin") {
    echo '<script>alert("Anda tidak punya akses!"); window.location="index.php";</script>';
    exit();
}

if (isset($_POST['id_bayar']) && isset($_POST['action'])) {
    $id_bayar = mysqli_real_escape_string($koneksi, $_POST['id_bayar']);
    $action = $_POST['action'];

    // Ambil data pembayaran termasuk dp, sisa, dan total_harga dari tb_bayar
    $query_check = mysqli_query($koneksi, "
        SELECT b.dp, b.sisa, b.id_paket, b.total_harga, p.harga AS harga_asli, p.harga_diskon AS harga_diskon, b.id_pesan
        FROM tb_bayar b 
        LEFT JOIN tb_paket p ON b.id_paket = p.id_paket 
        WHERE b.id_bayar='$id_bayar'
    ");
    $data = mysqli_fetch_assoc($query_check);

    if (!$data) {
        echo '<script>alert("Data pembayaran tidak ditemukan!"); window.location="admin_pembayaran.php";</script>';
        exit();
    }

    $dp = $data['dp'] ?? 0;
    $sisa = $data['sisa'] ?? 0;
    $total_harga = $data['total_harga'] > 0 ? $data['total_harga'] : ($data['harga_diskon'] > 0 ? $data['harga_diskon'] : $data['harga_asli']);
    $id_pesan = $data['id_pesan'];

    if ($action == 'konfirmasi') {
        if ($sisa <= 0 && $dp >= $total_harga) {
            // Jika sisa <= 0 dan DP mencakup total, ubah status ke LUNAS
            $query = "UPDATE tb_bayar SET status='LUNAS', sisa=0, tgl_konfir=NOW() WHERE id_bayar='$id_bayar'";
            $pesan_update = mysqli_query($koneksi, "UPDATE tb_pesan SET status=2 WHERE id_pesan='$id_pesan'");
        } else {
            // Jika masih ada sisa, update status ke DP DIBAYAR dan hitung sisa
            $sisa = max(0, $total_harga - $dp);
            $query = "UPDATE tb_bayar SET status='DP DIBAYAR', sisa='$sisa', tgl_konfir=NOW() WHERE id_bayar='$id_bayar'";
            $pesan_update = mysqli_query($koneksi, "UPDATE tb_pesan SET status=1 WHERE id_pesan='$id_pesan'");
        }
        if (mysqli_query($koneksi, $query) && $pesan_update) {
            // Periksa apakah tgl_booking perlu diisi (opsional)
            $booking_check = mysqli_query($koneksi, "SELECT tgl_booking FROM tb_pesan WHERE id_pesan='$id_pesan'");
            $booking_data = mysqli_fetch_assoc($booking_check);
            if ($booking_data['tgl_booking'] === NULL) {
                $tgl_main = mysqli_fetch_array(mysqli_query($koneksi, "SELECT tgl_main FROM tb_detail_pesan_reg WHERE id_pesan='$id_pesan'"));
                if ($tgl_main) {
                    mysqli_query($koneksi, "UPDATE tb_pesan SET tgl_booking='{$tgl_main['tgl_main']}' WHERE id_pesan='$id_pesan'");
                }
            }
            echo '<script>alert("Status berhasil diperbarui menjadi ' . ($sisa <= 0 ? 'LUNAS' : 'DP DIBAYAR') . '! Sisa pembayaran: Rp ' . number_format($sisa, 0, ',', '.') . '"); window.location="admin_pembayaran.php";</script>';
        } else {
            echo '<script>alert("Gagal memperbarui status: ' . mysqli_error($koneksi) . '"); window.location="admin_pembayaran.php";</script>';
        }
    } elseif ($action == 'tolak') {
        $query = "UPDATE tb_bayar SET status='DITOLAK', tgl_konfir=NOW() WHERE id_bayar='$id_bayar'";
        $pesan_update = mysqli_query($koneksi, "UPDATE tb_pesan SET status=0 WHERE id_pesan='$id_pesan'");
        if (mysqli_query($koneksi, $query) && $pesan_update) {
            echo '<script>alert("Status berhasil diperbarui menjadi DITOLAK!"); window.location="admin_pembayaran.php";</script>';
        } else {
            echo '<script>alert("Gagal memperbarui status: ' . mysqli_error($koneksi) . '"); window.location="admin_pembayaran.php";</script>';
        }
    } elseif ($action == 'konfirmasi_sisa') {
        // Konfirmasi pembayaran sisa, set status ke LUNAS jika sisa sudah dibayar
        if ($sisa > 0) {
            echo '<script>alert("Pembayaran sisa belum diterima atau tidak valid!"); window.location="admin_pembayaran.php";</script>';
            exit();
        }
        $query = "UPDATE tb_bayar SET status='LUNAS', sisa=0, tgl_konfir=NOW() WHERE id_bayar='$id_bayar'";
        $pesan_update = mysqli_query($koneksi, "UPDATE tb_pesan SET status=2 WHERE id_pesan='$id_pesan'");
        if (mysqli_query($koneksi, $query) && $pesan_update) {
            // Periksa dan isi tgl_booking jika NULL
            $booking_check = mysqli_query($koneksi, "SELECT tgl_booking FROM tb_pesan WHERE id_pesan='$id_pesan'");
            $booking_data = mysqli_fetch_assoc($booking_check);
            if ($booking_data['tgl_booking'] === NULL) {
                $tgl_main = mysqli_fetch_array(mysqli_query($koneksi, "SELECT tgl_main FROM tb_detail_pesan_reg WHERE id_pesan='$id_pesan'"));
                if ($tgl_main) {
                    mysqli_query($koneksi, "UPDATE tb_pesan SET tgl_booking='{$tgl_main['tgl_main']}' WHERE id_pesan='$id_pesan'");
                }
            }
            echo '<script>alert("Pembayaran sisa dikonfirmasi, status menjadi LUNAS!"); window.location="admin_pembayaran.php";</script>';
        } else {
            echo '<script>alert("Gagal mengkonfirmasi pembayaran sisa: ' . mysqli_error($koneksi) . '"); window.location="admin_pembayaran.php";</script>';
        }
    } else {
        echo '<script>alert("Aksi tidak valid!"); window.location="admin_pembayaran.php";</script>';
    }
} else {
    echo '<script>alert("Aksi tidak valid!"); window.location="admin_pembayaran.php";</script>';
}
?>