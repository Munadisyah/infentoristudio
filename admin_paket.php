<?php
session_start();
include 'koneksi.php';

// Cek akses admin
if (!isset($_SESSION['level']) || $_SESSION['level'] != "admin") {
    echo '<script>alert("Anda tidak punya akses!"); window.location="index.php";</script>';
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE user_id='$user_id'";
$result = mysqli_query($koneksi, $query);
$admin = mysqli_fetch_assoc($result);

// Handle Delete
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $koneksi->prepare("DELETE FROM tb_paket WHERE id_paket = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo '<script>alert("Paket berhasil dihapus!"); window.location="admin_paket.php";</script>';
    exit();
}

// Handle Add/Edit
if (isset($_POST['simpan'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nama = htmlspecialchars($_POST['nama_paket'], ENT_QUOTES, 'UTF-8');
    $rincian = htmlspecialchars($_POST['rincian'], ENT_QUOTES, 'UTF-8');
    $harga = intval($_POST['harga']);
    $jenis = $_POST['jenis'];

    if (!in_array($jenis, ['A', 'B'])) {
        echo '<script>alert("Jenis paket tidak valid! Pilih sesuai Paket A atau Paket B."); window.location="admin_paket.php";</script>';
        exit();
    }

    if ($id == 0) {
        $stmt = $koneksi->prepare("INSERT INTO tb_paket (nama_paket, rincian, harga, jenis) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $nama, $rincian, $harga, $jenis);
        $stmt->execute();
        $stmt->close();
        echo '<script>alert("Paket berhasil ditambahkan!"); window.location="admin_paket.php";</script>';
    } else {
        $stmt = $koneksi->prepare("UPDATE tb_paket SET nama_paket=?, rincian=?, harga=?, jenis=? WHERE id_paket=?");
        $stmt->bind_param("ssisi", $nama, $rincian, $harga, $jenis, $id);
        $stmt->execute();
        $stmt->close();
        echo '<script>alert("Paket berhasil diperbarui!"); window.location="admin_paket.php";</script>';
    }
    exit();
}

// Get data untuk edit jika ada
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $koneksi->prepare("SELECT * FROM tb_paket WHERE id_paket=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit = $result->fetch_assoc();
    $stmt->close();
}

// Jenis paket mapping
$jenis_paket = [
    'A' => "Paket A (Indoor)",
    'B' => "Paket B (Outdoor)"
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Paket Foto</title>
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

        .hamburger i {
            font-style: normal !important;
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

        h2, h3 {
            margin-bottom: 20px;
            text-align: center;
            color: #1e3a8a;
            text-transform: uppercase;
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
            padding: 15px;
            text-align: left;
        }

        th {
            background-color: #1e3a8a;
            color: white;
            font-size: 14px;
        }

        td {
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #777;
            font-style: italic;
        }

        .btn {
            padding: 8px 15px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.3s ease;
            font-size: 14px;
            display: inline-block;
            width: 60px;
            text-align: center;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        form {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        input, select, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
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
        }

        button:hover {
            background: #163068;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                left: -200px;
            }

            .main-content.shifted {
                margin-left: 200px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px;
            }

            .btn {
                width: 50px;
                font-size: 12px;
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
        <h2>Kelola Paket Foto</h2>

        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit ? $edit['id_paket'] : ''; ?>">
            <div>
                <label for="nama_paket">Nama Paket</label>
                <input type="text" name="nama_paket" id="nama_paket" required value="<?php echo $edit ? htmlspecialchars($edit['nama_paket']) : ''; ?>">
            </div>
            <div>
                <label for="rincian">Rincian</label>
                <textarea name="rincian" id="rincian" rows="3" required><?php echo $edit ? htmlspecialchars($edit['rincian']) : ''; ?></textarea>
            </div>
            <div>
                <label for="harga">Harga (Rp)</label>
                <input type="number" name="harga" id="harga" required value="<?php echo $edit ? $edit['harga'] : ''; ?>">
            </div>
            <div>
                <label for="jenis">Jenis</label>
                <select name="jenis" id="jenis" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="A" <?php echo ($edit && $edit['jenis'] == 'A') ? 'selected' : ''; ?>>Paket A (Indoor)</option>
                    <option value="B" <?php echo ($edit && $edit['jenis'] == 'B') ? 'selected' : ''; ?>>Paket B (Outdoor)</option>
                </select>
            </div>
            <button type="submit" name="simpan">Simpan</button>
            <?php if ($edit): ?>
                <a href="admin_paket.php" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </form>

        <h3>Paket A (Indoor)</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Paket</th>
                    <th>Rincian</th>
                    <th>Harga</th>
                    <th>Jenis</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            $data = $koneksi->query("SELECT * FROM tb_paket WHERE jenis = 'A'");
            if ($data->num_rows > 0) {
                while ($row = $data->fetch_assoc()) {
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_paket']); ?></td>
                    <td><?php echo htmlspecialchars($row['rincian']); ?></td>
                    <td>Rp. <?php echo number_format($row['harga']); ?></td>
                    <td><?php echo $jenis_paket[$row['jenis']]; ?></td>
                    <td>
                        <a href="admin_paket.php?edit=<?php echo $row['id_paket']; ?>" class="btn btn-warning">Edit</a>
                        <a href="admin_paket.php?hapus=<?php echo $row['id_paket']; ?>" onclick="return confirm('Yakin ingin hapus?')" class="btn btn-danger">Hapus</a>
                    </td>
                </tr>
            <?php
                }
            } else {
                echo '<tr><td colspan="6" class="no-data">Belum ada data untuk Paket A (Indoor)</td></tr>';
            }
            ?>
            </tbody>
        </table>

        <h3>Paket B (Outdoor)</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Paket</th>
                    <th>Rincian</th>
                    <th>Harga</th>
                    <th>Jenis</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            $data = $koneksi->query("SELECT * FROM tb_paket WHERE jenis = 'B'");
            if ($data->num_rows > 0) {
                while ($row = $data->fetch_assoc()) {
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_paket']); ?></td>
                    <td><?php echo htmlspecialchars($row['rincian']); ?></td>
                    <td>Rp. <?php echo number_format($row['harga']); ?></td>
                    <td><?php echo $jenis_paket[$row['jenis']]; ?></td>
                    <td>
                        <a href="admin_paket.php?edit=<?php echo $row['id_paket']; ?>" class="btn btn-warning">Edit</a>
                        <a href="admin_paket.php?hapus=<?php echo $row['id_paket']; ?>" onclick="return confirm('Yakin ingin hapus?')" class="btn btn-danger">Hapus</a>
                    </td>
                </tr>
            <?php
                }
            } else {
                echo '<tr><td colspan="6" class="no-data">Belum ada data untuk Paket B (Outdoor)</td></tr>';
            }
            ?>
            </tbody>
        </table>
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
    </script>
</body>
</html>