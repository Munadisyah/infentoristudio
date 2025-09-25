<?php
// Aktifkan mode debug untuk menampilkan error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("koneksi.php");

// Proses Login
if (isset($_POST['login'])) {
    $user = mysqli_real_escape_string($koneksi, $_POST['user']);
    $pass = md5($_POST['pass']);

    $query = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$user' AND password='$pass' AND status='on'");
    if (!$query) {
        die("Query gagal: " . mysqli_error($koneksi));
    }

    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
        $_SESSION['level'] = $row['level'] ?? 'customer';
        $_SESSION['username'] = $row['username'];

        switch ($_SESSION['level']) {
            case "admin":
                header("Location: admin_dashboard.php");
                exit();
            case "customer":
                header("Location: index.php");
                exit();
            case "fotografer":
                header("Location: fotografer.php");
                exit();
            case "pemilik":
                header("Location: pemilik_laporan.php");
                exit();
            default:
                echo '<script>alert("Level pengguna tidak dikenal!"); window.location="login.php";</script>';
                exit();
        }
    } else {
        echo '<script>alert("Username atau Password salah atau akun tidak aktif!"); window.location="login.php";</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Infentori Studio</title>
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
            min-height: 500px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeIn 1.2s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        .form-group:nth-child(2) { animation-delay: 0.4s; }

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

        .form-group input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            background: #f7fafc;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
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

        .form-group .forgot-password {
            display: block;
            text-align: right;
            font-size: 0.85rem;
            color: #3b82f6;
            text-decoration: none;
            margin-top: 8px;
            transition: color 0.3s ease;
        }

        .form-group .forgot-password:hover {
            color: #1e3a8a;
            text-decoration: underline;
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
            animation-delay: 0.6s;
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
                padding: 40px;
            }

            .form-container h2 {
                font-size: 1.8rem;
            }

            .form-container {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .auth-container {
                max-width: 100%;
                margin: 15px;
            }

            .auth-left {
                padding: 30px;
            }

            .auth-right {
                padding: 30px;
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
            <h2>Selamat Datang</h2>
            <h2>di</h2>
            <h2>Infentori Studio</h2>
            <p>Masuk ke akun Anda untuk melanjutkan pengalaman fotografi terbaik bersama kami.</p>
            <a href="register.php" class="switch-btn">Daftar</a>
        </div>

        <!-- Sisi Kanan: Form Login -->
        <div class="auth-right">
            <div class="form-container">
                <h2>Login</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="login-user">Username</label>
                        <input type="text" name="user" id="login-user" required>
                    </div>
                    <div class="form-group">
                        <label for="login-pass">Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="pass" id="login-pass" required>
                            <span class="toggle-password">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                        <a href="forgot_password.php" class="forgot-password">Lupa Password?</a>
                    </div>
                    <button type="submit" name="login" class="submit-btn">Masuk Sekarang</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.querySelector('#login-pass');

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