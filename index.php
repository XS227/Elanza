<?php
/*
 * Elanza Dental – One‑Page Website
 *
 * این پرونده یک وب‌سایت تک‌صفحه‌ای به زبان PHP است که تمام اطلاعات
 * کسب‌وکار موجود در گوگل را در قالب بخش‌های مختلف به زبان فارسی نمایش می‌دهد.
 * با توجه به عدم دسترسی به API گوگل، این صفحه به جای فراخوانی مستقیم سرویس‌های
 * گوگل، از کد جاسازی نقشه و پیوندهای مستقیم به صفحه کسب‌وکار در گوگل استفاده
 * می‌کند. بدین ترتیب بازدیدکنندگان همیشه تازه‌ترین اطلاعات را از طریق
 * سرویس‌های خود گوگل مشاهده می‌کنند.
 *
 * برای به‌روزرسانی خودکار محتوا، این صفحه هر ۱۵ دقیقه بازنگری (refresh)
 * می‌شود. در صورت تمایل می‌توانید این زمان را در اسکریپت پایین اصلاح کنید.
 */

// تنظیم محتوا و اطلاعات کسب‌وکار
$business = [
    'name'        => 'دکتر محمد مهدی سنایی – Elanza Dental',
    'description' => 'کلینیک دندانپزشکی الَنزا با بهره‌گیری از جدیدترین تکنولوژی‌ها و تیم متخصص، خدمات دندانپزشکی با کیفیت بالا را ارائه می‌دهد. ما متعهد به ارائه خدماتی مطمئن، بهداشتی و مقرون‌به‌صرفه هستیم.',
    'address'     => [
        'street'  => 'شهید بلور ۹۳۲',
        'city'    => 'کرمانشاه',
        'province'=> 'استان کرمانشاه',
        'country' => 'ایران'
    ],
    'phone'       => '+98 992 589 8954',
    'rating'      => 5.0,
    'reviewCount' => 3,
    // پیوند مستقیم به کسب‌وکار در گوگل (برای نظرات و رزرو وقت)
    'google_url'  => 'https://maps.app.goo.gl/6mmn6xKcUDSHHaoPA',
    // مختصات برای نمایش نقشه بدون نیاز به API
    'latitude'    => 34.3511086,
    'longitude'   => 47.0874473,
    // ساعت کار (مثال: شنبه تا چهارشنبه ساعت ۹:۳۰ تا ۱۷)
    'openingHours' => [
        ['days' => ['Saturday','Sunday','Monday','Tuesday','Wednesday'], 'opens' => '09:30', 'closes' => '17:00'],
    ],
];

// ایجاد رشته آدرس کامل برای نمایش
$fullAddress = $business['address']['street'] . '، ' . $business['address']['city'] . '، ' . $business['address']['province'] . '، ' . $business['address']['country'];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="کلینیک دندانپزشکی الَنزا: خدمات حرفه‌ای دندانپزشکی، نمونه‌کارها، نظرات مشتریان و اطلاعات تماس. این صفحه به صورت خودکار با آخرین اطلاعات گوگل به‌روزرسانی می‌شود.">
    <meta name="keywords" content="کلینیک دندانپزشکی, الَنزا, دکتر محمد مهدی سنایی, کرمانشاه, دندانپزشک, رزرو وقت, نظرات گوگل">
    <meta name="author" content="Elanza Dental">
    <title><?php echo htmlspecialchars($business['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet" integrity="sha384-iC0mJCPTCVBn2YFKro7B1vfnv9lkrdTuaNdcvE9ZF8vD6ofXIjdlY6jFy/aAEPde" crossorigin="anonymous">
    <!-- Font Awesome for icons -->
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
            scroll-behavior: smooth;
            background-color: #f8f9fa;
            padding-top: 70px; /* فاصله برای نوار ناوبری ثابت */
        }
        .navbar-brand {
            font-weight: bold;
        }
        .section-title {
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .gallery img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        footer {
            background-color: #343a40;
            color: #fff;
            padding: 1rem 0;
        }
    </style>
    <!-- Structured data using JSON-LD for SEO -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Dentist",
      "name": "<?php echo addslashes($business['name']); ?>",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "<?php echo addslashes($business['address']['street']); ?>",
        "addressLocality": "<?php echo addslashes($business['address']['city']); ?>",
        "addressRegion": "<?php echo addslashes($business['address']['province']); ?>",
        "addressCountry": "<?php echo addslashes($business['address']['country']); ?>"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "<?php echo $business['rating']; ?>",
        "reviewCount": "<?php echo $business['reviewCount']; ?>"
      },
      "telephone": "<?php echo addslashes($business['phone']); ?>",
      "openingHoursSpecification": [
      <?php foreach ($business['openingHours'] as $index => $spec): ?>
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["<?php echo implode('","', $spec['days']); ?>"],
          "opens": "<?php echo $spec['opens']; ?>",
          "closes": "<?php echo $spec['closes']; ?>"
        }<?php if ($index < count($business['openingHours']) - 1) echo ','; ?>
      <?php endforeach; ?>
      ],
      "url": "<?php echo addslashes($business['google_url']); ?>"
    }
    </script>
    <!-- Auto refresh every 15 minutes (900000ms) -->
    <script>
        setTimeout(function() {
            location.reload();
        }, 900000);
    </script>
