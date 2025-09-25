<?php
include("koneksi.php");
if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($koneksi, $_GET['token']);
    $cek = mysqli_query($koneksi, "SELECT * FROM user WHERE reset_token='$token' AND reset_expires > NOW()");
    if (mysqli_num_rows($cek) > 0) {
        echo "Form reset password akan ada di sini. Token valid!";
        // Tambahkan form untuk input password baru
    } else {
        echo '<script>alert("Token tidak valid atau kadaluarsa!"); window.location="forgot_password.php";</script>';
    }
}
?>