<?php
date_default_timezone_set('Asia/Jakarta'); // Mengatur zona waktu ke WIB
include("koneksi.php");
session_start();

if (!isset($_SESSION['level']) || $_SESSION['level'] != "admin") {
    echo '<script>alert("Anda tidak punya akses!"); window.location="index.php";</script>';
    exit();
}

// Logika penghapusan data
if (isset($_POST['action']) && $_POST['action'] == 'hapus' && isset($_POST['id_bayar'])) {
    $id_bayar = mysqli_real_escape_string($koneksi, $_POST['id_bayar']);
    $query_file = mysqli_query($koneksi, "SELECT upload_slip, upload_slip_sisa FROM tb_bayar WHERE id_bayar = '$id_bayar'");
    $data_file = mysqli_fetch_assoc($query_file);
    
    // Hapus file bukti pembayaran jika ada
    if (!empty($data_file['upload_slip'])) {
        $file_path = "img/buktibayar/" . $data_file['upload_slip'];
        if (file_exists($file_path)) unlink($file_path);
    }
    // Hapus file bukti pembayaran sisa jika ada
    if (!empty($data_file['upload_slip_sisa'])) {
        $file_path_sisa = "img/buktibayar/" . $data_file['upload_slip_sisa'];
        if (file_exists($file_path_sisa)) unlink($file_path_sisa);
    }
    
    // Hapus data dari database
    $query_delete = mysqli_query($koneksi, "DELETE FROM tb_bayar WHERE id_bayar = '$id_bayar'");
    if ($query_delete) {
        echo '<script>alert("Data berhasil dihapus!"); window.location="admin_pembayaran.php";</script>';
    } else {
        echo '<script>alert("Gagal menghapus data: " . mysqli_error($koneksi)); window.location="admin_pembayaran.php";</script>';
    }
}

// Logika konfirmasi pembayaran sisa
if (isset($_POST['action']) && $_POST['action'] == 'konfirmasi_sisa' && isset($_POST['id_bayar'])) {
    $id_bayar = mysqli_real_escape_string($koneksi, $_POST['id_bayar']);
    $tgl_konfir = date('Y-m-d'); // Hanya tanggal dari NOW()
    $update = mysqli_query($koneksi, "UPDATE tb_bayar SET status='LUNAS', sisa=0, tgl_konfir='$tgl_konfir' WHERE id_bayar='$id_bayar'");
    if ($update) {
        $bayar = mysqli_fetch_array(mysqli_query($koneksi, "SELECT id_pesan FROM tb_bayar WHERE id_bayar='$id_bayar'"));
        mysqli_query($koneksi, "UPDATE tb_pesan SET status=2 WHERE id_pesan='{$bayar['id_pesan']}'");
        echo '<script>alert("Pembayaran sisa dikonfirmasi!"); window.location="admin_pembayaran.php";</script>';
    } else {
        echo '<script>alert("Gagal mengkonfirmasi pembayaran sisa: " . mysqli_error($koneksi)); window.location="admin_pembayaran.php";</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pembayaran Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html, body {
            background: #f0f4f8;
            min-height: 100vh;
            color: #333;
            overflow-x: hidden;
        }

        .sidebar {
            width: 250px;
            background: #1a1a1a;
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: -250px;
            transition: left 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }

        .sidebar-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 2px solid #1e3a8a;
        }

        .sidebar-header h3 {
            font-size: 16px;
            font-weight: 600;
        }

        .sidebar-header p {
            font-size: 12px;
            color: #bbb;
        }

        .sidebar-menu {
            flex-grow: 1;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ddd;
            text-decoration: none;
            padding: 12px 15px;
            font-size: 14px;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: background 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: #2a2a2a;
        }

        .sidebar-menu a i {
            color: #1e3a8a;
        }

        .sidebar-menu .logout {
            margin-top: auto;
        }

        .hamburger {
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 24px;
            background: none;
            border: none;
            color: #1e3a8a;
            cursor: pointer;
            z-index: 1101;
        }

        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease-in-out;
            padding: 30px;
            position: relative;
            z-index: 1;
        }

        .main-content.shifted {
            margin-left: 250px;
        }

        h2 {
            color: #1e3a8a;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin: 0 auto;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background: #1e3a8a;
            color: white;
            padding: 10px;
            text-align: center;
            border: none;
            font-weight: 500;
            font-size: 14px;
        }

        .table tbody td {
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
            font-size: 13px;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        .table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            transition: transform 0.2s ease;
        }

        .table img:hover {
            transform: scale(1.1);
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
            display: inline-block;
            font-size: 12px;
        }

        .status-dp {
            background: #cce5ff;
            color: #004085;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
            display: inline-block;
            font-size: 12px;
        }

        .status-lunas {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
            display: inline-block;
            font-size: 12px;
        }

        .btn-action {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px;
            border-radius: 5px;
            background: #1e3a8a;
            color: white !important;
            margin: 0 5px;
            transition: all 0.3s ease;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #3b82f6;
        }

        .dataTables_length, .dataTables_filter {
            margin-bottom: 15px;
        }

        .dataTables_length select, .dataTables_filter input {
            border-radius: 5px;
            border: 1px solid #ced4da;
            padding: 6px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                left: -200px;
            }
            .main-content.shifted {
                margin-left: 200px;
            }
        }
    </style>
