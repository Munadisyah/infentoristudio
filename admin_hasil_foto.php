<?php
include("koneksi.php");
session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['level']) || $_SESSION['level'] != "admin") {
    echo '<script>alert("Anda tidak punya akses!"); window.location="index.php";</script>';
    exit();
}

$user_id = mysqli_real_escape_string($koneksi, $_SESSION['user_id']);
$query = "SELECT * FROM user WHERE user_id='$user_id'";
$result = mysqli_query($koneksi, $query);
$admin = mysqli_fetch_assoc($result);

// Fungsi untuk logging perubahan
function log_change($koneksi, $action, $id_paket, $old_value, $new_value, $description) {
    $user_id = mysqli_real_escape_string($koneksi, $_SESSION['user_id']);
    $log_query = "INSERT INTO tb_log_promo (user_id, action, id_paket, old_value, new_value, description, log_time) VALUES ('$user_id', '$action', '$id_paket', '$old_value', '$new_value', '$description', NOW())";
    mysqli_query($koneksi, $log_query) or error_log("Gagal log: " . mysqli_error($koneksi));
}

// Reset harga diskon berdasarkan promo aktif dengan prioritas diskon tertinggi
$reset_query = mysqli_query($koneksi, "
    UPDATE tb_paket p
    LEFT JOIN (
        SELECT id_paket, MAX(persentase_diskon) as max_diskon
        FROM tb_promo
        WHERE NOW() BETWEEN tanggal_mulai AND tanggal_selesai
        GROUP BY id_paket
    ) pr ON p.id_paket = pr.id_paket
    SET p.harga_diskon = IF(pr.max_diskon IS NOT NULL, p.harga * (1 - pr.max_diskon / 100), p.harga)
");
if (!$reset_query) {
    error_log("Gagal reset harga diskon: " . mysqli_error($koneksi));
} else {
    // Log reset otomatis
    $result = mysqli_query($koneksi, "SELECT id_paket, harga, harga_diskon FROM tb_paket WHERE harga_diskon != harga");
    while ($row = mysqli_fetch_assoc($result)) {
        log_change($koneksi, 'auto_reset', $row['id_paket'], $row['harga_diskon'], $row['harga'], 'Reset harga diskon otomatis setelah promo berakhir');
    }
}

// Cek promo yang akan berakhir dalam 2 hari untuk notifikasi
$warning_query = mysqli_query($koneksi, "
    SELECT p.id_paket, p.judul_promo, p.tanggal_selesai
    FROM tb_promo p
    WHERE DATE_ADD(NOW(), INTERVAL 2 DAY) >= p.tanggal_selesai
    AND p.tanggal_selesai > NOW()
");
$warnings = [];
while ($warning = mysqli_fetch_assoc($warning_query)) {
    $warnings[] = "Promo '{$warning['judul_promo']}' untuk paket ID {$warning['id_paket']} akan berakhir pada " . date('d M Y', strtotime($warning['tanggal_selesai']));
}

// Ambil daftar paket untuk dropdown
$paket_query = mysqli_query($koneksi, "SELECT id_paket, nama_paket FROM tb_paket ORDER BY nama_paket ASC");
$paket_options = [];
while ($paket = mysqli_fetch_assoc($paket_query)) {
    $paket_options[$paket['id_paket']] = $paket['nama_paket'];
}

// Logika untuk mengunggah foto hasil
if (isset($_POST['upload_foto'])) {
    $nama_foto = mysqli_real_escape_string($koneksi, $_POST['nama_foto']);
    $foto = $_FILES['file_foto']['name'];
    $path_foto = $_FILES['file_foto']['tmp_name'];
    $file_type = strtolower(pathinfo($foto, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (empty($nama_foto) || empty($foto)) {
        echo '<script>alert("Semua field wajib diisi!");</script>';
    } elseif (!in_array($file_type, $allowed_types)) {
        echo '<script>alert("Tipe file tidak diizinkan! Hanya JPG, JPEG, atau PNG.");</script>';
    } elseif ($_FILES['file_foto']['size'] > $max_size) {
        echo '<script>alert("Ukuran file terlalu besar! Maksimal 5MB.");</script>';
    } else {
        $upload_dir = "img/hasil_foto/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $new_filename = uniqid() . "_" . basename($foto);
        $dir_foto = $upload_dir . $new_filename;

        if (move_uploaded_file($path_foto, $dir_foto)) {
            $query = "INSERT INTO tb_hasil_foto (nama_foto, file_foto, tanggal_upload) VALUES ('$nama_foto', '$new_filename', NOW())";
            if (mysqli_query($koneksi, $query)) {
                echo '<script>alert("Foto berhasil diunggah!"); window.location="admin_hasil_foto.php";</script>';
            } else {
                unlink($dir_foto); // Hapus file jika query gagal
                echo '<script>alert("Gagal menyimpan data ke database: " . mysqli_error($koneksi) . "");</script>';
            }
        } else {
            echo '<script>alert("Gagal mengunggah file!");</script>';
        }
    }
}

// Logika untuk mengunggah promo dengan validasi overlap
if (isset($_POST['upload_promo'])) {
    $judul_promo = mysqli_real_escape_string($koneksi, $_POST['judul_promo']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai']);
    $tanggal_selesai = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);
    $gambar = $_FILES['gambar_promo']['name'];
    $path_gambar = $_FILES['gambar_promo']['tmp_name'];
    $id_paket = mysqli_real_escape_string($koneksi, $_POST['id_paket']);
    $persentase_diskon = mysqli_real_escape_string($koneksi, $_POST['persentase_diskon']);
    $file_type = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png'];
    $max_size = 5 * 1024 * 1024; // 5MB

    $tanggal_mulai_date = new DateTime($tanggal_mulai);
    $tanggal_selesai_date = new DateTime($tanggal_selesai);
    $now = new DateTime();

    // Validasi overlap promo
    $overlap_query = mysqli_query($koneksi, "SELECT COUNT(*) as overlap FROM tb_promo WHERE id_paket = '$id_paket' AND (
        (tanggal_mulai <= '$tanggal_mulai' AND tanggal_selesai >= '$tanggal_mulai') OR
        (tanggal_mulai <= '$tanggal_selesai' AND tanggal_selesai >= '$tanggal_selesai') OR
        (tanggal_mulai >= '$tanggal_mulai' AND tanggal_selesai <= '$tanggal_selesai')
    )");
    $overlap = mysqli_fetch_assoc($overlap_query)['overlap'];

    if ($tanggal_mulai_date > $tanggal_selesai_date) {
        echo '<script>alert("Tanggal mulai tidak boleh melebihi tanggal selesai!");</script>';
    } elseif ($overlap > 0) {
        echo '<script>alert("Terjadi tumpang tindih dengan promo lain untuk paket ini!");</script>';
    } elseif ($now > $tanggal_selesai_date) {
        echo '<script>alert("Tanggal selesai tidak boleh sebelum hari ini!");</script>';
    } elseif (empty($judul_promo) || empty($deskripsi) || empty($tanggal_mulai) || empty($tanggal_selesai) || empty($gambar) || empty($id_paket) || empty($persentase_diskon)) {
        echo '<script>alert("Semua field wajib diisi!");</script>';
    } elseif (!in_array($file_type, $allowed_types)) {
        echo '<script>alert("Tipe file tidak diizinkan! Hanya JPG, JPEG, atau PNG.");</script>';
    } elseif ($_FILES['gambar_promo']['size'] > $max_size) {
        echo '<script>alert("Ukuran file terlalu besar! Maksimal 5MB.");</script>';
    } elseif ($persentase_diskon < 0 || $persentase_diskon > 100) {
        echo '<script>alert("Persentase diskon harus antara 0% dan 100%!");</script>';
    } else {
        $upload_dir = "img/promo/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $new_filename = uniqid() . "_" . basename($gambar);
        $dir_gambar = $upload_dir . $new_filename;
        error_log("Uploading promo image to: " . $dir_gambar);

        if (move_uploaded_file($path_gambar, $dir_gambar)) {
            $query = "INSERT INTO tb_promo (judul_promo, deskripsi, gambar_promo, tanggal_mulai, tanggal_selesai, tanggal_upload, id_paket, persentase_diskon) VALUES ('$judul_promo', '$deskripsi', '$new_filename', '$tanggal_mulai', '$tanggal_selesai', NOW(), '$id_paket', '$persentase_diskon')";
            if (mysqli_query($koneksi, $query)) {
                $discount_rate = $persentase_diskon / 100;
                $paket_harga = mysqli_query($koneksi, "SELECT harga FROM tb_paket WHERE id_paket = '$id_paket'");
                $paket = mysqli_fetch_assoc($paket_harga);
                $harga_asli = $paket['harga'];
                $harga_diskon = $harga_asli * (1 - $discount_rate);
                $query_update = "UPDATE tb_paket SET harga_diskon = '$harga_diskon' WHERE id_paket = '$id_paket'";
                if (mysqli_query($koneksi, $query_update)) {
                    log_change($koneksi, 'promo_added', $id_paket, $harga_asli, $harga_diskon, "Promo baru ditambahkan: $judul_promo");
                    echo '<script>alert("Promo berhasil diunggah dan harga diskon diperbarui!"); window.location="admin_hasil_foto.php";</script>';
                } else {
                    echo '<script>alert("Promo berhasil diunggah, tapi gagal update harga diskon: " . mysqli_error($koneksi) . "");</script>';
                }
            } else {
                unlink($dir_gambar); // Hapus file jika query gagal
                echo '<script>alert("Gagal menyimpan data ke database: " . mysqli_error($koneksi) . "");</script>';
            }
        } else {
            echo '<script>alert("Gagal mengunggah file!");</script>';
        }
    }
}

// Logika untuk mengedit promo dengan validasi overlap
if (isset($_POST['edit_promo'])) {
    $id_promo = mysqli_real_escape_string($koneksi, $_POST['id_promo']);
    $judul_promo = mysqli_real_escape_string($koneksi, $_POST['judul_promo']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai']);
    $tanggal_selesai = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);
    $id_paket = mysqli_real_escape_string($koneksi, $_POST['id_paket']);
    $persentase_diskon = mysqli_real_escape_string($koneksi, $_POST['persentase_diskon']);
    $gambar_lama = mysqli_real_escape_string($koneksi, $_POST['gambar_lama']);

    $tanggal_mulai_date = new DateTime($tanggal_mulai);
    $tanggal_selesai_date = new DateTime($tanggal_selesai);
    $now = new DateTime();

    // Validasi overlap promo kecuali promo yang sedang diedit
    $overlap_query = mysqli_query($koneksi, "SELECT COUNT(*) as overlap FROM tb_promo WHERE id_paket = '$id_paket' AND id_promo != '$id_promo' AND (
        (tanggal_mulai <= '$tanggal_mulai' AND tanggal_selesai >= '$tanggal_mulai') OR
        (tanggal_mulai <= '$tanggal_selesai' AND tanggal_selesai >= '$tanggal_selesai') OR
        (tanggal_mulai >= '$tanggal_mulai' AND tanggal_selesai <= '$tanggal_selesai')
    )");
    $overlap = mysqli_fetch_assoc($overlap_query)['overlap'];

    if ($tanggal_mulai_date > $tanggal_selesai_date) {
        echo '<script>alert("Tanggal mulai tidak boleh melebihi tanggal selesai!");</script>';
    } elseif ($overlap > 0) {
        echo '<script>alert("Terjadi tumpang tindih dengan promo lain untuk paket ini!");</script>';
    } elseif ($now > $tanggal_selesai_date) {
        echo '<script>alert("Tanggal selesai tidak boleh sebelum hari ini!");</script>';
    } elseif (empty($judul_promo) || empty($deskripsi) || empty($tanggal_mulai) || empty($tanggal_selesai) || empty($id_paket) || empty($persentase_diskon)) {
        echo '<script>alert("Semua field wajib diisi!");</script>';
    } elseif ($persentase_diskon < 0 || $persentase_diskon > 100) {
        echo '<script>alert("Persentase diskon harus antara 0% dan 100%!");</script>';
    } else {
        $gambar = $_FILES['gambar_promo']['name'];
        $path_gambar = $_FILES['gambar_promo']['tmp_name'];
        $new_filename = $gambar_lama;

        if (!empty($gambar)) {
            $file_type = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_type, $allowed_types)) {
                echo '<script>alert("Tipe file tidak diizinkan! Hanya JPG, JPEG, atau PNG.");</script>';
            } elseif ($_FILES['gambar_promo']['size'] > $max_size) {
                echo '<script>alert("Ukuran file terlalu besar! Maksimal 5MB.");</script>';
            } else {
                $upload_dir = "img/promo/";
                $new_filename = uniqid() . "_" . basename($gambar);
                $dir_gambar = $upload_dir . $new_filename;
                error_log("Updating promo image to: " . $dir_gambar);

                if (move_uploaded_file($path_gambar, $dir_gambar)) {
                    $file_path_lama = $upload_dir . $gambar_lama;
                    if (file_exists($file_path_lama) && $gambar_lama != 'default.jpg') {
                        unlink($file_path_lama);
                    }
                } else {
                    echo '<script>alert("Gagal mengunggah file baru!");</script>';
                    exit();
                }
            }
        }

        // Ambil harga lama sebelum update
        $old_harga_query = mysqli_query($koneksi, "SELECT harga_diskon FROM tb_paket WHERE id_paket = '$id_paket'");
        $old_harga = mysqli_fetch_assoc($old_harga_query)['harga_diskon'];

        $query = "UPDATE tb_promo SET judul_promo='$judul_promo', deskripsi='$deskripsi', gambar_promo='$new_filename', tanggal_mulai='$tanggal_mulai', tanggal_selesai='$tanggal_selesai', id_paket='$id_paket', persentase_diskon='$persentase_diskon' WHERE id_promo='$id_promo'";
        if (mysqli_query($koneksi, $query)) {
            $discount_rate = $persentase_diskon / 100;
            $paket_harga = mysqli_query($koneksi, "SELECT harga FROM tb_paket WHERE id_paket = '$id_paket'");
            $paket = mysqli_fetch_assoc($paket_harga);
            $harga_asli = $paket['harga'];
            $harga_diskon = $harga_asli * (1 - $discount_rate);
            $query_update = "UPDATE tb_paket SET harga_diskon = '$harga_diskon' WHERE id_paket = '$id_paket'";
            if (mysqli_query($koneksi, $query_update)) {
                log_change($koneksi, 'promo_updated', $id_paket, $old_harga, $harga_diskon, "Promo diperbarui: $judul_promo");
                echo '<script>alert("Promo berhasil diperbarui dan harga diskon diperbarui!"); window.location="admin_hasil_foto.php";</script>';
            } else {
                echo '<script>alert("Promo berhasil diperbarui, tapi gagal update harga diskon: " . mysqli_error($koneksi) . "");</script>';
            }
        } else {
            echo '<script>alert("Gagal memperbarui data: " . mysqli_error($koneksi) . "");</script>';
        }
    }
}

// Logika untuk mengedit foto hasil
if (isset($_POST['edit_foto'])) {
    $id_foto = mysqli_real_escape_string($koneksi, $_POST['id_foto']);
    $nama_foto = mysqli_real_escape_string($koneksi, $_POST['nama_foto']);
    $foto_lama = mysqli_real_escape_string($koneksi, $_POST['foto_lama']);
    $foto = $_FILES['file_foto']['name'];
    $path_foto = $_FILES['file_foto']['tmp_name'];
    $new_filename = $foto_lama;

    if (!empty($foto)) {
        $file_type = strtolower(pathinfo($foto, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_type, $allowed_types)) {
            echo '<script>alert("Tipe file tidak diizinkan! Hanya JPG, JPEG, atau PNG.");</script>';
        } elseif ($_FILES['file_foto']['size'] > $max_size) {
            echo '<script>alert("Ukuran file terlalu besar! Maksimal 5MB.");</script>';
        } else {
            $upload_dir = "img/hasil_foto/";
            $new_filename = uniqid() . "_" . basename($foto);
            $dir_foto = $upload_dir . $new_filename;

            if (move_uploaded_file($path_foto, $dir_foto)) {
                $file_path_lama = $upload_dir . $foto_lama;
                if (file_exists($file_path_lama) && $foto_lama != 'default.jpg') {
                    unlink($file_path_lama);
                }
            } else {
                echo '<script>alert("Gagal mengunggah file baru!");</script>';
                exit();
            }
        }
    }

    $query = "UPDATE tb_hasil_foto SET nama_foto='$nama_foto', file_foto='$new_filename' WHERE id_foto='$id_foto'";
    if (mysqli_query($koneksi, $query)) {
        echo '<script>alert("Foto berhasil diperbarui!"); window.location="admin_hasil_foto.php";</script>';
    } else {
        echo '<script>alert("Gagal memperbarui data: " . mysqli_error($koneksi) . "");</script>';
    }
}

// Logika untuk menghapus foto
if (isset($_POST['hapus_foto'])) {
    $id_foto = mysqli_real_escape_string($koneksi, $_POST['id_foto']);
    $query = mysqli_query($koneksi, "SELECT file_foto FROM tb_hasil_foto WHERE id_foto='$id_foto'");
    $data = mysqli_fetch_assoc($query);
    if ($data) {
        $file_path = "img/hasil_foto/" . $data['file_foto'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $delete_query = mysqli_query($koneksi, "DELETE FROM tb_hasil_foto WHERE id_foto='$id_foto'");
        if ($delete_query) {
            echo '<script>alert("Foto berhasil dihapus!"); window.location="admin_hasil_foto.php";</script>';
        } else {
            echo '<script>alert("Gagal menghapus foto: " . mysqli_error($koneksi) . "");</script>';
        }
    }
}

// Logika untuk menghapus promo
if (isset($_POST['hapus_promo'])) {
    $id_promo = mysqli_real_escape_string($koneksi, $_POST['id_promo']);
    $query = mysqli_query($koneksi, "SELECT gambar_promo, id_paket FROM tb_promo WHERE id_promo='$id_promo'");
    $data = mysqli_fetch_assoc($query);
    if ($data) {
        $file_path = "img/promo/" . $data['gambar_promo'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $delete_query = mysqli_query($koneksi, "DELETE FROM tb_promo WHERE id_promo='$id_promo'");
        if ($delete_query) {
            // Reset harga diskon ke harga asli setelah promo dihapus
            $id_paket = $data['id_paket'];
            $old_harga_query = mysqli_query($koneksi, "SELECT harga_diskon FROM tb_paket WHERE id_paket = '$id_paket'");
            $old_harga = mysqli_fetch_assoc($old_harga_query)['harga_diskon'];
            $update_harga = mysqli_query($koneksi, "UPDATE tb_paket SET harga_diskon = harga WHERE id_paket = '$id_paket'");
            if ($update_harga) {
                log_change($koneksi, 'promo_deleted', $id_paket, $old_harga, mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT harga FROM tb_paket WHERE id_paket = '$id_paket'"))['harga'], 'Promo dihapus');
            }
            echo '<script>alert("Promo berhasil dihapus!"); window.location="admin_hasil_foto.php";</script>';
        } else {
            echo '<script>alert("Gagal menghapus promo: " . mysqli_error($koneksi) . "");</script>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Foto Hasil & Promo</title>
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
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 24px;
            background: none;
            border: none;
            color: #1e3a8a;
            cursor: pointer;
            z-index: 1101;
            display: block;
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

        h2 {
            color: #1e3a8a;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .form-container, .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 500;
            color: #1e3a8a;
        }

        .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 8px;
            width: 100%;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background: #1e3a8a;
            color: white;
            padding: 10px;
            text-align: center;
            border: none;
            font-weight: 500;
            font-size: 14px;
        }

        .table tbody td {
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
            font-size: 13px;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        .table img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }

        .btn-primary {
            background: #1e3a8a;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
            margin-right: 5px;
        }

        .btn-primary:hover {
            background: #163a79;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                left: -200px;
            }
            .main-content.shifted {
                margin-left: 200px;
            }
            .table img {
                width: 80px;
                height: 80px;
            }
            .modal-content {
                width: 90%;
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
            <h3><?php echo $admin['nama_lengkap'] ?? 'Admin'; ?></h3>
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
        <?php if (!empty($warnings)): ?>
            <div class="alert alert-warning" role="alert">
                <strong>Peringatan:</strong><br>
                <?php echo implode('<br>', $warnings); ?>
            </div>
        <?php endif; ?>

        <!-- Form Upload Foto Hasil -->
        <h2>Kelola Foto Hasil</h2>
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Nama Foto</label>
                    <input type="text" class="form-control" name="nama_foto" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unggah Foto</label>
                    <input type="file" class="form-control" name="file_foto" accept="image/jpg,image/jpeg,image/png" required>
                </div>
                <button type="submit" name="upload_foto" class="btn-primary">Unggah Foto</button>
            </form>
        </div>

        <!-- Tabel Foto Hasil -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Foto</th>
                        <th>Foto</th>
                        <th>Tanggal Upload</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_foto = mysqli_query($koneksi, "SELECT * FROM tb_hasil_foto ORDER BY tanggal_upload DESC");
                    $no = 1;
                    while ($data = mysqli_fetch_assoc($query_foto)) {
                        echo "<tr>
                            <td>$no</td>
                            <td>" . htmlspecialchars($data['nama_foto']) . "</td>
                            <td><img src='img/hasil_foto/" . htmlspecialchars($data['file_foto']) . "' alt='" . htmlspecialchars($data['nama_foto']) . "'></td>
                            <td>" . date('d M Y H:i', strtotime($data['tanggal_upload'])) . "</td>
                            <td>
                                <button class='btn-primary edit-foto-btn' onclick='openEditFotoModal(" . htmlspecialchars(json_encode($data)) . ")'>Edit</button>
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='id_foto' value='" . htmlspecialchars($data['id_foto']) . "'>
                                    <button type='submit' name='hapus_foto' class='btn-danger' onclick='return confirm(\"Yakin ingin menghapus foto ini?\")'>Hapus</button>
                                </form>
                            </td>
                        </tr>";
                        $no++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Form Upload Promo -->
        <h2>Kelola Promo</h2>
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Judul Promo</label>
                    <input type="text" class="form-control" name="judul_promo" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi Promo</label>
                    <textarea class="form-control" name="deskripsi" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="tanggal_mulai" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control" name="tanggal_selesai" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Pilih Paket</label>
                    <select class="form-control" name="id_paket" required>
                        <option value="">Pilih Paket</option>
                        <?php
                        foreach ($paket_options as $id => $nama) {
                            echo "<option value='$id'>$nama</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Persentase Diskon (%)</label>
                    <input type="number" class="form-control" name="persentase_diskon" min="0" max="100" step="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unggah Gambar Promo</label>
                    <input type="file" class="form-control" name="gambar_promo" accept="image/jpg,image/jpeg,image/png" required>
                </div>
                <button type="submit" name="upload_promo" class="btn-primary">Unggah Promo</button>
            </form>
        </div>

        <!-- Tabel Promo -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul Promo</th>
                        <th>Deskripsi</th>
                        <th>Gambar</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Paket</th>
                        <th>Diskon (%)</th>
                        <th>Tanggal Upload</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_promo = mysqli_query($koneksi, "SELECT p.*, t.nama_paket FROM tb_promo p LEFT JOIN tb_paket t ON p.id_paket = t.id_paket ORDER BY tanggal_upload DESC");
                    $no = 1;
                    while ($data = mysqli_fetch_assoc($query_promo)) {
                        echo "<tr>
                            <td>$no</td>
                            <td>" . htmlspecialchars($data['judul_promo']) . "</td>
                            <td>" . htmlspecialchars($data['deskripsi']) . "</td>
                            <td><img src='img/promo/" . htmlspecialchars($data['gambar_promo']) . "' alt='" . htmlspecialchars($data['judul_promo']) . "'></td>
                            <td>" . date('d M Y', strtotime($data['tanggal_mulai'])) . "</td>
                            <td>" . date('d M Y', strtotime($data['tanggal_selesai'])) . "</td>
                            <td>" . htmlspecialchars($data['nama_paket'] ?? '-') . "</td>
                            <td>" . htmlspecialchars($data['persentase_diskon']) . "%</td>
                            <td>" . date('d M Y H:i', strtotime($data['tanggal_upload'])) . "</td>
                            <td>
                                <button class='btn-primary edit-btn' onclick='openEditModal(" . htmlspecialchars(json_encode($data)) . ")'>Edit</button>
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='id_promo' value='" . htmlspecialchars($data['id_promo']) . "'>
                                    <button type='submit' name='hapus_promo' class='btn-danger' onclick='return confirm(\"Yakin ingin menghapus promo ini?\")'>Hapus</button>
                                </form>
                            </td>
                        </tr>";
                        $no++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Modal Edit Promo -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">×</span>
                <h3>Edit Promo</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_promo" id="edit_id_promo">
                    <input type="hidden" name="gambar_lama" id="edit_gambar_lama">
                    <div class="mb-3">
                        <label class="form-label">Judul Promo</label>
                        <input type="text" class="form-control" name="judul_promo" id="edit_judul_promo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi Promo</label>
                        <textarea class="form-control" name="deskripsi" id="edit_deskripsi" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" name="tanggal_mulai" id="edit_tanggal_mulai" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" name="tanggal_selesai" id="edit_tanggal_selesai" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih Paket</label>
                        <select class="form-control" name="id_paket" id="edit_id_paket" required>
                            <option value="">Pilih Paket</option>
                            <?php
                            $paket_query = mysqli_query($koneksi, "SELECT id_paket, nama_paket FROM tb_paket ORDER BY nama_paket ASC");
                            while ($paket = mysqli_fetch_assoc($paket_query)) {
                                echo "<option value='" . $paket['id_paket'] . "'>" . $paket['nama_paket'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Persentase Diskon (%)</label>
                        <input type="number" class="form-control" name="persentase_diskon" id="edit_persentase_diskon" min="0" max="100" step="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gambar Promo (Kosongkan jika tidak ingin mengganti)</label>
                        <input type="file" class="form-control" name="gambar_promo" id="edit_gambar_promo" accept="image/jpg,image/jpeg,image/png">
                    </div>
                    <button type="submit" name="edit_promo" class="btn-primary">Simpan Perubahan</button>
                </form>
            </div>
        </div>

        <!-- Modal Edit Foto Hasil -->
        <div id="editFotoModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditFotoModal()">×</span>
                <h3>Edit Foto</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_foto" id="edit_id_foto">
                    <input type="hidden" name="foto_lama" id="edit_foto_lama">
                    <div class="mb-3">
                        <label class="form-label">Nama Foto</label>
                        <input type="text" class="form-control" name="nama_foto" id="edit_nama_foto" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unggah Foto Baru (Kosongkan jika tidak ingin mengganti)</label>
                        <input type="file" class="form-control" name="file_foto" id="edit_file_foto" accept="image/jpg,image/jpeg,image/png">
                    </div>
                    <button type="submit" name="edit_foto" class="btn-primary">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        function openEditModal(data) {
            document.getElementById('edit_id_promo').value = data.id_promo;
            document.getElementById('edit_judul_promo').value = data.judul_promo;
            document.getElementById('edit_deskripsi').value = data.deskripsi;
            document.getElementById('edit_tanggal_mulai').value = data.tanggal_mulai;
            document.getElementById('edit_tanggal_selesai').value = data.tanggal_selesai;
            document.getElementById('edit_id_paket').value = data.id_paket || '';
            document.getElementById('edit_persentase_diskon').value = data.persentase_diskon;
            document.getElementById('edit_gambar_lama').value = data.gambar_promo;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openEditFotoModal(data) {
            document.getElementById('edit_id_foto').value = data.id_foto;
            document.getElementById('edit_nama_foto').value = data.nama_foto;
            document.getElementById('edit_foto_lama').value = data.file_foto;
            document.getElementById('editFotoModal').style.display = 'block';
        }

        function closeEditFotoModal() {
            document.getElementById('editFotoModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            const fotoModal = document.getElementById('editFotoModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
            if (event.target == fotoModal) {
                fotoModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php mysqli_close($koneksi); ?>