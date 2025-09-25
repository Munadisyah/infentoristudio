<?php
session_start();
include("koneksi.php");

if (!isset($_SESSION['level']) || $_SESSION['level'] != "pemilik") {
    echo '<script>alert("Anda tidak punya akses! Silakan login sebagai pemilik."); window.location="login.php";</script>';
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data pemilik
$stmt = mysqli_prepare($koneksi, "SELECT * FROM user WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pemilik = mysqli_fetch_assoc($result) ?: [];
mysqli_stmt_close($stmt);

// Ambil data ringkas untuk dashboard
$data = [];
$stmt = mysqli_prepare($koneksi, "SELECT 
    (SELECT COUNT(*) FROM user WHERE level='customer') as total_users,
    (SELECT COUNT(*) FROM jadwal_pemotretan) as total_jadwal,
    (SELECT COUNT(*) FROM tb_bayar WHERE status='LUNAS') as total_lunas,
    (SELECT COUNT(*) FROM tb_paket) as total_paket,
    (SELECT COUNT(*) FROM tb_hasil_foto) as total_foto,
    (SELECT SUM(total) FROM tb_detail_pesan_reg d JOIN tb_bayar b ON d.id_pesan = b.id_pesan WHERE b.status='LUNAS') as total_pendapatan");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($result) {
    $data = mysqli_fetch_assoc($result);
    $data['total_pendapatan'] = $data['total_pendapatan'] ?? 0;
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilik - Infentori Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2f3 100%);
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
            animation: fadeIn 1s ease-in;
        }

        .sidebar-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 2px solid #1e3a8a;
            transition: transform 0.3s ease;
        }

        .sidebar-header img:hover {
            transform: scale(1.1);
        }

        .sidebar-header h3 {
            font-size: 16px;
            font-weight: 600;
        }

        .sidebar-header p {
            font-size: 12px;
            color: #bbb;
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
            transition: background 0.3s ease, color 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: #2a2a2a;
            color: #fff;
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
            transition: transform 0.3s ease;
        }

        .hamburger:hover {
            transform: rotate(90deg);
        }

        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease-in-out;
            padding: 30px;
        }

        .main-content.shifted {
            margin-left: 250px;
        }

        .welcome-text {
            text-align: center;
            margin-bottom: 30px;
            color: #1e3a8a;
            font-weight: 700;
            font-size: 2.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            animation: fadeInSlideUp 1.5s ease-out;
        }

        .welcome-text span {
            display: inline-block;
            animation: bounce 1.5s infinite;
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .card h4 {
            font-size: 1.2rem;
            color: #1e3a8a;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInSlideUp {
            0% { opacity: 0; transform: translateY(50px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                left: -200px;
            }
            .main-content.shifted {
                margin-left: 200px;
            }
            .welcome-text {
                font-size: 2rem;
            }
            .card-container {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .sidebar, .hamburger {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 10px !important;
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
        <div class="welcome-text">
            Selamat Datang <span>Pemilik</span>!
        </div>

        <div class="card-container">
            <div class="card">
                <h4>Total Pelanggan</h4>
                <p><?php echo $data['total_users'] ?? 0; ?></p>
            </div>
            <div class="card">
                <h4>Jadwal Aktif</h4>
                <p><?php echo $data['total_jadwal'] ?? 0; ?></p>
            </div>
            <div class="card">
                <h4>Pembayaran Lunas</h4>
                <p><?php echo $data['total_lunas'] ?? 0; ?></p>
            </div>
            <div class="card">
                <h4>Paket Tersedia</h4>
                <p><?php echo $data['total_paket'] ?? 0; ?></p>
            </div>
            <div class="card">
                <h4>Total Foto</h4>
                <p><?php echo $data['total_foto'] ?? 0; ?></p>
            </div>
            <div class="card">
                <h4>Total Pendapatan</h4>
                <p>Rp <?php echo number_format($data['total_pendapatan'], 0, ',', '.'); ?></p>
            </div>
        </div>

        <div class="chart-container">
            <h4>Ringkasan Pendapatan Bulanan</h4>
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        // Data untuk chart (contoh, bisa diganti dengan data dari database)
        const chartData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: [500000, 750000, 600000, 800000, 900000, 700000, 850000, 950000, 1100000, 1200000, 1300000, 1400000],
                backgroundColor: 'rgba(30, 58, 138, 0.7)',
                borderColor: '#1e3a8a',
                borderWidth: 2,
                fill: true
            }]
        };

        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Pendapatan (Rp)' }
                    }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuad'
                }
            }
        });
    </script>
</body>
</html>