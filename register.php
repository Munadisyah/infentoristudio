<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("koneksi.php");

// Proses Registrasi
if (isset($_POST['regis'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $notelp = mysqli_real_escape_string($koneksi, $_POST['notelp']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $user = mysqli_real_escape_string($koneksi, $_POST['user']);
    $pass = mysqli_real_escape_string($koneksi, $_POST['pass']);
    $confpass = mysqli_real_escape_string($koneksi, $_POST['confpass']);

    // Validasi panjang nomor telepon
    $notelp = preg_replace('/\D/', '', $notelp); // Hapus karakter non-angka
    if (strlen($notelp) < 10 || strlen($notelp) > 12) {
        echo '<script>alert("Nomor telepon harus antara 10-12 digit!"); window.location="register.php";</script>';
        exit;
    }

    // Validasi password dan konfirmasi password
    if ($pass !== $confpass) {
        echo '<script>alert("Password dan konfirmasi password tidak cocok!"); window.location="register.php";</script>';
        exit;
    }

    $cek = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$user'");
    if (mysqli_num_rows($cek) > 0) {
        echo '<script>alert("Username sudah ada, silakan buat ulang!"); window.location="register.php";</script>';
    } else {
        $query = "INSERT INTO user (user_id, nama_lengkap, alamat, email, no_telp, username, password, level, status) 
                  VALUES ('', '$nama', '$alamat', '$email', '$notelp', '$user', md5('$pass'), 'customer', 'on')";
        if (mysqli_query($koneksi, $query)) {
            echo '<script>alert("Registrasi Berhasil! Silakan Login."); window.location="login.php";</script>';
        } else {
            echo '<script>alert("Error: Terjadi kesalahan, coba lagi."); window.location="register.php";</script>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Infentori Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #e0e7ff, #f3e8ff);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
            animation: gradientMove 15s ease infinite;
            background-size: 200% 200%;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .auth-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            min-height: 650px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeIn 1.2s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-left {
            flex: 1;
            background: linear-gradient(135deg, #3b82f6, #1e3a8a);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            animation: float 6s infinite ease-in-out;
        }

        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            animation: float 8s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-30px) scale(1.1); }
        }

        .auth-left h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            line-height: 1.2;
            animation: floatText 5s infinite ease-in-out;
        }

        @keyframes floatText {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .auth-left p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.5;
            animation: fadeInText 1.5s ease-in-out;
        }

        @keyframes fadeInText {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 0.9; transform: translateY(0); }
        }

        .auth-left .switch-btn {
            padding: 12px 40px;
            background: transparent;
            border: 2px solid #fff;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .auth-left .switch-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .auth-left .switch-btn:hover::after {
            left: 100%;
        }

        .auth-left .switch-btn:hover {
            background: #fff;
            color: #1e3a8a;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .auth-right {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px;
            background: #fff;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
        }

        .form-container h2 {
            font-size: 2rem;
            font-weight: 600;
            color: #1e3a8a;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            animation: zoomIn 1s ease-in-out;
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        .form-container h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: #facc15;
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 20px;
            animation: zoomIn 0.8s ease-in-out forwards;
            opacity: 0;
        }

        .form-group:nth-child(1) { animation-delay: 0.2s; }
        .form-group:nth-child(2) { animation-delay: 0.3s; }
        .form-group:nth-child(3) { animation-delay: 0.4s; }
        .form-group:nth-child(4) { animation-delay: 0.5s; }
        .form-group:nth-child(5) { animation-delay: 0.6s; }
        .form-group:nth-child(6) { animation-delay: 0.7s; }
        .form-group:nth-child(7) { animation-delay: 0.8s; }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .form-group .input-wrapper {
            position: relative;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            background: #f7fafc;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
            padding: 10px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #3b82f6;
            background: #fff;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .form-group .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .form-group .toggle-password:hover {
            color: #1e3a8a;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6, #1e3a8a);
            border: none;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: zoomIn 0.8s ease-in-out forwards;
            animation-delay: 0.9s;
            opacity: 0;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            transition: left 0.5s ease;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #2563eb, #163068);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.5);
        }

        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                min-height: auto;
                height: auto;
                max-width: 450px;
            }

            .auth-left {
                padding: 40px;
                min-height: 220px;
            }

            .auth-left h2 {
                font-size: 1.8rem;
            }

            .auth-left p {
                font-size: 1rem;
            }

            .auth-right {
                padding: 30px;
            }

            .form-container h2 {
                font-size: 1.8rem;
            }

            .form-group input,
            .form-group textarea {
                padding: 10px;
            }

            .form-group textarea {
                min-height: 70px;
            }
        }

        @media (max-width: 480px) {
            .auth-container {
                max-width: 100%;
                margin: 15px;
            }

            .auth-left {
                padding: 20px;
            }

            .auth-right {
                padding: 20px;
            }

            .auth-left h2 {
                font-size: 1.5rem;
            }

            .auth-left p {
                font-size: 0.9rem;
            }

            .form-container h2 {
                font-size: 1.5rem;
            }

            .form-group input,
            .form-group textarea {
                padding: 8px;
            }

            .form-group textarea {
                min-height: 60px;
            }

            .form-group .toggle-password {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <!-- Sisi Kiri: Pesan Sambutan -->
        <div class="auth-left">
            <h2>Buat Akun Baru</h2>
            <h2>di</h2>
            <h2>Infentori Studio</h2>
            <p>Bergabunglah dengan Infentori Studio untuk mengabadikan momen berharga Anda dengan sempurna.</p>
            <a href="login.php" class="switch-btn">Masuk</a>
        </div>

        <!-- Sisi Kanan: Form Registrasi -->
        <div class="auth-right">
            <div class="form-container">
                <h2>Registrasi</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="regis-nama">Nama Lengkap</label>
                        <input type="text" name="nama" id="regis-nama" required>
                    </div>
                    <div class="form-group">
                        <label for="regis-alamat">Alamat</label>
                        <textarea name="alamat" id="regis-alamat" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="regis-notelp">No Telepon</label>
                        <input type="text" name="notelp" id="regis-notelp" pattern="[0-9]{10,12}" maxlength="12" required>
                    </div>
                    <div class="form-group">
                        <label for="regis-email">Email</label>
                        <input type="email" name="email" id="regis-email" pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}" required>
                    </div>
                    <div class="form-group">
                        <label for="regis-user">Username</label>
                        <input type="text" name="user" id="regis-user" required>
                    </div>
                    <div class="form-group">
                        <label for="regis-pass">Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="pass" id="regis-pass" required>
                            <span class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="regis-confpass">Konfirmasi Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="confpass" id="regis-confpass" required>
                        </div>
                    </div>
                    <button type="submit" name="regis" class="submit-btn">Daftar Sekarang</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.querySelector('#regis-pass');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>