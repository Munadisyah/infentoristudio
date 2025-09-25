<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "koneksi.php";
include "helper.php";

// Set zona waktu ke WIB
date_default_timezone_set('Asia/Jakarta');

$page = isset($_GET['page']) ? $_GET['page'] : false;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : false;
$level = isset($_SESSION['level']) ? $_SESSION['level'] : false;

// Debugging: Tambahkan log untuk memeriksa status login
error_log("User ID: " . ($user_id ?: 'Not set'));
error_log("Level: " . ($level ?: 'Not set'));

// Ambil data user jika sudah login
$data_user = [];
if ($user_id) {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM user WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data_user = mysqli_fetch_array($result) ?: [];
    mysqli_stmt_close($stmt);
}

// Ambil data paket dari tb_paket dan pisahkan berdasarkan jenis (A: Indoor, B: Outdoor) dengan pengurutan
$packages_indoor = [];
$packages_outdoor = [];
$query = "SELECT * FROM tb_paket ORDER BY jenis, nama_paket ASC";
$result = mysqli_query($koneksi, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['jenis'] == 'A') {
            $packages_indoor[] = $row;
        } elseif ($row['jenis'] == 'B') {
            $packages_outdoor[] = $row;
        }
    }
    mysqli_free_result($result);
}

// Ambil semua data promo aktif dengan JOIN ke tb_paket
$query_promo = "SELECT p.*, t.id_paket, t.nama_paket FROM tb_promo p LEFT JOIN tb_paket t ON p.id_paket = t.id_paket WHERE p.tanggal_mulai <= NOW() AND p.tanggal_selesai >= NOW() ORDER BY p.tanggal_upload DESC";
$result_promo = mysqli_query($koneksi, $query_promo);
$promo_data = [];
if ($result_promo) {
    while ($row = mysqli_fetch_assoc($result_promo)) {
        $promo_data[] = $row;
        error_log("Promo Data - ID: " . $row['id_promo'] . ", Gambar: " . $row['gambar_promo']);
    }
    mysqli_free_result($result_promo);
} else {
    error_log("Promo Query Error: " . mysqli_error($koneksi));
}

// Terapkan diskon berdasarkan id_paket dari semua promo aktif
foreach ($packages_indoor as &$package) {
    $package['harga_diskon'] = $package['harga']; // Default ke harga asli
    foreach ($promo_data as $promo) {
        if (isset($promo['id_paket']) && $promo['id_paket'] == $package['id_paket']) {
            $discount_rate = $promo['persentase_diskon'] / 100;
            $package['harga_diskon'] = $package['harga'] * (1 - $discount_rate);
        }
    }
}

foreach ($packages_outdoor as &$package) {
    $package['harga_diskon'] = $package['harga']; // Default ke harga asli
    foreach ($promo_data as $promo) {
        if (isset($promo['id_paket']) && $promo['id_paket'] == $package['id_paket']) {
            $discount_rate = $promo['persentase_diskon'] / 100;
            $package['harga_diskon'] = $package['harga'] * (1 - $discount_rate);
        }
    }
}
unset($package);

// Ambil data galeri dari tb_hasil_foto (ambil 3 terbaru)
$gallery_data = [];
$query_gallery = "SELECT * FROM tb_hasil_foto ORDER BY tanggal_upload DESC LIMIT 3";
$result_gallery = mysqli_query($koneksi, $query_gallery);
if ($result_gallery) {
    while ($row = mysqli_fetch_assoc($result_gallery)) {
        $gallery_data[] = $row;
    }
    mysqli_free_result($result_gallery);
}