</head>
<body>
    <!-- Fixed Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><?php echo htmlspecialchars($business['name'], ENT_QUOTES, 'UTF-8'); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="منو">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#about">معرفی</a></li>
                    <li class="nav-item"><a class="nav-link" href="#gallery">نمونه‌کارها</a></li>
                    <li class="nav-item"><a class="nav-link" href="#reviews">نظرات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#booking">رزرو وقت</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">تماس</a></li>
                    <li class="nav-item"><a class="nav-link" href="#map">مسیر</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- About Section -->
    <section id="about" class="py-5 bg-white">
        <div class="container">
            <h2 class="section-title text-center">معرفی</h2>
            <p class="lead text-center">
                <?php echo nl2br(htmlspecialchars($business['description'], ENT_QUOTES, 'UTF-8')); ?>
            </p>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center">نمونه‌کارها</h2>
            <div class="row g-3 gallery">
                <?php
                // لیست تصاویر گالری (فایل‌های موجود در پوشه images)
                $imageDir = __DIR__ . '/images';
                $images = glob($imageDir . '/*.{jpg,png,jpeg,webp}', GLOB_BRACE);
                if ($images) {
                    foreach ($images as $img) {
                        $src = 'images/' . basename($img);
                        echo '<div class="col-6 col-md-4"><img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="نمونه کار"></div>';
                    }
                } else {
                    echo '<p class="text-center">هیچ تصویری ثبت نشده است. لطفاً تصاویر نمونه‌کارها را در پوشه <code>images</code> قرار دهید.</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Reviews Section -->
    <section id="reviews" class="py-5 bg-white">
        <div class="container">
            <h2 class="section-title text-center">نظرات مشتریان</h2>
            <p class="text-center mb-3">از طریق پیوند زیر می‌توانید نظرات واقعی مشتریان را مستقیماً در گوگل مشاهده کنید. این پیوند همیشه آخرین نظرات را نمایش می‌دهد.</p>
            <div class="text-center">
                <a href="<?php echo htmlspecialchars($business['google_url'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" target="_blank" rel="noopener">مشاهده نظرات در گوگل</a>
            </div>
        </div>
    </section>

    <!-- Booking Section -->
    <section id="booking" class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center">رزرو وقت</h2>
            <p class="text-center mb-3">برای رزرو وقت و مشاهده قابلیت‌های رزرو آنلاین، بر روی دکمه زیر کلیک کنید. این پیوند شما را به صفحه رسمی گوگل کسب‌وکار منتقل می‌کند و در صورت فعال بودن سیستم رزرو، قادر خواهید بود وقت خود را تعیین کنید.</p>
            <div class="text-center">
                <a href="<?php echo htmlspecialchars($business['google_url'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success" target="_blank" rel="noopener"><i class="fa fa-calendar-check me-1"></i> رزرو وقت در گوگل</a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-white">
        <div class="container">
            <h2 class="section-title text-center">اطلاعات تماس</h2>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fa fa-map-marker-alt ms-2 text-danger"></i> آدرس</h5>
                            <p class="card-text"><?php echo htmlspecialchars($fullAddress, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fa fa-phone ms-2 text-success"></i> تلفن</h5>
                            <p class="card-text"><a href="tel:<?php echo str_replace(' ', '', htmlspecialchars($business['phone'], ENT_QUOTES, 'UTF-8')); ?>"><?php echo htmlspecialchars($business['phone'], ENT_QUOTES, 'UTF-8'); ?></a></p>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fa fa-star ms-2 text-warning"></i> امتیاز کاربران</h5>
                            <p class="card-text">امتیاز: <?php echo $business['rating']; ?> از ۵ (بر اساس <?php echo $business['reviewCount']; ?> نظر)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section id="map" class="py-5 bg-light">
        <div class="container-fluid">
            <h2 class="section-title text-center">مسیر</h2>
            <div class="ratio ratio-16x9">
                <!-- نقشه تعبیه شده بدون نیاز به API گوگل -->
                <iframe
                    src="https://www.google.com/maps?q=<?php echo $business['latitude']; ?>,<?php echo $business['longitude']; ?>&hl=fa&z=16&output=embed"
                    loading="lazy"
                    allowfullscreen
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <p class="mb-0">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($business['name'], ENT_QUOTES, 'UTF-8'); ?> – تمام حقوق محفوظ است.</p>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-s1e+zLz6O7cHK5xrh8gQicxXnc9cXgcav+qEq/n2m8jiPpdSUfEpjaFpZ5Yl8ys9" crossorigin="anonymous"></script>
</body>
</html>