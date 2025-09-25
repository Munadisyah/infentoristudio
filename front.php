<?php
include "koneksi.php";
?>

<!-- Start Banner Area -->
<section class="banner-area relative" id="home">			
    <div class="slider">
        <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner" role="listbox">
                <!-- Slide One - Set the background image for this slide in the line below -->
                <div class="carousel-item active" style="background-image: url('img/slider4.jpg')">
                    <div class="carousel-caption d-md-block">
                        <h2 class="text-uppercase">Booking Photo Studio</h2>
                        <p>Sewa Photographer untuk berbagai macam keperluan dengan cepat</p>
                        <div>
                            <a href="?page=module/booking" class="btn btn-primary">Booking Sekarang</a>
                        </div>
                    </div>
                </div>
                <!-- Slide Two - Set the background image for this slide in the line below -->
                <div class="carousel-item" style="background-image: url('img/slider3new.jpg')">
                    <div class="carousel-caption d-md-block">
                        <h2 class="text-uppercase">Sewa Photographer</h2>
                        <p>Cepat dan Mudah</p>
                        <div>
                            <a href="?page=module/booking" class="btn btn-primary text-uppercase">Booking Sekarang</a>
                        </div>
                    </div>
                </div>
                <!-- Slide Three - Set the background image for this slide in the line below -->
                <div class="carousel-item" style="background-image: url('img/slider2.jpg')">
                    <div class="carousel-caption d-md-block">
                        <h2 class="text-uppercase">Booking Sekarang</h2>
                        <p>Kepuasan Pelanggan adalah tujuan kami</p>
                        <div>
                            <a href="?page=module/booking" class="btn btn-primary">Booking Sekarang</a>
                        </div>
                    </div>
                </div>
            </div>
            <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a>
        </div>
    </div>
</section>
<!-- End Banner Area -->

<!-- Start Hasil Foto Area -->
<section class="gallery-area section-gap" id="gallery">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 pb-30 header-text text-center">
                <h1 class="text-white">Hasil Foto</h1>
                <p class="text-white">Berikut ini hasil jepretan dari foto studio kami</p>
            </div>
        </div>
        <div class="row">
            <?php
            $query_foto = mysqli_query($koneksi, "SELECT * FROM tb_hasil_foto ORDER BY tanggal_upload DESC LIMIT 10");
            if (mysqli_num_rows($query_foto) > 0) {
                while ($foto = mysqli_fetch_assoc($query_foto)) {
                    echo '<div class="col-md-4 col-sm-6 mb-4">
                        <div class="card" data-aos="fade-up" data-aos-delay="200">
                            <a href="img/hasil_foto/' . $foto['file_foto'] . '" data-lightbox="gallery">
                                <img src="img/hasil_foto/' . $foto['file_foto'] . '" class="card-img-top" alt="' . $foto['nama_foto'] . '">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title">' . $foto['nama_foto'] . '</h5>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                echo '<div class="col-12 text-center"><p class="text-white">Belum ada foto yang diunggah.</p></div>';
            }
            ?>
        </div>
    </div>
</section>
<!-- End Hasil Foto Area -->

<!-- Start Promo Area -->
<section class="promo-area section-gap">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 pb-30 header-text text-center">
                <h1 class="text-white">Promo Spesial</h1>
                <p class="text-white">Jangan lewatkan penawaran menarik dari kami!</p>
            </div>
        </div>
        <div class="row">
            <?php
            $today = date('Y-m-d');
            $query_promo = mysqli_query($koneksi, "SELECT * FROM tb_promo WHERE tanggal_mulai <= '$today' AND tanggal_selesai >= '$today' ORDER BY tanggal_upload DESC LIMIT 3");
            if (mysqli_num_rows($query_promo) > 0) {
                while ($promo = mysqli_fetch_assoc($query_promo)) {
                    echo '<div class="col-md-4 col-sm-6 mb-4">
                        <div class="card" data-aos="fade-up" data-aos-delay="200">
                            <img src="img/promo/' . $promo['gambar_promo'] . '" class="card-img-top" alt="' . $promo['judul_promo'] . '">
                            <div class="card-body">
                                <h5 class="card-title">' . $promo['judul_promo'] . '</h5>
                                <p class="card-text">' . $promo['deskripsi'] . '</p>
                                <p class="card-text"><small class="text-muted">Berlaku: ' . date('d M Y', strtotime($promo['tanggal_mulai'])) . ' - ' . date('d M Y', strtotime($promo['tanggal_selesai'])) . '</small></p>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                echo '<div class="col-12 text-center"><p class="text-white">Tidak ada promo saat ini.</p></div>';
            }
            ?>
        </div>
    </div>
</section>
<!-- End Promo Area -->

<style>
.section-gap {
    padding: 80px 0;
}
.gallery-area, .promo-area {
    background: #1e40af; /* Warna latar belakang sesuai tema */
}
.header-text h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 1rem;
}
.header-text p {
    font-size: 1.1rem;
    color: #fff;
}
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s;
    background: #fff;
}
.card:hover {
    transform: translateY(-5px);
}
.card-img-top {
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    height: 200px;
    object-fit: cover;
}
.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e40af;
}
.card-text {
    font-size: 0.95rem;
    color: #6c757d;
}
.gal {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
}
</style>