<?php
session_start();
include("koneksi.php");

if (!isset($_SESSION['level']) || $_SESSION['level'] != "admin") {
    echo '<script>alert("Anda tidak punya akses!"); window.location="index.php?page=login";</script>';
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM user WHERE user_id='$user_id'";
$result = mysqli_query($koneksi, $query);
$admin = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Admin Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f0f4f8;
            min-height: 100vh;
            color: #333;
            display: flex;
            flex-direction: column;
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
            z-index: 1100;
        }

        .main-content {
            flex: 1;
            margin-left: 0;
            transition: margin-left 0.3s ease-in-out;
            padding: 30px;
        }

        .main-content.shifted {
            margin-left: 250px;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .dashboard-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            font-size: 16px;
            color: #555;
        }

        .dashboard-header .datetime {
            font-size: 14px;
            color: #777;
            margin-top: 5px;
        }

        .menu-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            justify-items: center;
        }

        .menu-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 200px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .menu-card i {
            font-size: 40px;
            color: #1e3a8a;
            margin-bottom: 15px;
        }

        .menu-card h3 {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .menu-card p {
            font-size: 12px;
            color: #777;
            text-align: center;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                left: -200px;
            }

            .main-content.shifted {
                margin-left: 200px;
            }

            .menu-card {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <button class="hamburger" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo $admin['foto_profil'] ?? 'https://via.placeholder.com/60'; ?>" alt="Profil Admin">
            <h3><?php echo $admin['nama_lengkap']; ?></h3>
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
            <a href="admin_laporan.php"><i class="fas fa-chart-bar"></i> Kelola Laporan</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content" id="main-content">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Selamat datang, <b><?php echo $admin['nama_lengkap']; ?></b>!</p>
            <div class="datetime" id="datetime"></div>
        </div>

        <div class="menu-container">
            <a href="admin_pengguna.php" class="menu-card">
                <i class="fas fa-users"></i>
                <h3>Kelola Pengguna</h3>
                <p>Atur data pengguna sistem</p>
            </a>
            <a href="admin_jadwal.php" class="menu-card">
                <i class="fas fa-calendar-alt"></i>
                <h3>Kelola Jadwal</h3>
                <p>Atur jadwal booking</p>
            </a>
            <a href="admin_pembayaran.php" class="menu-card">
                <i class="fas fa-money-bill-wave"></i>
                <h3>Pembayaran</h3>
                <p>Atur data pembayaran</p>
            </a>
            <a href="admin_paket.php" class="menu-card">
                <i class="fas fa-box-open"></i>
                <h3>Kelola Paket</h3>
                <p>Atur paket layanan</p>
            </a>
            <a href="admin_hasil_foto.php" class="menu-card">
                <i class="fas fa-camera"></i>
                <h3>Kelola Foto & Promo</h3>
                <p>Atur foto hasil dan promo</p>
            </a>
            <a href="admin_laporan.php" class="menu-card">
                <i class="fas fa-chart-bar"></i>
                <h3>Kelola Laporan</h3>
                <p>Lihat dan kelola laporan aktivitas</p>
            </a>
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

        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit', 
                timeZone: 'Asia/Jakarta' 
            };
            const dateTimeString = now.toLocaleString('id-ID', options);
            document.getElementById('datetime').textContent = 'Selamat, ' + now.toLocaleString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) + ' pukul ' + now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
</body>
</html>