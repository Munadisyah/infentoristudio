<?php
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once __DIR__ . '/vendor/autoload.php';
include("koneksi.php");
session_start();

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

if (!isset($_SESSION['level']) || $_SESSION['level'] != "pemilik") {
    echo '<script>alert("Anda tidak punya akses! Silakan login sebagai pemilik."); window.location="login.php";</script>';
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($koneksi, "SELECT nama_lengkap, foto_profil FROM user WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pemilik = mysqli_fetch_assoc($result) ?: [];
mysqli_stmt_close($stmt);

$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$status_bayar = isset($_GET['status_bayar']) ? trim($_GET['status_bayar']) : '';
$paket_id = isset($_GET['paket_id']) ? trim($_GET['paket_id']) : '';
$status_pemotretan = isset($_GET['status_pemotretan']) ? trim($_GET['status_pemotretan']) : '';

$valid_status_pemotretan = ['', 'Pemotretan Selesai', 'Menunggu Konfirmasi', 'BELUM DIJADWALKAN'];
if (!in_array($status_pemotretan, $valid_status_pemotretan)) {
    $status_pemotretan = '';
}

if ($start_date && !DateTime::createFromFormat('d/m/Y', $start_date)) {
    echo '<script>alert("Format tanggal mulai tidak valid!");</script>';
    $start_date = '';
}
if ($end_date && !DateTime::createFromFormat('d/m/Y', $end_date)) {
    echo '<script>alert("Format tanggal akhir tidak valid!");</script>';
    $end_date = '';
}

$query = "
    SELECT 
        d.tgl_pesan AS tanggal_pesan, 
        u.nama_lengkap, 
        pk.nama_paket, 
        d.total AS total_bayar, 
        b.status AS status_bayar, 
        COALESCE(j.status, 'BELUM DIJADWALKAN') AS status_pemotretan,
        j.tanggal AS tanggal_pemotretan,
        j.waktu AS waktu_pemotretan
    FROM tb_detail_pesan_reg d
    JOIN user u ON d.user_id = u.user_id
    JOIN tb_paket pk ON d.id_paket = pk.id_paket
    LEFT JOIN tb_bayar b ON d.id_pesan = b.id_pesan
    LEFT JOIN jadwal_pemotretan j ON d.id_pesan = j.id_pesan
    WHERE 1=1
";

$params = [];
$types = "";

if ($start_date) {
    $start_date_formatted = date('Y-m-d', strtotime(str_replace('/', '-', $start_date)));
    $query .= " AND DATE(d.tgl_pesan) >= ?";
    $params[] = $start_date_formatted;
    $types .= "s";
}
if ($end_date) {
    $end_date_formatted = date('Y-m-d', strtotime(str_replace('/', '-', $end_date)));
    $query .= " AND DATE(d.tgl_pesan) <= ?";
    $params[] = $end_date_formatted;
    $types .= "s";
}
if ($status_bayar) {
    $query .= " AND b.status = ?";
    $params[] = $status_bayar;
    $types .= "s";
}
if ($paket_id && is_numeric($paket_id)) {
    $query .= " AND d.id_paket = ?";
    $params[] = $paket_id;
    $types .= "i";
}
if ($status_pemotretan) {
    if ($status_pemotretan === 'BELUM DIJADWALKAN') {
        $query .= " AND j.status IS NULL";
    } else {
        $query .= " AND j.status = ?";
        $params[] = $status_pemotretan;
        $types .= "s";
    }
}

$stmt = mysqli_prepare($koneksi, $query);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$laporan_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$total_pendapatan = array_sum(array_column(array_filter($laporan_data, fn($r) => $r['status_bayar'] === 'LUNAS'), 'total_bayar'));
$jumlah_pesanan = count($laporan_data);
$rata_rata_pendapatan = $jumlah_pesanan > 0 ? $total_pendapatan / $jumlah_pesanan : 0;

$paket_query = "SELECT id_paket, nama_paket FROM tb_paket";
$paket_stmt = mysqli_prepare($koneksi, $paket_query);
mysqli_stmt_execute($paket_stmt);
$paket_result = mysqli_stmt_get_result($paket_stmt);
$paket_list = mysqli_fetch_all($paket_result, MYSQLI_ASSOC);
mysqli_stmt_close($paket_stmt);

$periode_query = "
    SELECT 
        DATE_FORMAT(d.tgl_pesan, '%Y-%m') AS periode,
        SUM(d.total) AS total_pendapatan
    FROM tb_detail_pesan_reg d
    JOIN tb_bayar b ON d.id_pesan = b.id_pesan
    WHERE b.status = 'LUNAS'" . ($start_date && $end_date ? " AND DATE(d.tgl_pesan) BETWEEN ? AND ?" : "") . "
    GROUP BY DATE_FORMAT(d.tgl_pesan, '%Y-%m')
    ORDER BY periode
";
$periode_stmt = mysqli_prepare($koneksi, $periode_query);
if ($start_date && $end_date) {
    mysqli_stmt_bind_param($periode_stmt, "ss", $start_date_formatted, $end_date_formatted);
}
mysqli_stmt_execute($periode_stmt);
$periode_result = mysqli_stmt_get_result($periode_stmt);
$periode_data = mysqli_fetch_all($periode_result, MYSQLI_ASSOC);
mysqli_stmt_close($periode_stmt);

$paket_terlaris_query = "
    SELECT 
        pk.nama_paket,
        COUNT(*) AS jumlah_pesanan
    FROM tb_detail_pesan_reg d
    JOIN tb_paket pk ON d.id_paket = pk.id_paket
    JOIN tb_bayar b ON d.id_pesan = b.id_pesan
    WHERE b.status = 'LUNAS'" . ($start_date && $end_date ? " AND DATE(d.tgl_pesan) BETWEEN ? AND ?" : "") . "
    GROUP BY pk.nama_paket
    ORDER BY jumlah_pesanan DESC
    LIMIT 5
";
$paket_terlaris_stmt = mysqli_prepare($koneksi, $paket_terlaris_query);
if ($start_date && $end_date) {
    mysqli_stmt_bind_param($paket_terlaris_stmt, "ss", $start_date_formatted, $end_date_formatted);
}
mysqli_stmt_execute($paket_terlaris_stmt);
$paket_terlaris_result = mysqli_stmt_get_result($paket_terlaris_stmt);
$paket_terlaris_data = mysqli_fetch_all($paket_terlaris_result, MYSQLI_ASSOC);
mysqli_stmt_close($paket_terlaris_stmt);

$pelanggan_sering_query = "
    SELECT 
        u.nama_lengkap,
        COUNT(*) AS jumlah_pesanan
    FROM tb_detail_pesan_reg d
    JOIN user u ON d.user_id = u.user_id
    JOIN tb_bayar b ON d.id_pesan = b.id_pesan
    WHERE b.status = 'LUNAS'" . ($start_date && $end_date ? " AND DATE(d.tgl_pesan) BETWEEN ? AND ?" : "") . "
    GROUP BY u.nama_lengkap
    ORDER BY jumlah_pesanan DESC
    LIMIT 5
";
$pelanggan_sering_stmt = mysqli_prepare($koneksi, $pelanggan_sering_query);
if ($start_date && $end_date) {
    mysqli_stmt_bind_param($pelanggan_sering_stmt, "ss", $start_date_formatted, $end_date_formatted);
}
mysqli_stmt_execute($pelanggan_sering_stmt);
$pelanggan_sering_result = mysqli_stmt_get_result($pelanggan_sering_stmt);
$pelanggan_sering_data = mysqli_fetch_all($pelanggan_sering_result, MYSQLI_ASSOC);
mysqli_stmt_close($pelanggan_sering_stmt);

$status_counts = [];
$status_query = "SELECT b.status, COUNT(*) as count FROM tb_detail_pesan_reg d JOIN tb_bayar b ON d.id_pesan = b.id_pesan GROUP BY b.status";
$status_result = mysqli_query($koneksi, $status_query);
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_counts[$row['status']] = $row['count'];
}

$saved_reports = [];
$report_query = "SELECT l.*, u.nama_lengkap AS dibuat_oleh_nama 
                 FROM tb_laporan l 
                 JOIN user u ON l.dibuat_oleh = u.user_id 
                 ORDER BY l.tanggal_buat DESC";
$result = mysqli_query($koneksi, $report_query);
if ($result) {
    $saved_reports = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 45,
        'margin_bottom' => 30,
        'margin_left' => 20,
        'margin_right' => 20,
        'margin_header' => 10,
        'margin_footer' => 10
    ]);

    $html = '
    <html>
    <head>
        <style>
            body { font-family: "Times", serif; font-size: 12pt; color: #333; line-height: 1.6; }
            .header { width: 100%; text-align: center; border-bottom: 2pt solid #1e3a8a; padding-bottom: 10pt; margin-bottom: 20pt; }
            .header .company-info h1 { font-size: 18pt; color: #1e3a8a; font-weight: bold; margin: 0; }
            .header .company-info p { font-size: 10pt; color: #333; margin: 2pt 0; }
            .report-title { text-align: center; font-size: 16pt; color: #1e3a8a; font-weight: bold; margin: 15pt 0; }
            .period { text-align: center; font-size: 10pt; color: #666; margin-bottom: 15pt; font-style: italic; }
            .summary { margin: 20pt 0; padding: 10pt; border: 1pt solid #dee2e6; background-color: #f8f9fa; }
            .summary p { margin: 5pt 0; font-size: 10pt; }
            .summary .label { font-weight: bold; color: #1e3a8a; }
            table { width: 100%; border-collapse: collapse; font-size: 10pt; margin-bottom: 20pt; }
            table th, table td { border: 1pt solid #dee2e6; padding: 8pt; text-align: left; }
            table th { background-color: #1e3a8a; color: white; font-weight: bold; }
            table td { vertical-align: top; }
            .status-lunas { background-color: #28a745; color: white; padding: 2pt 6pt; border-radius: 3pt; display: inline-block; }
            .status-ditolak { background-color: #dc3545; color: white; padding: 2pt 6pt; border-radius: 3pt; display: inline-block; }
            .status-dp-dibayar { background-color: #ffc107; color: #333; padding: 2pt 6pt; border-radius: 3pt; display: inline-block; }
            .status-menunggu-konfirmasi { background-color: #17a2b8; color: white; padding: 2pt 6pt; border-radius: 3pt; display: inline-block; }
            .status-default { background-color: #6c757d; color: white; padding: 2pt 6pt; border-radius: 3pt; display: inline-block; }
            .signature { text-align: right; margin-top: 20pt; font-size: 10pt; }
            .signature p { margin: 5pt 0; }
            .footer { text-align: center; font-size: 9pt; color: #666; margin-top: 10pt; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="company-info">
                <h1>Infentori Studio</h1>
                <p>Jl. Raya Kasemen, Sukabela, Serang - Indonesia</p>
                <p>Tel: (021) 123-4567 | Email: info@infentoristudio.com</p>
                <p>Website: www.infentoristudio.com</p>
            </div>
        </div>
        <div class="report-title">Laporan Pemesanan Studio</div>
        <div class="period">Periode: ' . ($start_date ? date('d-m-Y', strtotime($start_date)) : 'Awal') . ' s/d ' . ($end_date ? date('d-m-Y', strtotime($end_date)) : 'Sekarang') . '</div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal Pesan</th>
                    <th>Nama Customer</th>
                    <th>Nama Paket</th>
                    <th>Total Bayar</th>
                    <th>Status Bayar</th>
                    <th>Status Pemotretan</th>
                    <th>Tanggal Pemotretan</th>
                    <th>Waktu Pemotretan</th>
                </tr>
            </thead>
            <tbody>';
    foreach ($laporan_data as $row) {
        $status_class = strtolower(str_replace(' ', '-', $row['status_bayar'] ?: 'default'));
        $html .= '
        <tr>
            <td>' . date('d-m-Y', strtotime($row['tanggal_pesan'])) . '</td>
            <td>' . htmlspecialchars($row['nama_lengkap']) . '</td>
            <td>' . htmlspecialchars($row['nama_paket']) . '</td>
            <td>Rp ' . number_format($row['total_bayar'], 0, ',', '.') . '</td>
            <td><span class="status-' . $status_class . '">' . ($row['status_bayar'] ?: 'BELUM BAYAR') . '</span></td>
            <td>' . $row['status_pemotretan'] . '</td>
            <td>' . ($row['tanggal_pemotretan'] ? date('d-m-Y', strtotime($row['tanggal_pemotretan'])) : '-') . '</td>
            <td>' . ($row['waktu_pemotretan'] ?: '-') . '</td>
        </tr>';
    }
    $html .= '
            </tbody>
        </table>
        <div class="summary">
            <p><span class="label">Total Pendapatan:</span> Rp ' . number_format($total_pendapatan, 0, ',', '.') . '</p>
            <p><span class="label">Jumlah Pesanan:</span> ' . $jumlah_pesanan . '</p>
            <p><span class="label">Rata-rata Pendapatan:</span> Rp ' . number_format($rata_rata_pendapatan, 0, ',', '.') . '</p>
        </div>
        <div class="signature">
            <p>Serang, ' . date('d-m-Y') . '</p>
            <p>Pemilik,</p>
            <p style="margin-top: 40pt;">' . htmlspecialchars($pemilik['nama_lengkap']) . '</p>
        </div>
        <div class="footer">
            <p>Laporan dicetak pada: ' . date('d-m-Y H:i', time()) . ' WIB | Infentori Studio © ' . date('Y') . '</p>
        </div>
    </body>
    </html>';

    $mpdf->WriteHTML($html);
    $mpdf->Output('laporan_pemesanan.pdf', 'D');
    exit;
}

if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Laporan Pemesanan Studio');
    $sheet->setCellValue('A2', 'Periode: ' . ($start_date ? date('d-m-Y', strtotime($start_date)) : 'Awal') . ' s/d ' . ($end_date ? date('d-m-Y', strtotime($end_date)) : 'Sekarang'));
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(12);

    $sheet->setCellValue('A4', 'Tanggal Pesan');
    $sheet->setCellValue('B4', 'Nama Customer');
    $sheet->setCellValue('C4', 'Nama Paket');
    $sheet->setCellValue('D4', 'Total Bayar');
    $sheet->setCellValue('E4', 'Status Bayar');
    $sheet->setCellValue('F4', 'Status Pemotretan');
    $sheet->setCellValue('G4', 'Tanggal Pemotretan');
    $sheet->setCellValue('H4', 'Waktu Pemotretan');
    $sheet->getStyle('A4:H4')->getFont()->setBold(true);
    $sheet->getStyle('A4:H4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1e3a8a');
    $sheet->getStyle('A4:H4')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));

    $row_number = 5;
    foreach ($laporan_data as $row) {
        $sheet->setCellValue('A' . $row_number, date('d-m-Y', strtotime($row['tanggal_pesan'])));
        $sheet->setCellValue('B' . $row_number, $row['nama_lengkap']);
        $sheet->setCellValue('C' . $row_number, $row['nama_paket']);
        $sheet->setCellValue('D' . $row_number, $row['total_bayar']);
        $sheet->setCellValue('E' . $row_number, $row['status_bayar'] ?: 'BELUM BAYAR');
        $sheet->setCellValue('F' . $row_number, $row['status_pemotretan']);
        $sheet->setCellValue('G' . $row_number, $row['tanggal_pemotretan'] ? date('d-m-Y', strtotime($row['tanggal_pemotretan'])) : '-');
        $sheet->setCellValue('H' . $row_number, $row['waktu_pemotretan'] ?: '-');
        $row_number++;
    }

    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="laporan_pemesanan.xlsx"');
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pemilik - Infentori Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        html, body { background: #f0f4f8; min-height: 100vh; color: #333; overflow-x: hidden; }
        .sidebar { width: 250px; background: #1a1a1a; color: white; height: 100vh; position: fixed; top: 0; left: -250px; transition: left 0.3s ease; padding: 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.2); }
        .sidebar.open { left: 0; }
        .sidebar-header { display: flex; flex-direction: column; align-items: center; margin-bottom: 30px; }
        .sidebar-header img { width: 60px; height: 60px; border-radius: 50%; margin-bottom: 10px; border: 2px solid #1e3a8a; }
        .sidebar-menu a { display: flex; align-items: center; gap: 10px; color: #ddd; text-decoration: none; padding: 12px 15px; font-size: 14px; border-radius: 5px; margin-bottom: 5px; transition: background 0.3s ease; }
        .sidebar-menu a:hover { background: #2a2a2a; }
        .sidebar-menu a i { color: #1e3a8a; }
        .hamburger { position: fixed; top: 15px; left: 15px; font-size: 24px; background: none; border: none; color: #1e3a8a; cursor: pointer; z-index: 1101; }
        .main-content { margin-left: 0; transition: margin-left 0.3s ease; padding: 30px; }
        .main-content.shifted { margin-left: 250px; }
        h2, h3, h4 { color: #1e3a8a; font-weight: 600; text-align: center; margin-bottom: 20px; text-transform: uppercase; }
        .dashboard-card { display: flex; align-items: center; justify-content: space-between; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 15px; margin-bottom: 20px; color: white; }
        .dashboard-card i { font-size: 24px; }
        .dashboard-card p { margin: 0; font-size: 18px; }
        .card-total { background: linear-gradient(135deg, #6be585, #b9e4c9); }
        .card-jumlah { background: linear-gradient(135deg, #ffcc5c, #ffeda0); }
        .card-rata { background: linear-gradient(135deg, #ff6f61, #ff8a80); }
        .card-lunas { background: linear-gradient(135deg, #28a745, #4caf50); }
        .card-pending { background: linear-gradient(135deg, #ff9800, #ffb74d); }
        .table-custom th { background-color: #1e3a8a; color: white; padding: 12px; border-bottom: 2px solid #dee2e6; }
        .table-custom td { padding: 10px; border-bottom: 1px solid #dee2e6; }
        .table-custom tr:hover { background-color: #f8fafc; }
        .status-lunas { background-color: #28a745; color: white; padding: 2px 8px; border-radius: 5px; }
        .status-ditolak { background-color: #dc3545; color: white; padding: 2px 8px; border-radius: 5px; }
        .status-dp-dibayar { background-color: #ffc107; color: #333; padding: 2px 8px; border-radius: 5px; }
        .status-menunggu-konfirmasi { background-color: #17a2b8; color: white; padding: 2px 8px; border-radius: 5px; }
        .status-default { background-color: #6c757d; color: white; padding: 2px 8px; border-radius: 5px; }
        .btn-primary { background: #1e3a8a; border: none; }
        .btn-success { background: #28a745; border: none; }
        .btn-info { background: #17a2b8; border: none; }
        .btn-warning { background: #ffc107; border: none; color: #333; }
        .btn-secondary { background: #6c757d; border: none; color: white; }
        .chart-container { background: #e6f0fa; border-radius: 10px; padding: 15px; margin-bottom: 20px; overflow-x: auto; max-width: 400px; margin-left: auto; margin-right: auto; }
        .chart-container canvas { width: 100% !important; height: auto !important; max-height: 300px; }
        .charts-row { display: flex; flex-wrap: nowrap; justify-content: center; gap: 20px; }
        .table-container { overflow-x: auto; }
        .saved-reports-table th, .saved-reports-table td { padding: 8px; text-align: center; }
        .saved-reports-table th { background-color: #1e3a8a; color: white; }
        .ui-datepicker { font-size: 14px; background: #fff; border: 1px solid #ccc; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); width: 250px; }
        .ui-datepicker .ui-datepicker-header { background: #1e3a8a; color: #fff; border-radius: 3px; padding: 5px; }
        .ui-datepicker .ui-datepicker-prev, .ui-datepicker .ui-datepicker-next { cursor: pointer; }
        .ui-datepicker .ui-datepicker-calendar th { font-weight: bold; color: #1e3a8a; }
        .ui-datepicker .ui-datepicker-calendar td { padding: 5px; text-align: center; }
        .ui-datepicker .ui-datepicker-calendar a { color: #333; text-decoration: none; padding: 3px; }
        .ui-datepicker .ui-datepicker-calendar a.ui-state-active { background: #1e3a8a; color: #fff; border-radius: 50%; }
        @media (max-width: 768px) {
            .sidebar { width: 200px; left: -200px; }
            .main-content.shifted { margin-left: 200px; }
            .table-container, .chart-container { overflow-x: auto; }
            .chart-container { max-width: 100%; }
            .chart-container canvas { max-height: 200px; }
            .charts-row { flex-direction: column; align-items: center; }
            .ui-datepicker { width: 200px; font-size: 12px; }
        }
        @media print {
            .sidebar, .hamburger, .btn-info, .btn-success, .btn-warning, .btn-secondary { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 10px !important; }
            .dashboard-card, .chart-container, .form-container { display: none !important; }
            .report-container { margin-top: 0 !important; }
            .table-custom { font-size: 10pt; }
            .table-custom th { background-color: #1e3a8a !important; color: white !important; -webkit-print-color-adjust: exact; }
            .status-lunas { background-color: #28a745 !important; color: white !important; -webkit-print-color-adjust: exact; }
            .status-ditolak { background-color: #dc3545 !important; color: white !important; -webkit-print-color-adjust: exact; }
            .status-dp-dibayar { background-color: #ffc107 !important; color: #333 !important; -webkit-print-color-adjust: exact; }
            .status-menunggu-konfirmasi { background-color: #17a2b8 !important; color: white !important; -webkit-print-color-adjust: exact; }
            .status-default { background-color: #6c757d !important; color: white !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <button class="hamburger" onclick="toggleSidebar()" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo $pemilik['foto_profil'] ?? 'https://via.placeholder.com/60'; ?>" alt="Profil Pemilik">
            <h3><?php echo htmlspecialchars($pemilik['nama_lengkap']); ?></h3>
            <p>Pemilik</p>
        </div>
        <div class="sidebar-menu">
            <a href="pemilik_dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="pemilik_profil.php"><i class="fas fa-user"></i> Profil</a>
            <a href="pemilik_laporan.php"><i class="fas fa-file-alt"></i> Kelola Laporan</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    <div class="main-content" id="main-content">
        <h2>Laporan Pemesanan & Keuangan</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="dashboard-card card-total">
                    <div>
                        <h5>Total Pendapatan</h5>
                        <p>Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></p>
                    </div>
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card card-jumlah">
                    <div>
                        <h5>Jumlah Pesanan</h5>
                        <p><?php echo $jumlah_pesanan; ?></p>
                    </div>
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card card-rata">
                    <div>
                        <h5>Rata-rata Pendapatan</h5>
                        <p>Rp <?php echo number_format($rata_rata_pendapatan, 0, ',', '.'); ?></p>
                    </div>
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card card-lunas">
                    <div><h5>Jumlah Lunas</h5><p><?php echo $status_counts['LUNAS'] ?? 0; ?></p></div>
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card card-pending">
                    <div><h5>Menunggu Konfirmasi</h5><p><?php echo $status_counts['MENUNGGU KONFIRMASI'] ?? 0; ?></p></div>
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="charts-row row">
            <?php if (!empty($periode_data)): ?>
            <div class="col-md-4 chart-container">
                <h4>Pendapatan per Periode</h4>
                <canvas id="periodeChart"></canvas>
            </div>
            <?php else: ?>
            <div class="col-md-4">
                <div class="alert alert-info">Tidak ada data pendapatan untuk periode yang dipilih.</div>
            </div>
            <?php endif; ?>
            <?php if (!empty($paket_terlaris_data)): ?>
            <div class="col-md-4 chart-container">
                <h4>Paket Terlaris</h4>
                <canvas id="paketTerlarisChart"></canvas>
            </div>
            <?php else: ?>
            <div class="col-md-4">
                <div class="alert alert-info">Tidak ada data paket terlaris untuk periode yang dipilih.</div>
            </div>
            <?php endif; ?>
            <?php if (!empty($pelanggan_sering_data)): ?>
            <div class="col-md-4 chart-container">
                <h4>Pelanggan yang Sering Memesan</h4>
                <canvas id="pelangganSeringChart"></canvas>
            </div>
            <?php else: ?>
            <div class="col-md-4">
                <div class="alert alert-info">Tidak ada data pelanggan yang sering memesan untuk periode yang dipilih.</div>
            </div>
            <?php endif; ?>
        </div>
        <div class="form-container">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="text" class="form-control datepicker" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" placeholder="dd/mm/yyyy">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="text" class="form-control datepicker" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" placeholder="dd/mm/yyyy">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status Pembayaran</label>
                    <select name="status_bayar" class="form-select">
                        <option value="">Semua</option>
                        <option value="LUNAS" <?php echo $status_bayar == 'LUNAS' ? 'selected' : ''; ?>>Lunas</option>
                        <option value="DP DIBAYAR" <?php echo $status_bayar == 'DP DIBAYAR' ? 'selected' : ''; ?>>DP Dibayar</option>
                        <option value="MENUNGGU KONFIRMASI" <?php echo $status_bayar == 'MENUNGGU KONFIRMASI' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                        <option value="DITOLAK" <?php echo $status_bayar == 'DITOLAK' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Paket</label>
                    <select name="paket_id" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach ($paket_list as $paket): ?>
                            <option value="<?php echo $paket['id_paket']; ?>" <?php echo $paket_id == $paket['id_paket'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($paket['nama_paket']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status Pemotretan</label>
                    <select name="status_pemotretan" class="form-select">
                        <option value="">Semua</option>
                        <option value="Pemotretan Selesai" <?php echo $status_pemotretan == 'Pemotretan Selesai' ? 'selected' : ''; ?>>Pemotretan Selesai</option>
                        <option value="Menunggu Konfirmasi" <?php echo $status_pemotretan == 'Menunggu Konfirmasi' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                        <option value="BELUM DIJADWALKAN" <?php echo $status_pemotretan == 'BELUM DIJADWALKAN' ? 'selected' : ''; ?>>Belum Dijadwalkan</option>
                    </select>
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?export=pdf" class="btn btn-success">Export PDF</a>
                    <a href="?export=excel" class="btn btn-info">Export Excel</a>
                    <button type="button" class="btn btn-warning" onclick="window.print()">Cetak</button>
                    <a href="pemilik_laporan.php" class="btn btn-secondary">Reset Filter</a>
                </div>
            </form>
        </div>
        <div class="report-container">
            <div class="table-container">
                <table class="table-custom table">
                    <thead>
                        <tr>
                            <th>Tanggal Pesan</th>
                            <th>Nama Customer</th>
                            <th>Nama Paket</th>
                            <th>Total Bayar</th>
                            <th>Status Bayar</th>
                            <th>Status Pemotretan</th>
                            <th>Tanggal Pemotretan</th>
                            <th>Waktu Pemotretan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laporan_data as $row): ?>
                            <tr>
                                <td data-label="Tanggal Pesan"><?php echo date('d-m-Y', strtotime($row['tanggal_pesan'])); ?></td>
                                <td data-label="Nama Customer"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td data-label="Nama Paket"><?php echo htmlspecialchars($row['nama_paket']); ?></td>
                                <td data-label="Total Bayar">Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                                <td data-label="Status Bayar"><span class="status-<?php echo strtolower(str_replace(' ', '-', $row['status_bayar'] ?: 'default')); ?>"><?php echo $row['status_bayar'] ?: 'BELUM BAYAR'; ?></span></td>
                                <td data-label="Status Pemotretan"><?php echo $row['status_pemotretan']; ?></td>
                                <td data-label="Tanggal Pemotretan"><?php echo $row['tanggal_pemotretan'] ? date('d-m-Y', strtotime($row['tanggal_pemotretan'])) : '-'; ?></td>
                                <td data-label="Waktu Pemotretan"><?php echo $row['waktu_pemotretan'] ?: '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <h3>Laporan Tersimpan</h3>
        <div class="table-container">
            <table class="table-custom table saved-reports-table">
                <thead>
                    <tr>
                        <th>ID Laporan</th>
                        <th>Catatan</th>
                        <th>Dibuat Oleh</th>
                        <th>Tanggal Buat</th>
                        <th>Tanggal Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($saved_reports)): ?>
                        <tr><td colspan="5" class="text-center">Tidak ada laporan tersimpan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($saved_reports as $report): ?>
                            <tr>
                                <td><?php echo $report['id_laporan']; ?></td>
                                <td><?php echo htmlspecialchars($report['catatan']); ?></td>
                                <td><?php echo htmlspecialchars($report['dibuat_oleh_nama']); ?></td>
                                <td><?php echo date('d-m-Y H:i', strtotime($report['tanggal_buat'])); ?></td>
                                <td><?php echo $report['tanggal_update'] ? date('d-m-Y H:i', strtotime($report['tanggal_update'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const hamburgerIcon = document.querySelector('.hamburger i');
            sidebar.classList.toggle('open');
            mainContent.classList.toggle('shifted');
            hamburgerIcon.classList.toggle('fa-bars');
            hamburgerIcon.classList.toggle('fa-times');
        }

        $(".datepicker").datepicker({
            dateFormat: 'dd/mm/yy',
            changeMonth: true,
            changeYear: true,
            showAnim: 'slideDown',
            showButtonPanel: true,
            minDate: new Date(2020, 0, 1),
            maxDate: new Date(2025, 11, 31),
            beforeShow: function(input) {
                setTimeout(function() {
                    var calendar = $('.ui-datepicker');
                    calendar.css({
                        'font-size': '14px',
                        'background': '#fff',
                        'border': '1px solid #ccc',
                        'padding': '10px',
                        'border-radius': '5px',
                        'box-shadow': '0 2px 5px rgba(0,0,0,0.2)'
                    });
                    $('.ui-datepicker-header').css({
                        'background': '#1e3a8a',
                        'color': '#fff',
                        'border-radius': '3px',
                        'padding': '5px'
                    });
                    $('.ui-datepicker-calendar th').css({
                        'font-weight': 'bold',
                        'color': '#1e3a8a'
                    });
                    $('.ui-datepicker-calendar td').css({
                        'padding': '5px',
                        'text-align': 'center'
                    });
                    $('.ui-datepicker-calendar a').css({
                        'color': '#333',
                        'text-decoration': 'none',
                        'padding': '3px'
                    });
                    $('.ui-datepicker-calendar a.ui-state-active').css({
                        'background': '#1e3a8a',
                        'color': '#fff',
                        'border-radius': '50%'
                    });
                }, 0);
            }
        });

        <?php if (!empty($periode_data)): ?>
        const periodeCtx = document.getElementById('periodeChart').getContext('2d');
        new Chart(periodeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($periode_data, 'periode')); ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?php echo json_encode(array_column($periode_data, 'total_pendapatan')); ?>,
                    backgroundColor: '#1e3a8a',
                    borderColor: '#1e3a8a',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            },
                            color: '#333',
                            font: { size: 12 }
                        }
                    },
                    x: {
                        ticks: { color: '#333', font: { size: 12 } }
                    }
                },
                plugins: {
                    legend: { labels: { color: '#333', font: { size: 14 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Pendapatan: Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if (!empty($paket_terlaris_data)): ?>
        const paketTerlarisCtx = document.getElementById('paketTerlarisChart').getContext('2d');
        new Chart(paketTerlarisCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($paket_terlaris_data, 'nama_paket')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($paket_terlaris_data, 'jumlah_pesanan')); ?>,
                    backgroundColor: ['#1e3a8a', '#28a745', '#ffc107', '#dc3545', '#17a2b8'],
                    borderColor: ['#fff'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#333', font: { size: 12 }, padding: 15 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + ' pesanan';
                            }
                        }
                    }
                },
                aspectRatio: 1.5
            }
        });
        <?php endif; ?>

        <?php if (!empty($pelanggan_sering_data)): ?>
        const pelangganSeringCtx = document.getElementById('pelangganSeringChart').getContext('2d');
        new Chart(pelangganSeringCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($pelanggan_sering_data, 'nama_lengkap')); ?>,
                datasets: [{
                    label: 'Jumlah Pesanan',
                    data: <?php echo json_encode(array_column($pelanggan_sering_data, 'jumlah_pesanan')); ?>,
                    backgroundColor: '#28a745',
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#333', font: { size: 12 } }
                    },
                    x: {
                        ticks: { color: '#333', font: { size: 12 } }
                    }
                },
                plugins: {
                    legend: { labels: { color: '#333', font: { size: 14 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + ' pesanan';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php
mysqli_close($koneksi);
?>