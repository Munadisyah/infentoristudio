<?php
session_start();
include("koneksi.php");

if (!isset($_SESSION['level']) || $_SESSION['level'] != "admin") {
    echo '<script>alert("Anda tidak punya akses!"); window.location="index.php";</script>';
    exit();
}

// Tambah Pengguna
if (isset($_POST['tambah_user'])) {
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $email = $_POST['email'];
    $no_telp = $_POST['no_telp'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $level = $_POST['level'];

    $stmt = $koneksi->prepare("INSERT INTO user (nama_lengkap, alamat, email, no_telp, username, password, level, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'on')");
    $stmt->bind_param("sssssss", $nama, $alamat, $email, $no_telp, $username, $password, $level);
    if ($stmt->execute()) {
        echo '<script>alert("Data pengguna berhasil ditambahkan!"); window.location="admin_pengguna.php";</script>';
    } else {
        echo '<script>alert("Gagal menambahkan data!");</script>';
    }
    $stmt->close();
}

// Hapus Pengguna
if (isset($_GET['hapus_user'])) {
    $id = $_GET['hapus_user'];
    $stmt = $koneksi->prepare("DELETE FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo '<script>alert("Data pengguna berhasil dihapus!"); window.location="admin_pengguna.php";</script>';
    } else {
        echo '<script>alert("Gagal menghapus data!");</script>';
    }
    $stmt->close();
}

// Edit Pengguna
if (isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    $nama = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $email = $_POST['email'];
    $no_telp = $_POST['no_telp'];
    $username = $_POST['username'];
    $level = $_POST['level'];
    $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $edit_data['password']; // Gunakan password lama jika kosong

    $stmt = $koneksi->prepare("UPDATE user SET nama_lengkap = ?, alamat = ?, email = ?, no_telp = ?, username = ?, password = ?, level = ? WHERE user_id = ?");
    $stmt->bind_param("sssssssi", $nama, $alamat, $email, $no_telp, $username, $password, $level, $id);
    if ($stmt->execute()) {
        echo '<script>alert("Data pengguna berhasil diperbarui!"); window.location="admin_pengguna.php";</script>';
    } else {
        echo '<script>alert("Gagal memperbarui data! Error: ' . $koneksi->error . '");</script>';
    }
    $stmt->close();
}

// Ambil data pengguna untuk edit jika ada parameter edit
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_data = null;
if ($edit_id) {
    $stmt = $koneksi->prepare("SELECT * FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
}

// Pencarian dan Pagination
$search = isset($_GET['search']) ? $koneksi->real_escape_string($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = $search ? "WHERE nama_lengkap LIKE ? OR email LIKE ? OR no_telp LIKE ?" : '';
$params = $search ? [$search, $search, $search] : [];
$stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM user $where");
if ($search) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
$stmt->close();

$stmt = $koneksi->prepare("SELECT * FROM user $where LIMIT ? OFFSET ?");
if ($search) {
    $types = str_repeat('s', count($params)) . 'ii';
    $all_params = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($types, ...$all_params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Pengguna</title>
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

        .user-container {
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

        input, select, button {
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
                width: 80%;
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
            $stmt = $koneksi->prepare("SELECT * FROM user WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            ?>
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
        <div class="user-container">
            <h1>Kelola Pengguna</h1>

            <h3>Daftar Pengguna</h3>
            <div class="table-controls">
                <div class="dataTables_length">
                    <label>Show 
                        <select name="limit" onchange="window.location.href='admin_pengguna.php?limit='+this.value+'&page=1&search=<?php echo htmlspecialchars($search); ?>'">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        </select> entries
                    </label>
                </div>
                <div class="dataTables_filter">
                    <label>Search: <input type="search" value="<?php echo htmlspecialchars($search); ?>" onkeyup="if(event.keyCode == 13) window.location.href='admin_pengguna.php?limit=<?php echo $limit; ?>&page=1&search='+this.value"></label>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = $offset + 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>$no</td>
                            <td>" . htmlspecialchars($row['nama_lengkap']) . "</td>
                            <td>" . htmlspecialchars($row['email']) . "</td>
                            <td>" . htmlspecialchars($row['no_telp']) . "</td>
                            <td>
                                <a href='?edit={$row['user_id']}' class='btn-edit'>Edit</a>
                                <a href='admin_pengguna.php?hapus_user={$row['user_id']}' class='btn-hapus' onclick='return confirm(\"Yakin hapus?\");'>Hapus</a>
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
                    <button onclick="window.location.href='admin_pengguna.php?limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?>&search=<?php echo htmlspecialchars($search); ?>'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
                    <div class="page-numbers">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <button onclick="window.location.href='admin_pengguna.php?limit=<?php echo $limit; ?>&page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>'" <?php echo $i == $page ? 'class="active"' : ''; ?>><?php echo $i; ?></button>
                        <?php endfor; ?>
                    </div>
                    <button onclick="window.location.href='admin_pengguna.php?limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?>&search=<?php echo htmlspecialchars($search); ?>'" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Next</button>
                    <button class="btn-tambah" onclick="showAddForm()">Tambah Pengguna</button>
                </div>
            </div>

            <!-- Form Tambah Pengguna (Popup) -->
            <div class="edit-form add-form" id="add-form">
                <h3>Tambah Pengguna</h3>
                <form method="POST">
                    <input type="text" name="nama_lengkap" placeholder="Nama Lengkap" required>
                    <input type="text" name="alamat" placeholder="Alamat" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="no_telp" placeholder="No Telepon" required>
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <select name="level" required>
                        <option value="">-- Pilih Level --</option>
                        <option value="admin">Admin</option>
                        <option value="customer">Customer</option>
                        <option value="fotografer">Fotografer</option>
                        <option value="pemilik">Pemilik</option>
                    </select>
                    <button type="submit" name="tambah_user">Tambah Pengguna</button>
                    <button type="button" onclick="cancelAdd()">Batal</button>
                </form>
            </div>

            <!-- Form Edit Pengguna -->
            <?php if ($edit_data): ?>
                <div class="edit-form active" id="edit-form">
                    <h3>Edit Pengguna: <?php echo htmlspecialchars($edit_data['nama_lengkap']); ?></h3>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $edit_data['user_id']; ?>">
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" value="<?php echo htmlspecialchars($edit_data['nama_lengkap']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <input type="text" name="alamat" id="alamat" value="<?php echo htmlspecialchars($edit_data['alamat']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($edit_data['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="no_telp">No Telepon</label>
                            <input type="text" name="no_telp" id="no_telp" value="<?php echo htmlspecialchars($edit_data['no_telp']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($edit_data['username']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password Baru (kosongkan jika tidak ingin ubah)</label>
                            <input type="password" name="password" id="password">
                        </div>
                        <div class="form-group">
                            <label for="level">Level</label>
                            <select name="level" id="level" required>
                                <option value="admin" <?php echo $edit_data['level'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="customer" <?php echo $edit_data['level'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                <option value="fotografer" <?php echo $edit_data['level'] == 'fotografer' ? 'selected' : ''; ?>>Fotografer</option>
                                <option value="pemilik" <?php echo $edit_data['level'] == 'pemilik' ? 'selected' : ''; ?>>Pemilik</option>
                            </select>
                        </div>
                        <button type="submit" name="update_user">Simpan Perubahan</button>
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
            window.location.href = 'admin_pengguna.php';
        }

        function showAddForm() {
            document.getElementById('add-form').classList.add('active');
        }

        function cancelAdd() {
            document.getElementById('add-form').classList.remove('active');
        }
    </script>
</body>
</html>
<?php
if ($koneksi instanceof mysqli && !$koneksi->connect_error) {
    $koneksi->close();
}
?>