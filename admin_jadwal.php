<?php
session_start();
include "koneksi.php";

// Cek akses admin
if (!isset($_SESSION['level']) || $_SESSION['level'] != "admin") {
    echo '<script>alert("Anda tidak punya akses!"); window.location="index.php";</script>';
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE user_id=?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

// Handle Add/Edit Jadwal
if (isset($_POST['simpan'])) {
    $id_jadwal = isset($_POST['id_jadwal']) ? intval($_POST['id_jadwal']) : 0;
    $id_fotografer = intval($_POST['id_fotografer']);
    $id_pesan = intval($_POST['id_pesan']);
    $nama_pelanggan = htmlspecialchars($_POST['nama_pelanggan'], ENT_QUOTES, 'UTF-8');
    $tanggal = htmlspecialchars($_POST['tanggal'], ENT_QUOTES, 'UTF-8');
    $waktu = htmlspecialchars($_POST['waktu'], ENT_QUOTES, 'UTF-8');
    $status = !empty($_POST['status']) ? htmlspecialchars($_POST['status'], ENT_QUOTES, 'UTF-8') : 'Menunggu Konfirmasi';

    // Validasi tanggal tidak boleh kurang dari hari ini
    $current_date = date('Y-m-d');
    if ($tanggal < $current_date) {
        echo '<script>alert("Tanggal tidak boleh kurang dari hari ini!"); window.location="admin_jadwal.php";</script>';
        exit();
    }

    if ($id_jadwal == 0) {
        $stmt = $koneksi->prepare("INSERT INTO jadwal_pemotretan (id_fotografer, id_pesan, nama_pelanggan, tanggal, waktu, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $id_fotografer, $id_pesan, $nama_pelanggan, $tanggal, $waktu, $status);
        if ($stmt->execute()) {
            $stmt->close();
            echo '<script>alert("Jadwal berhasil ditambahkan dan dikirim ke fotografer!"); window.location="admin_jadwal.php";</script>';
        } else {
            $stmt->close();
            echo '<script>alert("Gagal menambahkan jadwal!"); window.location="admin_jadwal.php";</script>';
        }
    } else {
        $stmt = $koneksi->prepare("UPDATE jadwal_pemotretan SET id_fotografer=?, id_pesan=?, nama_pelanggan=?, tanggal=?, waktu=?, status=? WHERE id_jadwal=?");
        $stmt->bind_param("iissssi", $id_fotografer, $id_pesan, $nama_pelanggan, $tanggal, $waktu, $status, $id_jadwal);
        if ($stmt->execute()) {
            $stmt->close();
            echo '<script>alert("Jadwal berhasil diperbarui!"); window.location="admin_jadwal.php";</script>';
        } else {
            $stmt->close();
            echo '<script>alert("Gagal memperbarui jadwal!"); window.location="admin_jadwal.php";</script>';
        }
    }
    exit();
}

// Handle Delete
if (isset($_GET['hapus'])) {
    $id_jadwal = intval($_GET['hapus']);
    $stmt = $koneksi->prepare("DELETE FROM jadwal_pemotretan WHERE id_jadwal = ?");
    $stmt->bind_param("i", $id_jadwal);
    if ($stmt->execute()) {
        $stmt->close();
        echo '<script>alert("Jadwal berhasil dihapus!"); window.location="admin_jadwal.php";</script>';
    } else {
        $stmt->close();
        echo '<script>alert("Gagal menghapus jadwal!"); window.location="admin_jadwal.php";</script>';
    }
    exit();
}

// Get data untuk edit jika ada
$edit = null;
if (isset($_GET['edit'])) {
    $id_jadwal = intval($_GET['edit']);
    $stmt = $koneksi->prepare("SELECT jp.*, p.id_paket, p.nama_paket, p.rincian, COALESCE(dpr.catatan, '-') AS catatan 
                               FROM jadwal_pemotretan jp 
                               LEFT JOIN tb_pesan tp ON jp.id_pesan = tp.id_pesan 
                               LEFT JOIN tb_detail_pesan_reg dpr ON tp.id_pesan = dpr.id_pesan 
                               LEFT JOIN tb_paket p ON dpr.id_paket = p.id_paket 
                               WHERE jp.id_jadwal=?");
    $stmt->bind_param("i", $id_jadwal);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit = $result->fetch_assoc();
    $stmt->close();
}

// Ambil daftar fotografer
$fotografers = $koneksi->query("SELECT user_id, nama_lengkap FROM user WHERE level='fotografer' AND status='on'");

// Ambil daftar pelanggan dari tb_pesan dan tb_detail_pesan_reg
$pelanggans = $koneksi->query("SELECT p.id_pesan, p.nama_penerima, d.tgl_main, d.jam_main, d.id_paket, pk.nama_paket, pk.rincian, COALESCE(d.catatan, '-') AS catatan 
                               FROM tb_pesan p 
                               JOIN tb_detail_pesan_reg d ON p.id_pesan = d.id_pesan 
                               JOIN tb_paket pk ON d.id_paket = pk.id_paket 
                               WHERE p.id_pesan NOT IN (SELECT id_pesan FROM jadwal_pemotretan WHERE id_pesan IS NOT NULL)");
$pelanggan_data = [];
while ($row = $pelanggans->fetch_assoc()) {
    $pelanggan_data[] = [
        'id_pesan' => $row['id_pesan'],
        'nama_penerima' => $row['nama_penerima'],
        'tgl_main' => $row['tgl_main'],
        'jam_main' => $row['jam_main'],
        'id_paket' => $row['id_paket'],
        'nama_paket' => $row['nama_paket'],
        'rincian' => $row['rincian'],
        'catatan' => $row['catatan']
    ];
}

// Pencarian dan Pagination
$search = isset($_GET['search']) ? $koneksi->real_escape_string($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = $search ? "WHERE jp.nama_pelanggan LIKE ? OR u.nama_lengkap LIKE ? OR p.nama_paket LIKE ?" : '';
$param = $search ? "%$search%" : '';
$stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM jadwal_pemotretan jp 
                           LEFT JOIN user u ON jp.id_fotografer = u.user_id 
                           LEFT JOIN tb_pesan tp ON jp.id_pesan = tp.id_pesan 
                           LEFT JOIN tb_detail_pesan_reg dpr ON tp.id_pesan = dpr.id_pesan 
                           LEFT JOIN tb_paket p ON dpr.id_paket = p.id_paket $where");
if ($search) {
    $stmt->bind_param("sss", $param, $param, $param);
}
$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$stmt->close();

$stmt = $koneksi->prepare("SELECT jp.*, u.nama_lengkap AS nama_fotografer, p.nama_paket, p.rincian, COALESCE(dpr.catatan, '-') AS catatan, jp.alasan_tolak 
                           FROM jadwal_pemotretan jp 
                           LEFT JOIN user u ON jp.id_fotografer = u.user_id 
                           LEFT JOIN tb_pesan tp ON jp.id_pesan = tp.id_pesan 
                           LEFT JOIN tb_detail_pesan_reg dpr ON tp.id_pesan = dpr.id_pesan 
                           LEFT JOIN tb_paket p ON dpr.id_paket = p.id_paket $where LIMIT ? OFFSET ?");
if ($search) {
    $stmt->bind_param("sssii", $param, $param, $param, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$data_jadwal = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal Pemotretan</title>
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
            margin: 0 !important;
            padding: 0 !important;
            position: relative;
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
            all: initial !important;
            position: fixed !important;
            top: 15px !important;
            left: 15px !important;
            font-size: 24px !important;
            background: none !important;
            border: none !important;
            color: #1e3a8a !important;
            cursor: pointer !important;
            z-index: 1101 !important;
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            transform: translateZ(0) !important;
            font-family: 'Font Awesome 5 Free' !important;
            font-weight: 900 !important;
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

        .jadwal-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h1, h3 {
            margin-bottom: 20px;
            text-align: center;
            color: #1e3a8a;
        }

        .table-controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .dataTables_length label,
        .dataTables_filter label {
            font-weight: normal;
            color: #333;
        }

        .dataTables_length select,
        .dataTables_filter input {
            padding: 5px 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            margin-left: 5px;
            width: 60px;
        }

        .dataTables_filter input {
            width: 200px;
        }

        .dataTables_length select:focus,
        .dataTables_filter input:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 5px rgba(30, 58, 138, 0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #1e3a8a;
            color: white;
        }

        td {
            border-bottom: 1px solid #ddd;
        }

        th:last-child, td:last-child {
            width: 150px;
            text-align: center;
        }

        .btn-hapus {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s ease;
            display: inline-block;
            margin: 0 2px;
            vertical-align: middle;
            line-height: 1;
        }

        .btn-hapus:hover {
            background: #c82333;
        }

        .btn-edit {
            background: #ffc107;
            color: white;
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 0;
            transition: background 0.3s ease;
            display: inline-block;
            margin: 0 2px;
            vertical-align: middle;
            line-height: 1;
        }

        .btn-edit:hover {
            background: #e0a800;
        }

        .edit-form {
            display: none;
            max-width: 600px;
            width: 90%;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 1001;
            max-height: 80vh;
            overflow-y: auto;
        }

        .edit-form.active {
            display: block;
        }

        form {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
        }

        button {
            background: #1e3a8a;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border: none;
            transition: background 0.3s ease;
            padding: 10px;
            margin-right: 10px;
        }

        button:hover {
            background: #163068;
        }

        .pagination-container {
            margin-top: 15px;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .page-numbers {
            display: flex;
            gap: 5px;
        }

        .pagination button {
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #e9ecef;
            cursor: pointer;
            font-size: 14px;
            color: #333;
            min-width: 40px;
            text-align: center;
        }

        .pagination button:disabled {
            background: #e9ecef;
            cursor: not-allowed;
            color: #6c757d;
        }

        .pagination button.active {
            background: #1e3a8a;
            color: white;
            border-color: #1e3a8a;
        }

        .pagination-info {
            margin-top: 0;
            text-align: left;
            font-size: 14px;
            color: #666;
            margin-left: 10px;
        }

        .btn-tambah {
            background: rgb(255, 255, 255);
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 5px;
        }

        .btn-tambah:hover {
            background: rgb(77, 51, 221);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                left: -200px;
            }

            .main-content.shifted {
                margin-left: 200px;
            }

            .edit-form {
                width: 95%;
                max-height: 90vh;
                padding: 15px;
            }

            .form-group {
                margin-bottom: 10px;
            }

            .form-group select,
            .form-group input,
            .form-group textarea {
                font-size: 14px;
                padding: 8px;
            }

            button {
                font-size: 14px;
                padding: 8px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 8px;
            }

            .dataTables_filter input {
                width: 150px;
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
            <img src="<?php echo $admin['foto_profil'] ?? 'https://via.placeholder.com/60'; ?>" alt="Profil Admin">
            <h3><?php echo htmlspecialchars($admin['nama_lengkap']); ?></h3>
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
        <div class="jadwal-container">
            <h1>Kelola Jadwal Pemotretan</h1>

            <h3>Daftar Jadwal</h3>
            <div class="table-controls">
                <div class="dataTables_length">
                    <label>Show 
                        <select name="limit" onchange="window.location.href='admin_jadwal.php?limit='+this.value+'&page=1&search=<?php echo htmlspecialchars($search); ?>'">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        </select> entries
                    </label>
                </div>
                <div class="dataTables_filter">
                    <label>Search: <input type="search" value="<?php echo htmlspecialchars($search); ?>" onkeyup="if(event.keyCode == 13) window.location.href='admin_jadwal.php?limit=<?php echo $limit; ?>&page=1&search='+this.value"></label>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Fotografer</th>
                        <th>Pelanggan</th>
                        <th>Paket</th>
                        <th>Rincian</th>
                        <th>Catatan</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th>Alasan Tolak</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = $offset + 1;
                    while ($row = $data_jadwal->fetch_assoc()) {
                        echo "<tr>
                            <td>$no</td>
                            <td>" . htmlspecialchars($row['nama_fotografer'] ?? 'Tidak Ditentukan') . "</td>
                            <td>" . htmlspecialchars($row['nama_pelanggan']) . "</td>
                            <td>" . htmlspecialchars($row['nama_paket'] ?? 'Tidak Diketahui') . "</td>
                            <td>" . htmlspecialchars($row['rincian'] ?? 'Tidak Ada Rincian') . "</td>
                            <td>" . htmlspecialchars($row['catatan']) . "</td>
                            <td>" . htmlspecialchars($row['tanggal']) . "</td>
                            <td>" . htmlspecialchars($row['waktu']) . "</td>
                            <td>" . htmlspecialchars($row['status'] ?? 'Menunggu Konfirmasi') . "</td>
                            <td>" . htmlspecialchars($row['alasan_tolak'] ?? '-') . "</td>
                            <td>
                                <a href='?edit={$row['id_jadwal']}' class='btn-edit'>Edit</a>
                                <a href='admin_jadwal.php?hapus={$row['id_jadwal']}' class='btn-hapus' onclick='return confirm(\"Yakin hapus?\");'>Hapus</a>
                            </td>
                        </tr>";
                        $no++;
                    }
                    ?>
                </tbody>
            </table>
            <div class="pagination-container">
                <div class="pagination-info">Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_rows); ?> of <?php echo $total_rows; ?> entries</div>
                <div class="pagination">
                    <button onclick="window.location.href='admin_jadwal.php?limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?>&search=<?php echo htmlspecialchars($search); ?>'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
                    <div class="page-numbers">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <button onclick="window.location.href='admin_jadwal.php?limit=<?php echo $limit; ?>&page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>'" <?php echo $i == $page ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                        <?php endfor; ?>
                    </div>
                    <button onclick="window.location.href='admin_jadwal.php?limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?>&search=<?php echo htmlspecialchars($search); ?>'" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Next</button>
                    <button class="btn-tambah" onclick="showAddForm()">Tambah Jadwal</button>
                </div>
            </div>

            <!-- Form Tambah Jadwal (Popup) -->
            <div class="edit-form add-form" id="add-form">
                <h3>Tambah Jadwal</h3>
                <form method="POST">
                    <input type="hidden" name="id_jadwal" value="<?php echo $edit ? $edit['id_jadwal'] : ''; ?>">
                    <div class="form-group">
                        <label for="id_fotografer">Fotografer</label>
                        <select name="id_fotografer" id="id_fotografer" required>
                            <option value="">-- Pilih Fotografer --</option>
                            <?php
                            $fotografers->data_seek(0);
                            while ($fotografer = $fotografers->fetch_assoc()): ?>
                                <option value="<?php echo $fotografer['user_id']; ?>" <?php echo ($edit && $edit['id_fotografer'] == $fotografer['user_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($fotografer['nama_lengkap']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_pesan">Pesanan</label>
                        <select name="id_pesan" id="id_pesan" required onchange="fillSchedule()">
                            <option value="">-- Pilih Pesanan --</option>
                            <?php foreach ($pelanggan_data as $pelanggan): ?>
                                <option value="<?php echo $pelanggan['id_pesan']; ?>" 
                                        data-nama="<?php echo htmlspecialchars($pelanggan['nama_penerima']); ?>" 
                                        data-tgl="<?php echo $pelanggan['tgl_main']; ?>" 
                                        data-jam="<?php echo $pelanggan['jam_main']; ?>" 
                                        data-paket="<?php echo htmlspecialchars($pelanggan['nama_paket']); ?>" 
                                        data-rincian="<?php echo htmlspecialchars($pelanggan['rincian']); ?>" 
                                        data-catatan="<?php echo htmlspecialchars($pelanggan['catatan']); ?>" 
                                        <?php echo ($edit && $edit['id_pesan'] == $pelanggan['id_pesan']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pelanggan['nama_penerima']) . ' - ' . htmlspecialchars($pelanggan['nama_paket']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nama_pelanggan">Nama Pelanggan</label>
                        <input type="text" name="nama_pelanggan" id="nama_pelanggan" required readonly value="<?php echo $edit ? htmlspecialchars($edit['nama_pelanggan']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="nama_paket">Nama Paket</label>
                        <input type="text" name="nama_paket" id="nama_paket" readonly value="<?php echo $edit ? htmlspecialchars($edit['nama_paket']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="rincian_paket">Rincian Paket</label>
                        <textarea name="rincian_paket" id="rincian_paket" readonly><?php echo $edit ? htmlspecialchars($edit['rincian']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="catatan">Catatan</label>
                        <textarea name="catatan" id="catatan" readonly><?php echo $edit ? htmlspecialchars($edit['catatan']) : '-'; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="tanggal">Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" required value="<?php echo $edit ? $edit['tanggal'] : ''; ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="waktu">Waktu</label>
                        <input type="time" name="waktu" id="waktu" required value="<?php echo $edit ? $edit['waktu'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="Menunggu Konfirmasi" <?php echo ($edit && $edit['status'] == 'Menunggu Konfirmasi') ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                            <option value="Dikonfirmasi" <?php echo ($edit && $edit['status'] == 'Dikonfirmasi') ? 'selected' : ''; ?>>Dikonfirmasi</option>
                            <option value="Ditolak" <?php echo ($edit && $edit['status'] == 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    <button type="submit" name="simpan">Submit</button>
                    <button type="button" onclick="cancelAdd()">Batal</button>
                </form>
            </div>

            <!-- Form Edit Jadwal -->
            <?php if ($edit): ?>
                <div class="edit-form active" id="edit-form">
                    <h3>Edit Jadwal: <?php echo htmlspecialchars($edit['nama_pelanggan']); ?></h3>
                    <form method="POST">
                        <input type="hidden" name="id_jadwal" value="<?php echo $edit['id_jadwal']; ?>">
                        <div class="form-group">
                            <label for="id_fotografer">Fotografer</label>
                            <select name="id_fotografer" id="id_fotografer" required>
                                <option value="">-- Pilih Fotografer --</option>
                                <?php
                                $fotografers->data_seek(0);
                                while ($fotografer = $fotografers->fetch_assoc()): ?>
                                    <option value="<?php echo $fotografer['user_id']; ?>" <?php echo ($edit['id_fotografer'] == $fotografer['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($fotografer['nama_lengkap']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="id_pesan">Pesanan</label>
                            <select name="id_pesan" id="id_pesan" required onchange="fillSchedule()">
                                <option value="">-- Pilih Pesanan --</option>
                                <?php foreach ($pelanggan_data as $pelanggan): ?>
                                    <option value="<?php echo $pelanggan['id_pesan']; ?>" 
                                            data-nama="<?php echo htmlspecialchars($pelanggan['nama_penerima']); ?>" 
                                            data-tgl="<?php echo $pelanggan['tgl_main']; ?>" 
                                            data-jam="<?php echo $pelanggan['jam_main']; ?>" 
                                            data-paket="<?php echo htmlspecialchars($pelanggan['nama_paket']); ?>" 
                                            data-rincian="<?php echo htmlspecialchars($pelanggan['rincian']); ?>" 
                                            data-catatan="<?php echo htmlspecialchars($pelanggan['catatan']); ?>" 
                                            <?php echo ($edit['id_pesan'] == $pelanggan['id_pesan']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pelanggan['nama_penerima']) . ' - ' . htmlspecialchars($pelanggan['nama_paket']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nama_pelanggan">Nama Pelanggan</label>
                            <input type="text" name="nama_pelanggan" id="nama_pelanggan" required readonly value="<?php echo htmlspecialchars($edit['nama_pelanggan']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="nama_paket">Nama Paket</label>
                            <input type="text" name="nama_paket" id="nama_paket" readonly value="<?php echo htmlspecialchars($edit['nama_paket']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="rincian_paket">Rincian Paket</label>
                            <textarea name="rincian_paket" id="rincian_paket" readonly><?php echo htmlspecialchars($edit['rincian']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="catatan">Catatan</label>
                            <textarea name="catatan" id="catatan" readonly><?php echo htmlspecialchars($edit['catatan']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" required value="<?php echo $edit['tanggal']; ?>" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="waktu">Waktu</label>
                            <input type="time" name="waktu" id="waktu" required value="<?php echo $edit['waktu']; ?>">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="Menunggu Konfirmasi" <?php echo $edit['status'] == 'Menunggu Konfirmasi' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                                <option value="Dikonfirmasi" <?php echo $edit['status'] == 'Dikonfirmasi' ? 'selected' : ''; ?>>Dikonfirmasi</option>
                                <option value="Ditolak" <?php echo $edit['status'] == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                            </select>
                        </div>
                        <button type="submit" name="simpan">Simpan Perubahan</button>
                        <button type="button" onclick="cancelEdit()">Batal</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
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

        function cancelEdit() {
            window.location.href = 'admin_jadwal.php';
        }

        function showAddForm() {
            document.getElementById('add-form').classList.add('active');
        }

        function cancelAdd() {
            document.getElementById('add-form').classList.remove('active');
        }

        function fillSchedule() {
            const pesanSelect = document.getElementById('id_pesan');
            const namaPelangganInput = document.getElementById('nama_pelanggan');
            const namaPaketInput = document.getElementById('nama_paket');
            const rincianPaketInput = document.getElementById('rincian_paket');
            const catatanInput = document.getElementById('catatan');
            const tanggalInput = document.getElementById('tanggal');
            const waktuInput = document.getElementById('waktu');

            const selectedOption = pesanSelect.options[pesanSelect.selectedIndex];
            const namaPelanggan = selectedOption.getAttribute('data-nama');
            const namaPaket = selectedOption.getAttribute('data-paket');
            const rincianPaket = selectedOption.getAttribute('data-rincian');
            const catatan = selectedOption.getAttribute('data-catatan') || '-';
            const tglMain = selectedOption.getAttribute('data-tgl');
            const jamMain = selectedOption.getAttribute('data-jam');

            if (namaPelanggan) {
                namaPelangganInput.value = namaPelanggan;
                namaPaketInput.value = namaPaket || '';
                rincianPaketInput.value = rincianPaket || '';
                catatanInput.value = catatan;
                tanggalInput.value = tglMain || '';
                
                if (jamMain && jamMain.match(/^\d{1,2}:\d{2}$/)) {
                    waktuInput.value = jamMain;
                } else if (jamMain && jamMain.match(/^\d{1,2} - \d{1,2}$/)) {
                    const [start] = jamMain.split(' - ');
                    waktuInput.value = start.padStart(2, '0') + ':00';
                } else {
                    waktuInput.value = '';
                    if (jamMain) alert('Format waktu tidak valid: ' + jamMain);
                }
            } else {
                namaPelangganInput.value = '';
                namaPaketInput.value = '';
                rincianPaketInput.value = '';
                catatanInput.value = '-';
                tanggalInput.value = '';
                waktuInput.value = '';
            }
        }
    </script>
</body>
</html>
<?php
if ($koneksi instanceof mysqli && !$koneksi->connect_error) {
    $koneksi->close();
}
?>