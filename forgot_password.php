<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("koneksi.php");

// Proses Pengiriman Email Reset Password (Simulasi)
if (isset($_POST['reset'])) {
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    
    $cek = mysqli_query($koneksi, "SELECT * FROM user WHERE email='$email'");
    if (mysqli_num_rows($cek) > 0) {
        // Simulasi: Dalam implementasi nyata, Anda perlu mengirim email dengan link reset
        // Contoh: Gunakan PHPMailer untuk mengirim email dengan link reset
        echo '<script>alert("Link reset password telah dikirim ke email Anda! (Simulasi)"); window.location="login.php";</script>';
    } else {
        echo '<script>alert("Email tidak ditemukan! Silakan coba lagi."); window.location="forgot_password.php";</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Infentori Studio</title>
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
        }

        .auth-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            min-height: 400px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
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
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .auth-left h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .auth-left p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.5;
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
            animation: slideIn 0.5s ease-in-out forwards;
            opacity: 0;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
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
            animation: slideIn 0.5s ease-in-out forwards;
            animation-delay: 0.2s;
            opacity: 0;
        }

        .submit-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .submit-btn:hover::after {
            left: 100%;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #2563eb, #163068);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
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
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <!-- Sisi Kiri: Pesan Sambutan -->
        <div class="auth-left">
            <h2>Lupa Password?</h2>
            <p>Masukkan email Anda untuk menerima link reset password.</p>
            <a href="login.php" class="switch-btn">Kembali ke Login</a>
        </div>

        <!-- Sisi Kanan: Form Reset Password -->
        <div class="auth-right">
            <div class="form-container">
                <h2>Reset Password</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="reset-email">Email</label>
                        <input type="email" name="email" id="reset-email" pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}" required>
                    </div>
                    <button type="submit" name="reset" class="submit-btn">Kirim Link Reset</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>