</head>
<body>
    <button class="hamburger" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <?php
            $user_id = $_SESSION['user_id'];
            $result = mysqli_query($koneksi, "SELECT * FROM user WHERE user_id='$user_id'");
            $admin = mysqli_fetch_assoc($result);
            ?>
            <img src="<?= $admin['foto_profil'] ?? 'https://via.placeholder.com/60'; ?>" alt="Profil Admin">
            <h3><?= $admin['nama_lengkap']; ?></h3>
            <p>Administrator</p>
        </div>
        <div class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
            <a href="admin_profil.php"><i class="fas fa-user"></i> Profil</a>
            <a href="admin_pengguna.php"><i class="fas fa-users"></i> Kelola Pengguna</a>
            <a href="admin_jadwal.php"><i class="fas fa-calendar-alt"></i> Kelola Jadwal</a>
            <a href="admin_pembayaran.php"><i class="fas fa-money-bill-wave"></i> Pembayaran</a>
            <a href="admin_paket.php"><i class="fas fa-box-open"></i> Kelola Paket</a>
            <a href="admin_hasil_foto.php"><i class="fas fa-camera"></i> Kelola Foto & Promo</a>
            <a href="admin_laporan.php"><i class="fas fa-file-alt"></i> Kelola Laporan</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content" id="main-content">
        <h2>Data Pembayaran Pelanggan</h2>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-striped" id="datatables">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 15%;">Nama</th>
                            <th style="width: 15%;">Paket</th>
                            <th style="width: 10%;">Tgl Konfirmasi</th>
                            <th style="width: 10%;">Total</th>
                            <th style="width: 10%;">DP</th>
                            <th style="width: 10%;">Sisa</th>
                            <th style="width: 15%;">Bukti Bayar</th>
                            <th style="width: 15%;">Bukti Bayar Sisa</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 15%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $base_url = "http://localhost/fotostudio-main/";
                        $current_date = date('Y-m-d H:i:s');
                        $query = mysqli_query($koneksi, "
                            SELECT b.*, u.nama_lengkap, p.nama_paket, p.harga AS harga_asli, p.harga_diskon, p.harga
                            FROM tb_bayar b 
                            JOIN user u ON b.user_id = u.user_id
                            JOIN tb_paket p ON b.id_paket = p.id_paket
                        ");

                        while ($data = mysqli_fetch_assoc($query)) {
                            // Hitung total berdasarkan promo jika ada dan masih aktif
                            $base_price = $data['harga'] > 0 ? $data['harga'] : $data['harga_asli'];
                            $total = $data['total_harga'] > 0 ? $data['total_harga'] : $base_price;
                            $promo_query = mysqli_query($koneksi, "SELECT persentase_diskon FROM tb_promo WHERE id_paket = '{$data['id_paket']}' AND '$current_date' BETWEEN tanggal_mulai AND tanggal_selesai");
                            if ($promo = mysqli_fetch_assoc($promo_query)) {
                                $diskon = $base_price * ($promo['persentase_diskon'] / 100);
                                $total = $base_price - $diskon;
                            } elseif ($data['harga_diskon'] > 0 && $data['harga_diskon'] < $base_price && $data['status'] != 'LUNAS') {
                                $total = $base_price;
                            }

                            // Penyesuaian khusus untuk status LUNAS: gunakan DP sebagai total jika sisa = 0
                            if ($data['status'] == 'LUNAS' && $data['sisa'] == 0 && $data['total_harga'] == 0.00) {
                                $total = $data['dp'];
                            }

                            // Update total_harga di tb_bayar jika masih 0.00 atau tidak sesuai
                            if ($data['total_harga'] == 0.00 || $data['total_harga'] != $total) {
                                mysqli_query($koneksi, "UPDATE tb_bayar SET total_harga = '$total' WHERE id_bayar = '{$data['id_bayar']}'");
                                $data['total_harga'] = $total;
                            }

                            $total_formatted = number_format($total, 0, ',', '.');
                            $status_class = ($data['status'] == 'LUNAS') ? 'status-lunas' : 
                                           (($data['status'] == 'DITOLAK') ? 'status-pending' : 
                                           (($data['status'] == 'DP DIBAYAR') ? 'status-dp' : 'status-pending'));
                            $status = "<span class='$status_class'>" . str_replace('_', ' ', $data['status']) . "</span>";
                            $dp = number_format($data['dp'], 0, ',', '.');
                            $sisa = max(0, $total - $data['dp']);
                            $sisa_formatted = number_format($sisa, 0, ',', '.');

                            // Pengecekan dan penyesuaian tgl_konfir (hanya tanggal)
                            $tgl_konfir_display = '-';
                            if ($data['tgl_konfir'] && $data['tgl_konfir'] != '0000-00-00 00:00:00') {
                                $tgl_konfir_display = date('d M Y', strtotime($data['tgl_konfir']));
                            }

                            echo "<tr>
                                <td>{$no}</td>
                                <td>" . htmlspecialchars($data['nama_lengkap']) . "</td>
                                <td>" . htmlspecialchars($data['nama_paket']) . "</td>
                                <td>{$tgl_konfir_display}</td>
                                <td>Rp {$total_formatted}</td>
                                <td>Rp {$dp}</td>
                                <td>Rp {$sisa_formatted}</td>
                                <td>";
                            if (!empty($data['upload_slip'])) {
                                $file_path = "img/buktibayar/{$data['upload_slip']}";
                                if (file_exists($file_path)) {
                                    echo "<a href='{$base_url}{$file_path}' target='_blank'>
                                            <img src='{$base_url}{$file_path}' alt='Bukti Pembayaran' style='max-width: 50px; max-height: 50px;'>
                                          </a>";
                                } else {
                                    echo "<span style='color: red; font-size: 12px;'>File Tidak Ditemukan</span>";
                                }
                            } else {
                                echo "<span style='color: gray; font-size: 12px;'>Tidak Ada</span>";
                            }

                            echo "</td>
                                <td>";
                            if (!empty($data['upload_slip_sisa'])) {
                                $file_path_sisa = "img/buktibayar/{$data['upload_slip_sisa']}";
                                if (file_exists($file_path_sisa)) {
                                    echo "<a href='{$base_url}{$file_path_sisa}' target='_blank'>
                                            <img src='{$base_url}{$file_path_sisa}' alt='Bukti Pembayaran Sisa' style='max-width: 50px; max-height: 50px;'>
                                          </a>";
                                } else {
                                    echo "<span style='color: red; font-size: 12px;'>File Tidak Ditemukan</span>";
                                }
                            } else {
                                echo "<span style='color: gray; font-size: 12px;'>Tidak Ada</span>";
                            }

                            echo "</td>
                                <td>{$status}</td>
                                <td>";
                            if ($data['status'] == 'MENUNGGU KONFIRMASI') {
                                echo "<form method='POST' action='konfirmasi_pembayaran.php' style='display: inline;'>
                                        <input type='hidden' name='id_bayar' value='{$data['id_bayar']}'>
                                        <button type='submit' name='action' value='konfirmasi' class='btn btn-success btn-action btn-sm me-1'><i class='fas fa-check'></i> Konfirmasi</button>
                                        <button type='submit' name='action' value='tolak' class='btn btn-danger btn-action btn-sm me-1'><i class='fas fa-times'></i> Tolak</button>
                                      </form>";
                            } elseif ($data['status'] == 'DP DIBAYAR' && $sisa > 0) {
                                echo "<form method='POST' action='' style='display: inline;'>
                                        <input type='hidden' name='id_bayar' value='{$data['id_bayar']}'>
                                        <button type='submit' name='action' value='konfirmasi_sisa' class='btn btn-success btn-action btn-sm me-1'><i class='fas fa-check'></i> Konfirmasi Sisa</button>
                                      </form>";
                            } else {
                                echo "<span style='color: gray; font-size: 12px;'>Selesai</span>";
                            }
                            // Tombol Hapus
                            echo "<form method='POST' action='' style='display: inline;'>
                                    <input type='hidden' name='id_bayar' value='{$data['id_bayar']}'>
                                    <button type='submit' name='action' value='hapus' class='btn btn-warning btn-action btn-sm' onclick='return confirm(\"Apakah Anda yakin ingin menghapus data ini?\")'><i class='fas fa-trash'></i> Hapus</button>
                                  </form>";
                            echo "</td>
                            </tr>";
                            $no++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#datatables').DataTable({
                "pageLength": 10,
                "lengthMenu": [5, 10, 25, 50],
                "ordering": true,
                "searching": true,
                "paging": true,
                "info": true
            });
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const hamburgerIcon = document.querySelector('.hamburger i');
            
            sidebar.classList.toggle('open');
            mainContent.classList.toggle('shifted');

            if (sidebar.classList.contains('open')) {
                hamburgerIcon.classList.remove('fa-bars');
                hamburgerIcon.classList.add('fa-times');
            } else {
                hamburgerIcon.classList.remove('fa-times');
                hamburgerIcon.classList.add('fa-bars');
            }
        }
    </script>
</body>
</html>