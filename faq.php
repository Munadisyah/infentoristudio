<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta charset="UTF-8">
    <title>Bantuan & FAQ - Infentori Studio</title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/main.css">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Google Fonts (Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            background-color: #f4f4f4;
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }

        .faq-header {
            background: linear-gradient(135deg, #e0f7fa 0%, #ffffff 100%);
            color: #333;
            padding: 60px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .faq-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://www.transparenttextures.com/patterns/white-diamond.png');
            opacity: 0.1;
            z-index: 1;
        }

        .faq-header h1 {
            font-size: 40px;
            font-weight: 600;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }

        .faq-header p {
            font-size: 18px;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }

        .faq-search {
            padding: 20px 0;
            text-align: center;
        }

        .faq-search input {
            width: 100%;
            max-width: 500px;
            padding: 12px 20px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .faq-search input:focus {
            border-color:rgb(33, 182, 167);
            box-shadow: 0 2px 10px rgba(38, 166, 154, 0.2);
        }

        .faq-section {
            padding: 50px 0;
        }

        .accordion-item {
            border: none;
            margin-bottom: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .accordion-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }

        .accordion-button {
            background-color: #fff;
            color: #333;
            font-weight: 600;
            padding: 15px 20px;
            border: none;
            border-radius: 12px;
            transition: background-color 0.3s ease, color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .accordion-button::after {
            content: '';
            position: absolute;
            width: 0;
            height: 100%;
            background: rgba(38, 166, 154, 0.1);
            top: 0;
            left: 0;
            transition: width 0.4s ease;
            z-index: 0;
        }

        .accordion-button:hover::after {
            width: 100%;
        }

        .accordion-button:not(.collapsed) {
            background-color: #26a69a;
            color: #fff;
        }

        .accordion-button:focus {
            box-shadow: none;
        }

        .accordion-button i {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .accordion-button:not(.collapsed) i {
            transform: rotate(90deg);
        }

        .accordion-body {
            background-color: #fff;
            padding: 20px;
            border-radius: 0 0 12px 12px;
            line-height: 1.6;
            color: #555;
        }

        .contact-section {
            text-align: center;
            padding: 40px 0;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-top: 30px;
        }

        .contact-section h3 {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .contact-section p {
            font-size: 16px;
            margin-bottom: 20px;
            color: #666;
        }

        .contact-section .btn {
            background: linear-gradient(135deg, #26a69a, #4db6ac);
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 10px rgba(38, 166, 154, 0.3);
        }

        .contact-section .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(38, 166, 154, 0.5);
        }

        .contact-section .social-links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .contact-section .social-links a {
            color: #666;
            font-size: 26px;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .contact-section .social-links a:hover {
            color: #26a69a;
            transform: scale(1.2);
        }

        @media (max-width: 768px) {
            .faq-header h1 {
                font-size: 32px;
            }

            .faq-header p {
                font-size: 16px;
            }

            .faq-search input {
                max-width: 90%;
            }

            .contact-section h3 {
                font-size: 22px;
            }

            .contact-section p {
                font-size: 14px;
            }

            .contact-section .social-links a {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<section class="faq-header" data-aos="fade-down" data-aos-duration="800">
    <div class="container">
        <h1 data-aos="zoom-in" data-aos-delay="200">Bantuan & FAQ</h1>
        <p data-aos="fade-up" data-aos-delay="400">Kami siap membantu Anda! Temukan jawaban atas pertanyaan umum di bawah ini.</p>
    </div>
</section>

<!-- Search Bar -->
<section class="faq-search" data-aos="fade-up" data-aos-duration="800">
    <div class="container">
        <input type="text" id="faqSearch" placeholder="Cari pertanyaan..." onkeyup="searchFAQ()" data-aos="zoom-in" data-aos-delay="200">
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section" data-aos="fade-up" data-aos-duration="800">
    <div class="container">
        <div class="accordion" id="faqAccordion">
            <!-- FAQ Item 1 -->
            <div class="accordion-item faq-item" data-aos="fade-right" data-aos-delay="200">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        <i class="fas fa-chevron-right"></i> Bagaimana cara melakukan booking studio?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Untuk booking studio, Anda perlu login terlebih dahulu. Setelah login, buka menu "Booking" di halaman utama, pilih tanggal dan waktu yang tersedia, lalu pilih paket foto yang diinginkan. Isi informasi yang diperlukan, seperti nama, nomor telepon, dan detail sesi, lalu konfirmasi booking Anda. Anda akan menerima email konfirmasi setelah booking berhasil.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="accordion-item faq-item" data-aos="fade-right" data-aos-delay="400">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        <i class="fas fa-chevron-right"></i> Apa saja metode pembayaran yang diterima?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Kami menerima pembayaran melalui transfer bank (BCA, Mandiri, BNI), e-wallet (OVO, GoPay, DANA), serta kartu kredit (Visa, MasterCard). Detail metode pembayaran akan diberikan setelah Anda menyelesaikan proses booking. Pastikan untuk menyelesaikan pembayaran dalam waktu 24 jam agar booking Anda tidak dibatalkan.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="accordion-item faq-item" data-aos="fade-right" data-aos-delay="600">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        <i class="fas fa-chevron-right"></i> Bagaimana jika saya ingin membatalkan booking?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Anda dapat membatalkan booking melalui menu "List Pesanan" di akun Anda. Pilih booking yang ingin dibatalkan, lalu klik "Batalkan". Pembatalan gratis jika dilakukan lebih dari 48 jam sebelum jadwal. Jika kurang dari 48 jam, akan dikenakan biaya sebesar 50% dari total biaya. Untuk informasi lebih lanjut, silakan hubungi kami.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="accordion-item faq-item" data-aos="fade-right" data-aos-delay="800">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        <i class="fas fa-chevron-right"></i> Apakah saya bisa membawa peralatan sendiri?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Tentu saja! Anda diperbolehkan membawa peralatan sendiri, seperti kamera, tripod, atau properti foto. Harap beri tahu kami saat booking agar kami dapat menyiapkan ruang yang sesuai. Kami juga menyediakan peralatan studio lengkap, termasuk kamera, lighting, dan background, yang dapat Anda gunakan tanpa biaya tambahan.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="accordion-item faq-item" data-aos="fade-right" data-aos-delay="1000">
                <h2 class="accordion-header" id="headingFive">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                        <i class="fas fa-chevron-right"></i> Apa saja paket foto yang tersedia di Infentori Studio?
                    </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Kami menawarkan berbagai paket foto, seperti:
                        <ul>
                            <li><strong>Paket Personal:</strong> Sesi 1 jam, 10 foto digital, harga Rp500.000.</li>
                            <li><strong>Paket Keluarga:</strong> Sesi 2 jam, 20 foto digital + 5 cetak, harga Rp1.200.000.</li>
                            <li><strong>Paket Event:</strong> Sesi 3 jam, 30 foto digital + video highlight, harga Rp2.500.000.</li>
                        </ul>
                        Untuk detail lebih lanjut, Anda dapat melihat daftar paket di menu "Booking".
                    </div>
                </div>
            </div>

            <!-- FAQ Item 6 -->
            <div class="accordion-item faq-item" data-aos="fade-right" data-aos-delay="1200">
                <h2 class="accordion-header" id="headingSix">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                        <i class="fas fa-chevron-right"></i> Berapa lama sesi foto berlangsung?
                    </button>
                </h2>
                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Durasi sesi foto tergantung pada paket yang Anda pilih. Paket Personal biasanya berlangsung 1 jam, Paket Keluarga 2 jam, dan Paket Event hingga 3 jam. Waktu ini termasuk persiapan, pengambilan foto, dan pemilihan hasil awal. Jika Anda membutuhkan waktu tambahan, silakan hubungi kami untuk biaya perpanjangan.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 7 -->
            <div class="accordion-item faq-item" data-aos="fade-right" data-aos-delay="1400">
                <h2 class="accordion-header" id="headingSeven">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                        <i class="fas fa-chevron-right"></i> Bagaimana cara mendapatkan hasil foto setelah sesi?
                    </button>
                </h2>
                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Hasil foto digital akan tersedia dalam waktu 3-5 hari kerja setelah sesi selesai. Anda dapat mengunduhnya melalui link yang kami kirimkan ke email Anda atau melalui menu "List Pesanan" di akun Anda. Untuk foto cetak (jika termasuk dalam paket), akan dikirimkan ke alamat Anda dalam waktu 7-10 hari kerja.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 8 -->
            <div class="accordion-item faq-item" data-aos="fade-right" data-aos-delay="1600">
                <h2 class="accordion-header" id="headingEight">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                        <i class="fas fa-chevron-right"></i> Apakah foto saya akan dipublikasikan oleh studio?
                    </button>
                </h2>
                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Kami sangat menjaga privasi pelanggan. Foto Anda tidak akan dipublikasikan tanpa izin tertulis dari Anda. Jika kami ingin menggunakan foto Anda untuk promosi (misalnya di media sosial atau website), kami akan meminta persetujuan terlebih dahulu. Anda berhak menolak tanpa memengaruhi layanan kami.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 9 -->
            <div class="accordion-item faq-item" data-aos="fade-right" data-aos-delay="1800">
                <h2 class="accordion-header" id="headingNine">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                        <i class="fas fa-chevron-right"></i> Bagaimana cara menghubungi Infentori Studio untuk pertanyaan lebih lanjut?
                    </button>
                </h2>
                <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Anda dapat menghubungi kami melalui email di <a href="mailto:support@infentoristudio.com">support@infentoristudio.com</a>, telepon di <a href="tel:+6281234567890">+62 812-3456-7890</a>, atau WhatsApp di nomor yang sama. Kami juga aktif di Instagram @infentori_studio. Tim kami akan merespons dalam waktu 24 jam.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section" data-aos="zoom-in" data-aos-duration="800">
    <div class="container">
        <h3 data-aos="fade-up" data-aos-delay="200">Pertanyaan Belum Terjawab?</h3>
        <p data-aos="fade-up" data-aos-delay="400">Jangan ragu untuk menghubungi kami. Tim kami siap membantu Anda kapan saja!</p>
        <a href="mailto:support@infentoristudio.com" class="btn" data-aos="zoom-in" data-aos-delay="600">Hubungi Kami</a>
        <div class="social-links" data-aos="fade-up" data-aos-delay="800">
            <a href="https://www.instagram.com/infentori_studio?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://wa.me/6281234567890" target="_blank"><i class="fab fa-whatsapp"></i></a>
            <a href="tel:+6281234567890"><i class="fas fa-phone-alt"></i></a>
        </div>
    </div>
</section>

<!-- JS Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="js/vendor/bootstrap.min.js"></script>
<!-- AOS JS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Inisialisasi AOS
    AOS.init({
        duration: 800,
        once: true,
        easing: 'ease-out-cubic'
    });

    // Fungsi pencarian FAQ
    function searchFAQ() {
        const input = document.getElementById('faqSearch').value.toLowerCase();
        const items = document.getElementsByClassName('faq-item');

        for (let i = 0; i < items.length; i++) {
            const question = items[i].getElementsByClassName('accordion-button')[0].textContent.toLowerCase();
            const answer = items[i].getElementsByClassName('accordion-body')[0].textContent.toLowerCase();

            if (question.includes(input) || answer.includes(input)) {
                items[i].style.display = 'block';
            } else {
                items[i].style.display = 'none';
            }
        }
    }
</script>

</body>
</html>