<?php
session_start();
include 'koneksi.php';

// Cek akses fotografer
if (!isset($_SESSION['level']) || $_SESSION['level'] != "fotografer") {
    echo '<script>alert("Anda tidak punya akses!"); window.location="index.php";</script>';
    exit();
}

// Debugging: Pastikan user_id ada di sesi
if (!isset($_SESSION['user_id'])) {
    error_log("Session user_id tidak diatur!");
    echo '<script>alert("Sesi tidak valid. Silakan login kembali."); window.location="index.php";</script>';
    exit();
}

$user_id = intval($_SESSION['user_id']);
error_log("Fotografer.php - Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'tidak diatur'));

// Ambil data fotografer
$query = "SELECT * FROM user WHERE user_id = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$fotografer = $result->fetch_assoc();
if (!$fotografer) {
    error_log("Fotografer dengan user_id $user_id tidak ditemukan!");
    echo '<script>alert("Akun fotografer tidak ditemukan!"); window.location="index.php";</script>';
    exit();
}

// Handle Update Status
if (isset($_GET['action']) && isset($_GET['id_jadwal'])) {
    $id_jadwal = intval($_GET['id_jadwal']);
    $action = $_GET['action'];
    $new_status = '';
    $alasan_tolak = isset($_POST['alasan_tolak']) ? htmlspecialchars($_POST['alasan_tolak'], ENT_QUOTES, 'UTF-8') : '';

    switch ($action) {
        case 'konfirmasi':
            $new_status = 'Dikonfirmasi';
            $alasan_tolak = ''; // Reset alasan_tolak jika konfirmasi
            break;
        case 'tolak':
            if (empty($alasan_tolak)) {
                echo '<div id="notification" style="position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; z-index: 1000;">Alasan penolakan wajib diisi!</div><script>setTimeout(() => document.getElementById(\'notification\').style.display = \'none\', 3000);</script>';
                echo '<script>window.location="fotografer.php?action=tolak&id_jadwal=' . $id_jadwal . '";</script>';
                exit();
            }
            $new_status = 'Ditolak';
            break;
        case 'mulai':
            $new_status = 'Sedang Berlangsung';
            break;
        case 'selesai':
            $new_status = 'Pemotretan Selesai';
            break;
        default:
            echo '<div id="notification" style="position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; z-index: 1000;">Aksi tidak valid!</div><script>setTimeout(() => document.getElementById(\'notification\').style.display = \'none\', 3000);</script>';
            echo '<script>window.location="fotografer.php";</script>';
            exit();
    }

    error_log("User ID: $user_id, ID Jadwal: $id_jadwal, Action: $action, New Status: $new_status, Alasan Tolak: $alasan_tolak");

    if ($action === 'tolak') {
        $stmt = $koneksi->prepare("UPDATE jadwal_pemotretan SET status = ?, alasan_tolak = ? WHERE id_jadwal = ? AND id_fotografer = ?");
        $stmt->bind_param("ssii", $new_status, $alasan_tolak, $id_jadwal, $user_id);
    } elseif ($action === 'konfirmasi') {
        $stmt = $koneksi->prepare("UPDATE jadwal_pemotretan SET status = ?, alasan_tolak = ? WHERE id_jadwal = ? AND id_fotografer = ?");
        $stmt->bind_param("ssii", $new_status, $alasan_tolak, $id_jadwal, $user_id);
    } else {
        $stmt = $koneksi->prepare("UPDATE jadwal_pemotretan SET status = ? WHERE id_jadwal = ? AND id_fotografer = ?");
        $stmt->bind_param("sii", $new_status, $id_jadwal, $user_id);
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo '<div id="notification" style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; z-index: 1000;">Status berhasil diperbarui menjadi ' . $new_status . '!</div><script>setTimeout(() => document.getElementById(\'notification\').style.display = \'none\', 3000);</script>';
            echo '<script>window.location="fotografer.php";</script>';
        } else {
            $check_stmt = $koneksi->prepare("SELECT * FROM jadwal_pemotretan WHERE id_jadwal = ? AND id_fotografer = ?");
            $check_stmt->bind_param("ii", $id_jadwal, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows == 0) {
                error_log("Jadwal dengan id_jadwal $id_jadwal dan id_fotografer $user_id tidak ditemukan!");
                echo '<div id="notification" style="position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; z-index: 1000;">Jadwal tidak ditemukan atau Anda tidak memiliki akses untuk jadwal ini.</div><script>setTimeout(() => document.getElementById(\'notification\').style.display = \'none\', 3000);</script>';
            } else {
                error_log("Tidak ada baris yang diperbarui meskipun jadwal ditemukan.");
                echo '<div id="notification" style="position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; z-index: 1000;">Gagal memperbarui status: Tidak ada perubahan dilakukan.</div><script>setTimeout(() => document.getElementById(\'notification\').style.display = \'none\', 3000);</script>';
            }
            $check_stmt->close();
            echo '<script>window.location="fotografer.php";</script>';
        }
    } else {
        error_log("Error SQL: " . $stmt->error);
        echo '<div id="notification" style="position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; z-index: 1000;">Terjadi kesalahan saat memperbarui status: ' . $stmt->error . '</div><script>setTimeout(() => document.getElementById(\'notification\').style.display = \'none\', 3000);</script>';
        echo '<script>window.location="fotografer.php";</script>';
    }
    $stmt->close();
    exit();
}

