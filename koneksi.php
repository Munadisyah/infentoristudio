<?php
$koneksi = mysqli_connect("localhost", "root", "", "db_foto");

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>