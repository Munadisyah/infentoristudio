<?php
session_start();
include("koneksi.php");

if (!isset($_SESSION['level']) || $_SESSION['level'] != "fotografer") {
    echo '<script>alert("Anda tidak punya akses!"); window.location="index.php?page=login";</script>';
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data fotografer sebelum proses update
$query = "SELECT * FROM user WHERE user_id='$user_id'";
$result = mysqli_query($koneksi, $query);
if (!$result) {
    echo '<script>alert("Gagal mengambil data: ' . mysqli_error($koneksi) . '"); window.location="fotografer.php";</script>';
    exit();
}
$fotografer = mysqli_fetch_assoc($result);

// Proses update profil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $email = $_POST['email'];
    $no_telp = $_POST['no_telp'];

    // Proses upload foto profil
    $foto_profil = $fotografer['foto_profil'] ?? null;
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] != UPLOAD_ERR_NO_FILE) {
        // Cek error upload dari PHP
        if ($_FILES['foto_profil']['error'] != 0) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => "File terlalu besar (melebihi batas upload server).",
                UPLOAD_ERR_FORM_SIZE => "File terlalu besar (melebihi batas form).",
                UPLOAD_ERR_PARTIAL => "File hanya terunggah sebagian.",
                UPLOAD_ERR_NO_TMP_DIR => "Direktori sementara untuk upload tidak ditemukan.",
                UPLOAD_ERR_CANT_WRITE => "Gagal menulis file ke disk.",
                UPLOAD_ERR_EXTENSION => "Upload dihentikan oleh ekstensi PHP."
            ];
            $error_message = $upload_errors[$_FILES['foto_profil']['error']] ?? "Error upload tidak diketahui.";
            echo '<script>alert("' . $error_message . '");</script>';
        } else {
            $target_dir = "img/uploads/";
            // Pastikan direktori ada
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            if (!is_writable($target_dir)) {
                echo '<script>alert("Folder ' . $target_dir . ' tidak dapat ditulis. Periksa izin folder.");</script>';
            } else {
                $file_extension = strtolower(pathinfo($_FILES["foto_profil"]["name"], PATHINFO_EXTENSION));
                $new_filename = "profile_" . $user_id . "_" . time() . "." . $file_extension;
                $target_file = $target_dir . $new_filename;

                // Validasi file
                $allowed_extensions = array("jpg", "jpeg", "png");
                if (!in_array($file_extension, $allowed_extensions)) {
                    echo '<script>alert("File harus berupa JPG atau PNG!");</script>';
                } elseif ($_FILES["foto_profil"]["size"] > 2000000) {
                    echo '<script>alert("File terlalu besar, maksimal 2MB!");</script>';
                } else {
                    if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $target_file)) {
                        $foto_profil = $target_file;
                        // Hapus foto lama jika ada
                        if ($fotografer['foto_profil'] && file_exists($fotografer['foto_profil'])) {
                            if (!unlink($fotografer['foto_profil'])) {
                                echo '<script>alert("Gagal menghapus foto lama!");</script>';
                            }
                        }
                    } else {
                        echo '<script>alert("Gagal mengunggah foto ke server! Periksa izin folder ' . $target_dir . '.");</script>';
                    }
                }
            }
        }
    }

    // Update data di database dengan prepared statement
    if ($koneksi) {
        $stmt = $koneksi->prepare("UPDATE user SET nama_lengkap=?, alamat=?, email=?, no_telp=?, foto_profil=? WHERE user_id=?");
        if ($stmt) {
            $stmt->bind_param("sssssi", $nama_lengkap, $alamat, $email, $no_telp, $foto_profil, $user_id);
            if ($stmt->execute()) {
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                echo '<script>alert("Profil berhasil diperbarui!"); window.location="fotografer_profil.php";</script>';
            } else {
                echo '<script>alert("Gagal memperbarui profil: ' . $koneksi->error . '");</script>';
            }
            $stmt->close();
        } else {
            echo '<script>alert("Gagal menyiapkan statement: ' . $koneksi->error . '");</script>';
        }
    } else {
        echo '<script>alert("Koneksi ke database gagal!");</script>';
    }
}

// Ambil data fotografer untuk ditampilkan
$query = "SELECT * FROM user WHERE user_id='$user_id'";
$result = mysqli_query($koneksi, $query);
$fotografer = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Profil Fotografer</title>
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

        .profile-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-header h1 {
            font-size: 24px;
            color: #1e3a8a;
        }

        .profile-form label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }

        .profile-form input[type="text"],
        .profile-form input[type="email"],
        .profile-form input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .profile-form img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .profile-form button {
            background: #1e3a8a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .profile-form button:hover {
            background: #163a6e;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                left: -200px;
            }

            .main-content.shifted {
                margin-left: 200px;
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
            <img src="<?php echo $fotografer['foto_profil'] ?? 'https://via.placeholder.com/60'; ?>" alt="Profil Fotografer">
            <h3><?php echo $fotografer['nama_lengkap']; ?></h3>
            <p>Fotografer</p>
        </div>
        <div class="sidebar-menu">
            <a href="fotografer_profil.php"><i class="fas fa-user"></i> Profil</a>
            <a href="fotografer.php"><i class="fas fa-calendar-alt"></i> Jadwal Pemotretan</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content" id="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <h1>Profil Fotografer</h1>
            </div>
            <form method="POST" class="profile-form" enctype="multipart/form-data">
                <label for="foto_profil">Foto Profil</label>
                <?php if ($fotografer['foto_profil']): ?>
                    <img src="<?php echo $fotografer['foto_profil']; ?>" alt="Foto Profil">
                <?php endif; ?>
                <input type="file" id="foto_profil" name="foto_profil" accept="image/jpeg, image/png">

                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo $fotografer['nama_lengkap']; ?>" required>

                <label for="alamat">Alamat</label>
                <input type="text" id="alamat" name="alamat" value="<?php echo $fotografer['alamat']; ?>" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo $fotografer['email']; ?>" required>

                <label for="no_telp">No. Telepon</label>
                <input type="text" id="no_telp" name="no_telp" value="<?php echo $fotografer['no_telp']; ?>" required>

                <button type="submit">Simpan Perubahan</button>
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
    </script>
</body>
</html>