// Ambil nomor admin untuk WhatsApp
$admin_contact = '6281234567890'; // Default nomor
$admin_ig = 'infentori_studio'; // Hardcoded Instagram handle
$admin_location = 'https://maps.google.com/?q=-6.1745,106.8294'; // Hardcoded lokasi
$admin_query = "SELECT no_telp FROM user WHERE level = 'admin' LIMIT 1";
$admin_result = mysqli_query($koneksi, $admin_query);
if ($admin_result && $admin = mysqli_fetch_assoc($admin_result)) {
    $admin_contact = $admin['no_telp'];
    mysqli_free_result($admin_result);
}
?>

<!DOCTYPE html>
<html lang="id" class="no-js">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta charset="UTF-8">
    <title>Infentori Studio</title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/main.css">
    <link href="datatables/datatables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/simple-lightbox.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding-top: 80px;
            background-color: #f9fafb;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e5e7eb;
        }
        body.dark-mode .navbar-custom {
            background-color: #2d2d2d;
        }
        body.dark-mode .navbar-menu a {
            color: #e5e7eb;
        }
        body.dark-mode .navbar-menu a:hover {
            color: #93c5fd;
        }
        body.dark-mode .hero-slider .slider-content h1,
        body.dark-mode .hero-slider .slider-content p {
            color: #e5e7eb;
        }
        body.dark-mode .bg-white {
            background-color: #2d2d2d;
        }
        body.dark-mode .bg-gray-100 {
            background-color: #3f3f3f;
        }
        body.dark-mode .text-gray-800 {
            color: #e5e7eb;
        }
        body.dark-mode .text-gray-600 {
            color: #d1d5db;
        }
        body.dark-mode .card {
            background-color: #4b4b4b;
        }
        body.dark-mode .btn-primary {
            background-color: #2563eb;
        }
        body.dark-mode .btn-primary:hover {
            background-color: #1e3a8a;
        }
        .navbar-custom {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 80px !important;
            background-color: #000 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0 1.5rem !important;
            z-index: 1000 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
            transition: background-color 0.3s ease, transform 0.3s ease !important;
        }
        .navbar-custom.scrolled {
            transform: translateY(-10px);
            background-color: rgba(0, 0, 0, 0.9) !important;
        }
        .navbar-logo {
            height: 50px;
            transition: transform 0.3s ease;
        }
        .navbar-logo:hover {
            transform: scale(1.05) rotate(5deg);
        }
        .navbar-text {
            color: #fff !important;
            font-size: 1.25rem;
            font-weight: 700;
            margin-left: 0.75rem;
        }
        .navbar-menu {
            display: flex;
            align-items: center;
        }
        .navbar-menu a {
            color: #fff;
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            transition: color 0.3s ease, transform 0.3s ease;
            position: relative;
        }
        .navbar-menu a:hover {
            color: #1e40af;
            transform: translateY(-2px);
        }
        .navbar-menu a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: #1e40af;
            transition: width 0.3s ease;
        }
        .navbar-menu a:hover::after {
            width: 100%;
        }
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            width: 24px;
            height: 18px;
            justify-content: space-between;
        }
        .hamburger span {
            height: 3px;
            width: 100%;
            background: #fff;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }
        .hero-slider {
            position: relative;
            width: 100%;
            height: 90vh;
            overflow: hidden;
            z-index: 1;
        }
        .hero-slider .swiper-slide {
            background-size: cover;
            background-position: center;
            height: 100%;
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            text-align: left;
            color: #fff;
            position: relative;
            perspective: 1000px;
        }
        .hero-slider .swiper-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.7));
            z-index: 1;
            transition: opacity 0.5s ease;
        }
        .hero-slider .swiper-slide-active::before {
            opacity: 0.8;
        }
        .hero-slider .swiper-slide > * {
            position: relative;
            z-index: 2;
            padding: 2rem;
            margin-right: 3rem;
            margin-bottom: 4rem;
            max-width: 450px;
            color: #fff;
        }
        .hero-slider .slider-content h1,
        .hero-slider .slider-content p {
            color: #fff;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            opacity: 0;
            transform: translateX(50px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .hero-slider .swiper-slide-active .slider-content h1 {
            opacity: 1;
            transform: translateX(0);
            transition-delay: 0.3s;
        }
        .hero-slider .swiper-slide-active .slider-content p {
            opacity: 1;
            transform: translateX(0);
            transition-delay: 0.5s;
        }
        .hero-slider .slider-content h1 {
            font-weight: 700;
            line-height: 1.2;
        }
        .hero-slider .slider-content p {
            font-weight: 400;
        }
        .hero-slider .btn-primary {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .hero-slider .swiper-slide-active .btn-primary {
            opacity: 1;
            transform: translateY(0);
            transition-delay: 0.7s;
        }
        .swiper-pagination-bullet {
            background: #fff;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        .swiper-pagination-bullet-active {
            background: #1e40af;
            opacity: 1;
            transform: scale(1.2);
        }
        .swiper-button-prev, .swiper-button-next {
            color: #fff;
            transition: color 0.3s ease, opacity 0.3s ease, transform 0.3s ease;
            opacity: 0.7;
        }
        .swiper-button-prev:hover, .swiper-button-next:hover {
            color: #1e40af;
            opacity: 1;
            transform: scale(1.1);
        }
        .btn-primary {
            background-color: #1e40af;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: 600;
            transition: all 0.3s ease, transform 0.3s ease;
            display: inline-block;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(0);
        }
        .btn-primary:active {
            transform: scale(0.95);
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }
        .btn-primary:hover::after {
            left: 100%;
        }
        .btn-primary:hover {
            background-color: #1e3a8a;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        .card {
            transition: transform 0.5s ease, box-shadow 0.5s ease, opacity 0.5s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            opacity: 0;
            transform: translateY(30px);
        }
        .card.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .card:hover {
            transform: translateY(-10px) rotateX(5deg) rotateY(5deg);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        .card-content {
            flex-grow: 1;
        }
        .card .btn-primary {
            margin-top: auto;
            align-self: center;
        }
        .gallery-img {
            transition: transform 0.5s ease, filter 0.5s ease;
            position: relative;
        }
        .gallery-img:hover {
            transform: scale(1.05);
            filter: brightness(1.1);
        }
        .social-icons a {
            color: #a855f7;
            font-size: 1.5rem;
            transition: transform 0.5s ease, box-shadow 0.5s ease, background 0.5s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(168, 85, 247, 0.2);
            transform-style: preserve-3d;
            box-shadow: 0 0 10px rgba(168, 85, 247, 0.3);
        }
        .social-icons a:hover {
            transform: rotateY(360deg) translateZ(20px) scale(1.1);
            box-shadow: 0 0 15px rgba(168, 85, 247, 0.5);
            background: rgba(168, 85, 247, 0.4);
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .dark-mode-toggle {
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 1001;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: background 0.3s ease, transform 0.3s ease;
        }
        .dark-mode-toggle:hover {
            transform: scale(1.1);
        }
        body.dark-mode .dark-mode-toggle {
            background: #4b4b4b;
        }
        .dark-mode-toggle i {
            font-size: 1.25rem;
            color: #1e40af;
            transition: transform 0.5s ease;
        }
        body.dark-mode .dark-mode-toggle i {
            color: #93c5fd;
            transform: rotate(180deg);
        }
        .gallery-card {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }
        .gallery-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }
        .promo-card {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        .promo-card img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }
        .original-price {
            text-decoration: line-through;
            color: #ef4444;
            font-weight: bold;
            margin: 0;
        }
        .discounted-price {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0;
        }
        .promo-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        @media (max-width: 1024px) {
            .hero-slider .swiper-slide > * {
                margin-right: 2rem;
                margin-bottom: 3rem;
                max-width: 350px;
            }
            .hero-slider .slider-content h1 {
                font-size: 2.5rem;
            }
            .hero-slider .slider-content p {
                font-size: 1rem;
            }
            .card {
                padding: 1rem;
            }
            .gallery-card img {
                height: 250px;
            }
            .promo-card img {
                height: 350px;
            }
            .dark-mode-toggle {
                top: 80px;
            }
        }
        @media (max-width: 768px) {
            .navbar-custom {
                height: 60px;
            }
            body {
                padding-top: 60px;
            }
            .navbar-logo {
                height: 40px;
            }
            .navbar-text {
                font-size: 1rem;
            }
            .navbar-menu {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 60px;
                left: 0;
                right: 0;
                background-color: #000;
                padding: 1rem;
                z-index: 999;
                align-items: flex-start;
            }
            .navbar-menu.active {
                display: flex;
            }
            .navbar-menu a {
                margin: 0.5rem 0;
                color: #fff;
                width: 100%;
                padding: 0.5rem 0;
                text-align: left;
            }
            .navbar-menu a:hover {
                color: #1e40af;
            }
            .hamburger {
                display: flex;
            }
            .hero-slider {
                height: 60vh;
            }
            .hero-slider .swiper-slide > * {
                margin-right: 1.5rem;
                margin-bottom: 2rem;
                max-width: 250px;
            }
            .hero-slider .slider-content h1 {
                font-size: 1.5rem;
            }
            .hero-slider .slider-content p {
                font-size: 0.875rem;
            }
            .grid {
                grid-template-columns: 1fr 1fr;
            }
            .card {
                padding: 0.75rem;
            }
            .gallery-card img {
                height: 200px;
            }
            .promo-card img {
                height: 300px;
            }
            .dark-mode-toggle {
                top: 70px;
            }
        }
        @media (max-width: 576px) {
            .navbar-custom {
                height: 50px;
            }
            body {
                padding-top: 50px;
            }
            .navbar-logo {
                height: 30px;
            }
            .navbar-text {
                font-size: 0.875rem;
            }
            .hero-slider {
                height: 50vh;
            }
            .hero-slider .swiper-slide > * {
                margin-right: 1rem;
                margin-bottom: 1.5rem;
                max-width: 200px;
            }
            .hero-slider .slider-content h1 {
                font-size: 1.25rem;
            }
            .hero-slider .slider-content p {
                font-size: 0.75rem;
            }
            .grid {
                grid-template-columns: 1fr;
            }
            .card {
                padding: 0.5rem;
            }
            .gallery-card img {
                height: 150px;
            }
            .promo-card img {
                height: 250px;
            }
            .swiper-button-prev, .swiper-button-next {
                display: none;
            }
            .dark-mode-toggle {
                top: 60px;
            }
        }
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            padding: 1rem 0;
        }
    </style>
</head>

<body>
<!-- Dark Mode Toggle -->
<div class="dark-mode-toggle" onclick="toggleDarkMode()">
    <i class="fas fa-moon"></i>
</div>

<!-- Start Navbar -->
<nav class="navbar-custom" data-aos="fade-down" data-aos-duration="800">
    <div class="flex items-center">
        <img src="img/logoinfen.png" alt="Logo" class="navbar-logo" data-aos="zoom-in" data-aos-delay="200">
        <span class="navbar-text" data-aos="fade-right" data-aos-delay="400">Infentori Studio</span>
    </div>

    <div class="flex items-center">
        <div class="navbar-menu" id="navbar-menu">
            <a href="index.php" data-aos="fade-up" data-aos-delay="600">Home</a>
            <?php if ($user_id) { ?>
                <a href="?page=module/profile" data-aos="fade-up" data-aos-delay="700">Profile</a>
                <?php if ($level == "admin") { ?>
                    <a href="admin_dashboard.php" data-aos="fade-up" data-aos-delay="800">Dashboard Admin</a>
                    <a href="admin_hasil_foto.php" data-aos="fade-up" data-aos-delay="900">Kelola Foto & Promo</a>
                    <a href="logout.php" data-aos="fade-up" data-aos-delay="1000">Logout</a>
                    <a href="?page=module/user" data-aos="fade-up" data-aos-delay="1100">Welcome, <?php echo htmlspecialchars($data_user['nama_lengkap'] ?? ''); ?></a>
                <?php } else { ?>
                    <a href="?page=module/booking" data-aos="fade-up" data-aos-delay="800">Booking</a>
                    <a href="?page=module/list_pes" data-aos="fade-up" data-aos-delay="900">List Pesanan</a>
                    <a href="logout.php" data-aos="fade-up" data-aos-delay="1000">Logout</a>
                    <a href="?page=module/user" data-aos="fade-up" data-aos-delay="1100">Welcome, <?php echo htmlspecialchars($data_user['nama_lengkap'] ?? ''); ?></a>
                <?php } ?>
            <?php } else { ?>
                <a href="?page=module/profile" data-aos="fade-up" data-aos-delay="700">Profile</a>
                <a href="register.php" data-aos="fade-up" data-aos-delay="800">Register</a>
                <a href="login.php" data-aos="fade-up" data-aos-delay="900">Login</a>
            <?php } ?>
        </div>
        <div class="hamburger" id="hamburger" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</nav>
<!-- End Navbar -->

<!-- Start Content -->
<?php
$filename = "$page.php";
if (file_exists($filename) && $page !== 'login' && $page !== 'register') {
    include_once($filename);
} else {
?>
    <!-- Hero Slider (Header) -->
    <section class="hero-slider">
        <div id="particles-js" style="position: absolute; width: 100%; height: 100%; z-index: 0;"></div>
        <div class="swiper-wrapper">
            <div class="swiper-slide" style="background-image: url('img/slider1.jpg')" data-swiper-parallax="-200">
                <div class="slider-content">
                    <h1 class="text-3xl md:text-5xl font-bold mb-4">Foto Wisuda Profesional</h1>
                    <p class="text-lg md:text-xl mb-6">Siap abadikan momen terbaik Anda.</p>
                    <a href="?page=module/booking" class="btn-primary">Booking Sekarang</a>
                </div>
            </div>
            <div class="swiper-slide" style="background-image: url('img/slider6.jpg')" data-swiper-parallax="-200">
                <div class="slider-content">
                    <h1 class="text-3xl md:text-5xl font-bold mb-4">Paket Keluarga Bahagia</h1>
                    <p class="text-lg md:text-xl mb-6">Satu frame untuk semua kebahagiaan.</p>
                    <a href="?page=module/booking" class="btn-primary">Booking Sekarang</a>
                </div>
            </div>
            <div class="swiper-slide" style="background-image: url('img/slider3.jpg')" data-swiper-parallax="-200">
                <div class="slider-content">
                    <h1 class="text-3xl md:text-4xl font-bold mb-4">Promo Spesial Bulan Ini</h1>
                    <p class="text-lg md:text-xl mb-6">Hemat banyak dengan booking sekarang!</p>
                    <a href="?page=module/booking" class="btn-primary">Booking Sekarang</a>
                </div>
            </div>
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
    </section>

    <!-- Keunggulan Studio -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-12" data-aos="fade-up">Kenapa Memilih Kami?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="card p-6 bg-gray-50 rounded-lg text-center">
                    <i class="fas fa-camera text-4xl text-blue-700 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Fotografer Profesional</h3>
                    <p class="text-gray-600">Tim berpengalaman untuk hasil terbaik.</p>
                </div>
                <div class="card p-6 bg-gray-50 rounded-lg text-center">
                    <i class="fas fa-wallet text-4xl text-blue-700 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Harga Terjangkau</h3>
                    <p class="text-gray-600">Kualitas premium, ramah di kantong.</p>
                </div>
                <div class="card p-6 bg-gray-50 rounded-lg text-center">
                    <i class="fas fa-clock text-4xl text-blue-700 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Pelayanan Cepat</h3>
                    <p class="text-gray-600">Hasil memuaskan dalam waktu singkat.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Daftar Paket -->
    <section class="py-16 bg-gray-100">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-12" data-aos="fade-up">Pilih Paket Anda</h2>

            <!-- Paket Indoor (A) -->
            <h3 class="text-2xl font-semibold text-center text-gray-800 mb-8" data-aos="fade-up">Paket Studio (Indoor)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <?php if (empty($packages_indoor)) { ?>
                    <p class="text-center text-gray-600 col-span-full">Belum ada paket indoor tersedia.</p>
                <?php } else { ?>
                    <?php foreach ($packages_indoor as $index => $package) { ?>
                        <div class="card p-6 bg-white rounded-lg shadow-lg">
                            <div class="card-content">
                                <h3 class="text-xl font-semibold text-blue-700 mb-2"><?php echo htmlspecialchars($package['nama_paket']); ?></h3>
                                <?php if (isset($package['harga_diskon']) && $package['harga_diskon'] < $package['harga']) { ?>
                                    <p class="original-price">Rp <?php echo number_format($package['harga'], 0, ',', '.'); ?></p>
                                    <p class="discounted-price">Rp <?php echo number_format($package['harga_diskon'], 0, ',', '.'); ?></p>
                                <?php } else { ?>
                                    <p class="text-2xl font-bold text-gray-800 mb-2">Rp <?php echo number_format($package['harga'], 0, ',', '.'); ?></p>
                                <?php } ?>
                                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($package['rincian'] ?? 'Detail belum tersedia'); ?></p>
                            </div>
                            <a href="?page=module/form_paket_a&id_paket=<?php echo $package['id_paket']; ?>" class="btn-primary">Booking Sekarang</a>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>

            <!-- Paket Outdoor (B) -->
            <h3 class="text-2xl font-semibold text-center text-gray-800 mb-8" data-aos="fade-up">Paket Outdoor</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($packages_outdoor)) { ?>
                    <p class="text-center text-gray-600 col-span-full">Belum ada paket outdoor tersedia.</p>
                <?php } else { ?>
                    <?php foreach ($packages_outdoor as $index => $package) { ?>
                        <div class="card p-6 bg-white rounded-lg shadow-lg">
                            <div class="card-content">
                                <h3 class="text-xl font-semibold text-blue-700 mb-2"><?php echo htmlspecialchars($package['nama_paket']); ?></h3>
                                <?php if (isset($package['harga_diskon']) && $package['harga_diskon'] < $package['harga']) { ?>
                                    <p class="original-price">Rp <?php echo number_format($package['harga'], 0, ',', '.'); ?></p>
                                    <p class="discounted-price">Rp <?php echo number_format($package['harga_diskon'], 0, ',', '.'); ?></p>
                                <?php } else { ?>
                                    <p class="text-2xl font-bold text-gray-800 mb-2">Rp <?php echo number_format($package['harga'], 0, ',', '.'); ?></p>
                                <?php } ?>
                                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($package['rincian'] ?? 'Detail belum tersedia'); ?></p>
                            </div>
                            <a href="?page=module/form_paket_b&id_paket=<?php echo $package['id_paket']; ?>" class="btn-primary">Booking Sekarang</a>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Promo Spesial -->
    <?php if (!empty($promo_data)) { ?>
    <section class="py-16 bg-blue-900 text-white text-center">
        <div class="container mx-auto px-4">
            <a href="index.php?page=promo" class="btn-primary mb-8 inline-block text-xl font-bold px-8 py-3 rounded-full" data-aos="fade-up" data-aos-delay="100">PROMO</a>
            <div class="promo-container">
                <?php foreach ($promo_data as $index => $promo) { ?>
                    <div class="promo-card max-w-xs mx-auto bg-white rounded-lg shadow-lg overflow-hidden" data-aos="fade-up" data-aos-delay="<?php echo 200 + ($index * 100); ?>">
                        <?php
                        $original_filename = $promo['gambar_promo'];
                        $image_path = "img/promo/" . $original_filename;
                        error_log("Promo Image - Original Filename: " . $original_filename);
                        error_log("Checking image path: " . $image_path);

                        if (file_exists($image_path)) {
                            echo '<img src="' . $image_path . '" alt="' . htmlspecialchars($promo['judul_promo']) . '" class="w-full h-full object-cover" loading="lazy">';
                        } else {
                            error_log("Promo Image not found at: " . $image_path);
                            echo '<div style="color: red; padding: 1rem;">Gambar tidak ditemukan: ' . htmlspecialchars($original_filename) . '</div>';
                        }
                        ?>
                        <div class="p-4 text-center">
                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($promo['judul_promo']); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($promo['deskripsi']); ?></p>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <!-- Galeri Foto -->
    <section class="py-16 bg-gray-100 text-center">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-12" data-aos="fade-up">Galeri Foto</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php if (empty($gallery_data)) { ?>
                    <p class="text-center text-gray-600 col-span-full">Belum ada foto tersedia.</p>
                <?php } else { ?>
                    <?php foreach ($gallery_data as $index => $foto) { ?>
                        <div class="card p-6 bg-white rounded-lg shadow-lg">
                            <img src="img/hasil_foto/<?php echo htmlspecialchars($foto['file_foto']); ?>" alt="<?php echo htmlspecialchars($foto['nama_foto']); ?>" class="w-full h-full object-cover rounded-lg mb-4" loading="lazy">
                            <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($foto['nama_foto']); ?></h3>
                            <p class="text-gray-600">Tanggal Upload: <?php echo date('d M Y', strtotime($foto['tanggal_upload'])); ?></p>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Testimoni -->
    <section class="py-16 bg-gray-100 text-center">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-12" data-aos="fade-up">Apa Kata Mereka?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                $testimonials = [
                    ['img' => 'img/1faisal.jpg', 'name' => 'Faisal Sahdi', 'comment' => 'Pelayanan cepat dan hasil foto sangat memuaskan!'],
                    ['img' => 'img/3dina.jpeg', 'name' => 'Dina Aulia', 'comment' => 'Fotografer profesional, harga worth it!'],
                    ['img' => 'img/2piya.jpg', 'name' => 'Siti Aisyah', 'comment' => 'Momen keluarga kami terekam sempurna.'],
                ];
                foreach ($testimonials as $index => $testimonial) { ?>
                    <div class="card p-6 bg-white rounded-lg shadow-lg">
                        <img src="<?php echo $testimonial['img']; ?>" alt="<?php echo $testimonial['name']; ?>" class="w-20 h-20 rounded-full mx-auto mb-4 object-cover" loading="lazy">
                        <h3 class="text-lg font-semibold text-gray-800"><?php echo $testimonial['name']; ?></h3>
                        <p class="text-gray-600 italic">"<?php echo $testimonial['comment']; ?>"</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

<!-- Call to Action -->
<section class="py-16 bg-blue-900 text-white text-center">
    <div class="container mx-auto px-4" data-aos="fade-up">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Siap Mengabadikan Momen Anda?</h2>
        <a href="?page=module/booking" class="btn-primary mb-4">Booking Sekarang</a>
        <div class="social-icons">
            <a href="https://maps.app.goo.gl/r67s45ABCkStjA13A" target="_blank"><i class="fas fa-map-marker-alt"></i></a>
            <a href="https://www.instagram.com/<?php echo htmlspecialchars($admin_ig); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://wa.me/<?php echo htmlspecialchars($admin_contact); ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
            <a href="faq.php" target="_blank"><i class="fas fa-question-circle"></i></a>
        </div>
    </div>
</section>
<?php
}
?>
<!-- End Content -->

<!-- JS Scripts -->
<script src="js/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="js/vendor/bootstrap.min.js"></script>
<script src="js/jquery.ajaxchimp.min.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/jquery.nice-select.min.js"></script>
<script src="js/jquery.sticky.js"></script>
<script src="js/parallax.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="js/simple-lightbox.min.js"></script>
<script src="js/main.js"></script>
<script src="datatables/datatables.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
function toggleMenu() {
    const menu = document.getElementById('navbar-menu');
    const hamburger = document.getElementById('hamburger');
    if (menu && hamburger) {
        menu.classList.toggle('active');
        hamburger.classList.toggle('active');
    }
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const icon = document.querySelector('.dark-mode-toggle i');
    if (document.body.classList.contains('dark-mode')) {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
        localStorage.setItem('theme', 'dark');
    } else {
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
        localStorage.setItem('theme', 'light');
    }
}

$(document).ready(function() {
    console.log("Navbar element:", document.querySelector('.navbar-custom'));

    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        document.querySelector('.dark-mode-toggle i').classList.remove('fa-moon');
        document.querySelector('.dark-mode-toggle i').classList.add('fa-sun');
    }

    // Navbar scroll effect
    $(window).on('scroll', function() {
        if ($(window).scrollTop() > 50) {
            $('.navbar-custom').addClass('scrolled');
        } else {
            $('.navbar-custom').removeClass('scrolled');
        }
    });

    // Intersection Observer for card animations
    const cards = document.querySelectorAll('.card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, index * 200);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    cards.forEach(card => {
        observer.observe(card);
    });

    if (document.querySelector('.hero-slider')) {
        const heroSlider = new Swiper('.hero-slider', {
            loop: true,
            autoplay: { delay: 5000, disableOnInteraction: false },
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
            effect: 'fade',
            fadeEffect: { crossFade: true },
            speed: 1200,
            parallax: true,
            on: {
                init: function() {
                    console.log('Hero Slider initialized');
                },
                slideChange: function() {
                    console.log('Hero Slider changed to slide: ', this.activeIndex);
                }
            }
        });

        if (document.getElementById('particles-js')) {
            particlesJS('particles-js', {
                "particles": {
                    "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
                    "color": { "value": "#ffffff" },
                    "shape": { "type": "circle", "stroke": { "width": 0, "color": "#000000" } },
                    "opacity": { "value": 0.5, "random": false, "anim": { "enable": false, "speed": 1, "opacity_min": 0.1, "sync": false } },
                    "size": { "value": 3, "random": true, "anim": { "enable": false, "speed": 40, "size_min": 0.1, "sync": false } },
                    "line_linked": { "enable": true, "distance": 150, "color": "#ffffff", "opacity": 0.4, "width": 1 },
                    "move": { "enable": true, "speed": 6, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false }
                },
                "interactivity": {
                    "detect_on": "canvas",
                    "events": { "onhover": { "enable": true, "mode": "repulse" }, "onclick": { "enable": true, "mode": "push" }, "resize": true },
                    "modes": { "grab": { "distance": 400, "line_linked": { "opacity": 1 } }, "bubble": { "distance": 400, "size": 40, "duration": 2, "opacity": 8, "speed": 3 }, "repulse": { "distance": 200, "duration": 0.4 }, "push": { "particles_nb": 4 }, "remove": { "particles_nb": 2 } }
                },
                "retina_detect": true
            });
            console.log("Particles.js initialized");
        }
    }

    if ($('#datatables').length) {
        $('#datatables').DataTable();
    }

    AOS.init({
        duration: 800,
        once: true,
        easing: 'ease-out-cubic',
        disable: window.innerWidth < 576
    });
    console.log("AOS initialized");
});
</script>
</body>
</html>
<?php
if ($koneksi instanceof mysqli && !$koneksi->connect_error) {
    mysqli_close($koneksi);
}
?>