// Ambil jadwal pemotretan untuk fotografer ini dengan rincian dari tb_paket dan lokasi dari tb_pesan
$jadwals = $koneksi->prepare("SELECT jp.*, p.nama_paket, p.rincian, dpr.catatan, jp.alasan_tolak, tp.alamat AS lokasi 
                              FROM jadwal_pemotretan jp 
                              LEFT JOIN tb_detail_pesan_reg dpr ON jp.id_pesan = dpr.id_pesan 
                              LEFT JOIN tb_paket p ON dpr.id_paket = p.id_paket 
                              LEFT JOIN tb_pesan tp ON jp.id_pesan = tp.id_pesan 
                              WHERE jp.id_fotografer = ?");
$jadwals->bind_param("i", $user_id);
$jadwals->execute();
$result = $jadwals->get_result();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pemotretan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        html, body { background: #f0f4f8; min-height: 100vh; color: #333; overflow-x: hidden; }
        .sidebar { width: 250px; background: #1a1a1a; color: white; height: 100vh; position: fixed; top: 0; left: -250px; transition: left 0.3s ease-in-out; display: flex; flex-direction: column; padding: 20px; box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2); z-index: 1000; }
        .sidebar.open { left: 0; }
        .sidebar-header { display: flex; flex-direction: column; align-items: center; margin-bottom: 30px; }
        .sidebar-header img { width: 60px; height: 60px; border-radius: 50%; margin-bottom: 10px; border: 2px solid #1e3a8a; }
        .sidebar-header h3 { font-size: 16px; font-weight: 600; }
        .sidebar-header p { font-size: 12px; color: #bbb; }
        .sidebar-menu { flex-grow: 1; }
        .sidebar-menu a { display: flex; align-items: center; gap: 10px; color: #ddd; text-decoration: none; padding: 12px 15px; font-size: 14px; border-radius: 5px; margin-bottom: 5px; transition: background 0.3s ease; }
        .sidebar-menu a:hover { background: #2a2a2a; }
        .sidebar-menu a i { color: #1e3a8a; }
        .sidebar-menu .logout { margin-top: auto; }
        .hamburger { position: fixed; top: 15px; left: 15px; font-size: 24px; background: none; border: none; color: #1e3a8a; cursor: pointer; z-index: 1101; }
        .main-content { margin-left: 0; transition: margin-left 0.3s ease-in-out; padding: 30px; position: relative; z-index: 1; }
        .main-content.shifted { margin-left: 250px; }
        h2 { margin-bottom: 20px; text-align: center; color: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); border-radius: 10px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; }
        th { background-color: #1e3a8a; color: white; }
        td { border-bottom: 1px solid #ddd; }
        .btn { padding: 8px 15px; text-decoration: none; border: none; cursor: pointer; border-radius: 5px; margin-right: 5px; transition: background 0.3s ease; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-success:hover { background-color: #218838; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-danger:hover { background-color: #c82333; }
        .btn-info { background-color: #007bff; color: white; }
        .btn-info:hover { background-color: #0056b3; }
        .status-menunggu-konfirmasi { background-color: #f0ad4e; color: white; padding: 5px 10px; border-radius: 5px; display: inline-block; }
        .status-dikonfirmasi { background-color: #28a745; color: white; padding: 5px 10px; border-radius: 5px; display: inline-block; }
        .status-sedang-berlangsung { background-color: #007bff; color: white; padding: 5px 10px; border-radius: 5px; display: inline-block; }
        .status-pemotretan-selesai { background-color: #17a2b8; color: white; padding: 5px 10px; border-radius: 5px; display: inline-block; }
        .status-ditolak { background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 5px; display: inline-block; }
        #notification { position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; z-index: 1000; display: none; }
        .filter-section { margin-bottom: 20px; text-align: right; }
        .filter-section select { padding: 8px; border-radius: 5px; border: 1px solid #ddd; font-size: 14px; }
        @media (max-width: 600px) { table { display: block; overflow-x: auto; } th, td { min-width: 120px; white-space: nowrap; } }
        @media (max-width: 768px) { .sidebar { width: 200px; left: -200px; } .main-content.shifted { margin-left: 200px; } }
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 5px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover, .close:focus { color: black; text-decoration: none; }
        .modal-btn { padding: 10px 20px; margin-top: 10px; }
    </style>
</head>
<body>
    <button class="hamburger" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo $fotografer['foto_profil'] ?? 'https://via.placeholder.com/60'; ?>" alt="Profil Fotografer">
            <h3><?php echo htmlspecialchars($fotografer['nama_lengkap']); ?></h3>
            <p>Fotografer</p>
        </div>
        <div class="sidebar-menu">
            <a href="fotografer_profil.php"><i class="fas fa-user"></i> Profil</a>
            <a href="fotografer.php"><i class="fas fa-calendar-alt"></i> Jadwal Pemotretan</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content" id="main-content">
        <h2>Jadwal Pemotretan</h2>

        <div class="filter-section">
            <label for="filterStatus">Filter Status: </label>
            <select id="filterStatus" onchange="filterTable()">
                <option value="">Semua</option>
                <option value="Menunggu Konfirmasi">Menunggu Konfirmasi</option>
                <option value="Dikonfirmasi">Dikonfirmasi</option>
                <option value="Sedang Berlangsung">Sedang Berlangsung</option>
                <option value="Pemotretan Selesai">Pemotretan Selesai</option>
                <option value="Ditolak">Ditolak</option>
            </select>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Pelanggan</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                    <th>Paket</th> <!-- Kolom baru untuk nama paket -->
                    <th>Rincian</th>
                    <th>Catatan</th>
                    <th>Alasan Tolak</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            while ($row = $result->fetch_assoc()) {
                $status = $row['status'] ?: 'Menunggu Konfirmasi';
                $lokasi = isset($row['lokasi']) && $row['lokasi'] !== '' ? htmlspecialchars($row['lokasi']) : '-';
                $nama_paket = isset($row['nama_paket']) && $row['nama_paket'] !== '' ? htmlspecialchars($row['nama_paket']) : 'Tidak Diketahui';
                $rincian = isset($row['rincian']) && $row['rincian'] !== '' ? htmlspecialchars($row['rincian']) : 'Tidak Ada Rincian';
                $catatan = isset($row['catatan']) && $row['catatan'] !== '' ? htmlspecialchars($row['catatan']) : '-';
                $alasan_tolak = isset($row['alasan_tolak']) && $row['alasan_tolak'] !== '' ? htmlspecialchars($row['alasan_tolak']) : '-';
                echo "<tr>
                    <td>" . $no . "</td>
                    <td>" . htmlspecialchars($row['nama_pelanggan']) . "</td>
                    <td>" . htmlspecialchars($row['tanggal']) . "</td>
                    <td>" . htmlspecialchars($row['waktu']) . "</td>
                    <td>" . $lokasi . "</td>
                    <td><span class='status-" . strtolower(str_replace(' ', '-', $status)) . "'>" . htmlspecialchars($status) . "</span></td>
                    <td>" . $nama_paket . "</td> <!-- Tampilkan nama paket -->
                    <td>" . $rincian . "</td>
                    <td>" . $catatan . "</td>
                    <td>" . $alasan_tolak . "</td>
                    <td>";
                if ($status == 'Menunggu Konfirmasi') {
                    echo "<button onclick=\"openTolakModal('" . $row['id_jadwal'] . "')\" class='btn btn-danger'>Tolak Pemotretan</button>";
                    echo "<a href='fotografer.php?action=konfirmasi&id_jadwal=" . $row['id_jadwal'] . "' class='btn btn-success'>Konfirmasi Pemotretan</a>";
                } elseif ($status == 'Dikonfirmasi') {
                    echo "<a href='fotografer.php?action=mulai&id_jadwal=" . $row['id_jadwal'] . "' class='btn btn-info'>Mulai Pemotretan</a>";
                } elseif ($status == 'Sedang Berlangsung') {
                    echo "<a href='fotografer.php?action=selesai&id_jadwal=" . $row['id_jadwal'] . "' class='btn btn-success'>Selesaikan Pemotretan</a>";
                } else {
                    echo "-";
                }
                echo "</td>
                </tr>";
                $no++;
            }
            ?>
            </tbody>
        </table>
    </div>

    <div id="tolakModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeTolakModal()">×</span>
            <h3>Masukkan Alasan Penolakan</h3>
            <form method="POST" action="" id="tolakForm">
                <input type="hidden" name="id_jadwal" id="modalIdJadwal">
                <textarea name="alasan_tolak" rows="4" cols="50" placeholder="Masukkan alasan penolakan..." required></textarea>
                <button type="submit" class="btn btn-danger modal-btn">Kirim</button>
            </form>
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

        function filterTable() {
            const filter = document.getElementById('filterStatus').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const status = row.querySelector('td:nth-child(6) span').textContent.toLowerCase();
                row.style.display = filter === '' || status.includes(filter) ? '' : 'none';
            });
        }

        function openTolakModal(id_jadwal) {
            document.getElementById('modalIdJadwal').value = id_jadwal;
            document.getElementById('tolakModal').style.display = 'block';
        }

        function closeTolakModal() {
            document.getElementById('tolakModal').style.display = 'none';
            document.getElementById('tolakForm').reset();
        }

        document.getElementById('tolakForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id_jadwal = document.getElementById('modalIdJadwal').value;
            const formData = new FormData(this);
            fetch(`fotografer.php?action=tolak&id_jadwal=${id_jadwal}`, {
                method: 'POST',
                body: formData
            }).then(response => response.text()).then(() => {
                closeTolakModal();
                window.location.reload();
            }).catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengirim data.');
                closeTolakModal();
            });
        });

        window.onclick = function(event) {
            const modal = document.getElementById('tolakModal');
            if (event.target == modal) {
                closeTolakModal();
            }
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$jadwals->close();
$koneksi->close();
?>