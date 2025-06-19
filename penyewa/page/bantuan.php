<?php
// Di sini Anda bisa menambahkan logika PHP jika diperlukan di masa depan
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bantuan & Kontak - Subang Outdoor</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/linearicons.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/nouislider.min.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="shortcut icon" href="../../assets/img/logo.jpg">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .section_gap { padding: 60px 0; }
        .bantuan-section h2.subtitle { font-size: 14px; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; }
        .bantuan-section h1.title { font-size: 36px; font-weight: 700; margin-bottom: 1rem; color: #222; }
        
        .contact-info-box {
            background: #fff; padding: 25px; border-radius: 8px;
            margin-bottom: 20px; transition: all 0.3s ease;
            display: flex; align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .contact-info-box:hover { box-shadow: 0 10px 30px rgba(0,0,0,0.1); transform: translateY(-5px); }
        .contact-info-box .info-icon { margin-right: 20px; }
        .contact-info-box .info-icon i { font-size: 2rem; color: #fab700; }
        .contact-info-box .info-details h5 { font-weight: 600; margin-bottom: 0.5rem; }
        .contact-info-box .info-details a { color: #6c757d; text-decoration: none; transition: color 0.3s ease; }
        .contact-info-box .info-details a:hover { color: #fab700; text-decoration: underline; }
        
        .contact-form-card {
            background: #ffffff; border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.07);
            border: none;
        }
        .contact-form .form-control { border-radius: 8px; border: 1px solid #e0e0e0; padding: 12px 15px; }
        .contact-form .form-control:focus { border-color: #fab700; box-shadow: 0 0 0 0.2rem rgba(250, 183, 0, 0.25); }
        .primary-btn {
            background-color: #fab700; border: none; color: #fff !important; padding: 12px 30px;
            border-radius: 50px; font-weight: 700; cursor: pointer;
            text-decoration: none; display: inline-block; transition: all 0.3s ease;
        }
        .primary-btn:hover { background-color: #e0a800; transform: translateY(-2px); }
        
        .map-container { border-radius: 12px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.07); }
    </style>
</head>
<body>
    <?php include('../layout/navbar1.php')?>

    <section class="banner-area organic-breadcrumb">
        <div class="container">
            <div class="breadcrumb-banner d-flex flex-wrap align-items-center justify-content-end">
                <div class="col-first">
                    <h1>Bantuan & Kontak</h1>
                    <nav class="d-flex align-items-center">
                        <a href="/subangoutdoor/index.php">Home<span class="lnr lnr-arrow-right"></span></a>
                        <a href="#">Bantuan</a>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <main class="bantuan-section container py-5">
        <div class="text-center mb-5">
            <h2 class="subtitle">BUTUH BANTUAN?</h2>
            <h1 class="title">Hubungi Kami</h1>
            <p class="col-lg-8 mx-auto text-secondary">Kami siap membantu menjawab pertanyaan Anda, memberikan informasi lebih lanjut, atau sekadar berbincang tentang petualangan Anda berikutnya.</p>
        </div>

        <div class="row">
            <div class="col-lg-5 mb-5 mb-lg-0">
                <div class="contact-info-box">
                    <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                    <div class="info-details">
                        <h5>Telepon</h5>
                        <a href="tel:+6285351315532">0853-5131-5532</a>
                    </div>
                </div>
                <div class="contact-info-box">
                    <div class="info-icon"><i class="fas fa-envelope"></i></div>
                    <div class="info-details">
                        <h5>Email</h5>
                        <a href="mailto:subangoutdoor@gmail.com">subangoutdoor@gmail.com</a>
                    </div>
                </div>
                <div class="contact-info-box">
                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="info-details">
                        <h5>Alamat</h5>
                        <a href="https://maps.google.com/?q=Jln+Babakan+Curug,+Tanjungwangi,+Cijambe" target="_blank" rel="noopener noreferrer">
                            Jln Babakan Curug, Tanjungwangi, Cijambe<br>Kota Subang, Jawa Barat
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card contact-form-card">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="mb-4 font-weight-bold">Kirim Pesan Langsung</h4>
                        <form class="contact-form" action="proses_bantuan.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3"><input type="text" class="form-control" name="nama" placeholder="Nama Lengkap Anda" required></div>
                                <div class="col-md-6 mb-3"><input type="email" class="form-control" name="email" placeholder="Alamat Email Anda" required></div>
                                <div class="col-12 mb-3"><input type="text" class="form-control" name="subjek" placeholder="Subjek Pesan" required></div>
                                <div class="col-12 mb-3"><textarea class="form-control" name="pesan" rows="5" placeholder="Tuliskan pesan Anda di sini..." required></textarea></div>
                                <div class="col-12 text-right">
                                    <button type="submit" class="primary-btn">Kirim Pesan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-5 pt-4">
            <div class="col-12">
                 <h3 class="text-center mb-4">Lokasi Kami di Peta</h3>
                 <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3767.256501799453!2d107.73463187475404!3d-6.6020729933918245!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6923fa15646aa7%3A0x12c3735fa315d694!2sSewa%20Alat%20Camping%20Subang!5e1!3m2!1sid!2sid!4v1750313143673!5m2!1sid!2sid"
                        width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                 </div>
            </div>
        </div>
    </main>

    <?php include('../layout/footer.php')?>

    <script src="js/vendor/jquery-2.2.4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="js/vendor/bootstrap.min.js"></script>
    <script src="js/jquery.ajaxchimp.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/nouislider.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>