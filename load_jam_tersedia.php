<?php
include 'koneksi.php';

// Ambil dan sanitasi tanggal dari parameter GET
$tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['tanggal']) : '';
if (empty($tanggal)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Tanggal tidak valid']);
    exit();
}

// Ambil jam yang sudah dibooking di tanggal itu
$query = mysqli_query($koneksi, "SELECT jam_main FROM tb_detail_pesan_reg WHERE tgl_main = '$tanggal'");
$booked = [];

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $jam = trim($row['jam_main']);
        if ($jam) {
            // Normalisasi jam untuk mendukung format berbeda
            if (preg_match('/^(\d{1,2}) - (\d{1,2})$/', $jam, $matches)) {
                $start = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $booked[] = "$start:00"; // Ambil jam awal saja
            } elseif (preg_match('/^\d{1,2}:\d{2}$/', $jam)) {
                $booked[] = $jam; // Gunakan jam apa adanya jika sudah dalam format HH:MM
            }
            // Abaikan jika format tidak dikenali (misalnya "paket")
        }
    }
} else {
    error_log("Query Error: " . mysqli_error($koneksi));
}

header('Content-Type: application/json');
echo json_encode($booked);
?>