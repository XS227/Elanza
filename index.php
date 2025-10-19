<?php
declare(strict_types=1);

mb_internal_encoding('UTF-8');

const CACHE_DIRECTORY = __DIR__ . '/storage/cache';
const CACHE_FILE = CACHE_DIRECTORY . '/google_place.json';
const MESSAGE_DIRECTORY = __DIR__ . '/storage/messages';

/**
 * Load environment variables from a simple .env file.
 */
function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, "'\"");
        if ($name === '') {
            continue;
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

loadEnv(__DIR__ . '/.env');

if (!is_dir(CACHE_DIRECTORY)) {
    mkdir(CACHE_DIRECTORY, 0775, true);
}
if (!is_dir(MESSAGE_DIRECTORY)) {
    mkdir(MESSAGE_DIRECTORY, 0775, true);
}

$businessDefaults = [
    'name' => getenv('BUSINESS_NAME') ?: 'کلینیک دندانپزشکی الَنزا',
    'description' => getenv('BUSINESS_DESCRIPTION') ?: 'کلینیک دندانپزشکی الَنزا با بهره‌گیری از تجهیزات پیشرفته و تیمی متخصص، خدمات جامع دندانپزشکی زیبایی و درمانی را در محیطی حرفه‌ای ارائه می‌دهد.',
    'phone' => getenv('BUSINESS_PHONE') ?: '+98 912 000 0000',
    'email' => getenv('BUSINESS_EMAIL') ?: 'info@example.com',
    'website' => getenv('BUSINESS_WEBSITE') ?: 'https://elanza.example.com',
    'address' => [
        'street' => getenv('BUSINESS_STREET') ?: 'تهران، خیابان مثال، پلاک ۱۰',
        'city' => getenv('BUSINESS_CITY') ?: 'تهران',
        'province' => getenv('BUSINESS_PROVINCE') ?: 'تهران',
        'postalCode' => getenv('BUSINESS_POSTAL_CODE') ?: '1234567890',
        'country' => getenv('BUSINESS_COUNTRY') ?: 'ایران',
    ],
    'coordinates' => [
        'lat' => (float) (getenv('BUSINESS_LATITUDE') ?: '35.715298'),
        'lng' => (float) (getenv('BUSINESS_LONGITUDE') ?: '51.404343'),
    ],
    'bookingUrl' => getenv('BOOKING_URL') ?: 'https://calendar.google.com',
    'mapEmbed' => getenv('CONTACT_MAP_EMBED') ?: '',
];

$placesApiKey = getenv('GOOGLE_PLACES_API_KEY') ?: '';
$placeId = getenv('GOOGLE_PLACE_ID') ?: '';
$cacheTtl = (int) (getenv('CACHE_TTL_SECONDS') ?: 43200); // 12 hours by default

$placeDetails = fetchGooglePlaceDetails($placesApiKey, $placeId, $cacheTtl);

$business = enrichBusinessData($businessDefaults, $placeDetails, $placesApiKey);

[$formStatus, $formErrors] = handleContactForm($business['email']);

$metaImage = $business['gallery']['images'][0]['url'] ?? 'https://via.placeholder.com/1200x630.png?text=Elanza+Dental';
$canonicalUrl = $business['googleUrl'] ?? $business['website'];
$structuredData = buildStructuredData($business);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($business['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?= htmlspecialchars(mb_substr($business['description'], 0, 160), ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="کلینیک دندانپزشکی, دندانپزشکی زیبایی, ایمپلنت, الَنزا, <?= htmlspecialchars($business['address']['city'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="author" content="<?= htmlspecialchars($business['name'], ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="business.business">
    <meta property="og:title" content="<?= htmlspecialchars($business['name'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?= htmlspecialchars($business['description'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?= htmlspecialchars($metaImage, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:locale" content="fa_IR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($business['name'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($business['description'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($metaImage, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
        }
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: #f4f7ff;
            scroll-behavior: smooth;
        }
        .navbar {
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
            background-color: #ffffff;
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
        }
        .navbar-brand .brand-mark {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.12), rgba(59, 130, 246, 0.08));
            padding: 7rem 0 5rem;
        }
        .hero::before,
        .hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.6;
            pointer-events: none;
        }
        .hero::before {
            width: 420px;
            height: 420px;
            background: radial-gradient(circle at center, rgba(14, 165, 233, 0.35), transparent 70%);
            top: -120px;
            inset-inline-start: -160px;
        }
        .hero::after {
            width: 360px;
            height: 360px;
            background: radial-gradient(circle at center, rgba(59, 130, 246, 0.25), transparent 70%);
            bottom: -140px;
            inset-inline-end: -120px;
        }
        .hero h1 {
            font-weight: 700;
            line-height: 1.3;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .hero p.lead {
            color: #4b5563;
        }
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .hero-actions .btn {
            padding-inline: 1.75rem;
            border-radius: 999px;
            font-weight: 600;
        }
        .hero-meta {
            margin-top: 2.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
        }
        .hero-meta-card {
            background-color: #ffffff;
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.08);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .hero-meta-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(37, 99, 235, 0.15));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-size: 1.25rem;
        }
        .hero-meta-card strong {
            font-size: 1.1rem;
        }
        .hero-illustration {
            position: relative;
            background: linear-gradient(180deg, #ffffff 10%, #e0f2fe 100%);
            border-radius: 2.5rem;
            padding: 2.5rem;
            box-shadow: 0 32px 70px rgba(15, 23, 42, 0.12);
            min-height: 420px;
            overflow: hidden;
        }
        .hero-circle {
            position: absolute;
            inset: 15% 20% 15% 20%;
            border-radius: 40%;
            background: radial-gradient(circle at top, rgba(59, 130, 246, 0.15), transparent 70%);
        }
        .hero-avatar {
            position: relative;
            width: 180px;
            height: 220px;
            border-radius: 48% 52% 45% 55%;
            background: linear-gradient(160deg, #e0f2fe, #bfdbfe);
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #1d4ed8;
            font-weight: 700;
        }
        .floating-card {
            position: absolute;
            background: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.15);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            color: #1f2937;
        }
        .floating-card i {
            color: #0ea5e9;
        }
        .floating-card.top {
            top: 28px;
            inset-inline-end: 28px;
        }
        .floating-card.bottom {
            bottom: 32px;
            inset-inline-start: 32px;
        }
        .primary-categories {
            margin-top: -3.5rem;
        }
        .category-card {
            background-color: #ffffff;
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
        }
        .category-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 50px rgba(37, 99, 235, 0.18);
        }
        .category-icon {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(37, 99, 235, 0.2));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #1d4ed8;
            margin-bottom: 1rem;
        }
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .service-card {
            border-radius: 1.25rem;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            border: 1px solid rgba(37, 99, 235, 0.08);
        }
        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 56px rgba(37, 99, 235, 0.18);
        }
        .rating-stars {
            color: #fbbf24;
        }
        .gallery-carousel .carousel-item img {
            border-radius: 1.25rem;
            object-fit: cover;
            height: 360px;
        }
        .testimonial {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            padding: 2rem;
            height: 100%;
        }
        .monogram {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.5rem;
        }
        footer {
            background-color: #0f172a;
            color: #e2e8f0;
        }
        footer a {
            color: inherit;
            text-decoration: none;
        }
        .contact-card {
            border-radius: 1rem;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }
        @media (max-width: 991.98px) {
            .hero {
                padding: 5.5rem 0 4rem;
            }
            .primary-categories {
                margin-top: -2rem;
            }
        }
        @media (max-width: 767.98px) {
            .hero-meta {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .hero-illustration {
                margin-top: 2rem;
                min-height: 360px;
            }
        }
    </style>
    <script type="application/ld+json">
        <?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#hero">
            <span class="brand-mark"><?= htmlspecialchars(getMonogramLetter($business['name']), ENT_QUOTES, 'UTF-8'); ?></span>
            <span><?= htmlspecialchars($business['name'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="باز کردن منو">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="#about">درباره ما</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">خدمات</a></li>
                <li class="nav-item"><a class="nav-link" href="#gallery">نمونه‌کارها</a></li>
                <li class="nav-item"><a class="nav-link" href="#reviews">نظرات</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">تماس</a></li>
                <li class="nav-item"><a class="nav-link" href="#map">مسیر</a></li>
            </ul>
            <a class="btn btn-primary rounded-pill ms-lg-3" href="<?= htmlspecialchars($business['bookingUrl'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">رزرو آنلاین</a>
        </div>
    </div>
</nav>

<header id="hero" class="hero text-dark">
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <span class="hero-badge"><i class="fa-solid fa-tooth ms-2"></i>کلینیک تخصصی خانواده</span>
                <h1 class="display-5 mb-3">تحول لبخند با دقت و آرامش در کلینیک الَنزا</h1>
                <p class="lead mb-4">از معاینه‌های دوره‌ای تا درمان‌های زیبایی پیشرفته، تیم متخصص الَنزا در کنار خانوادهٔ شماست تا لبخندی سالم و درخشان بسازید.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-lg" href="<?= htmlspecialchars($business['bookingUrl'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                        <i class="fa-solid fa-calendar-check ms-2"></i> رزرو وقت آنلاین
                    </a>
                    <a class="btn btn-outline-primary btn-lg" href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $business['phone']), ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-solid fa-phone ms-2"></i> مشاوره فوری
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-illustration">
                    <div class="hero-circle" aria-hidden="true"></div>
                    <div class="hero-avatar" aria-hidden="true">
                        <?= htmlspecialchars(getMonogramLetter($business['name']), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="floating-card top">
                        <i class="fa-solid fa-shield-heart"></i>
                        <span>ضمانت کیفیت درمان</span>
                    </div>
                    <div class="floating-card bottom">
                        <i class="fa-solid fa-user-doctor"></i>
                        <span>تیم دندانپزشکان مجرب</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<?php $highlightServices = getHighlightServices($business['services']); ?>
<section id="highlights" class="primary-categories">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($highlightServices as $service): ?>
                <?php
                    $description = (string) ($service['description'] ?? '');
                    $trimmedDescription = mb_substr($description, 0, 90);
                ?>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="category-card h-100">
                        <span class="category-icon"><i class="<?= htmlspecialchars($service['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                        <h3 class="h6 fw-bold mb-2"><?= htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="text-muted small mb-0"><?= htmlspecialchars($trimmedDescription, ENT_QUOTES, 'UTF-8'); ?><?= mb_strlen($description) > 90 ? '…' : ''; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="about" class="py-5 bg-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="section-title">چرا الَنزا؟</h2>
                <p class="mb-3">ما در کلینیک دندانپزشکی الَنزا با تکیه بر تیمی مجرب از دندانپزشکان متخصص، به ارائهٔ درمان‌های دقیق و مطابق با استانداردهای روز دنیا متعهد هستیم. استفاده از تجهیزات مدرن، رعایت بهداشت حرفه‌ای و توجه ویژه به تجربهٔ بیماران، ما را به انتخابی مطمئن برای خانواده‌ها تبدیل کرده است.</p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fa-solid fa-check text-success ms-2"></i>استفاده از مواد اولیهٔ معتبر و تجهیزات به‌روز</li>
                    <li class="mb-2"><i class="fa-solid fa-check text-success ms-2"></i>ارائهٔ طرح درمان اختصاصی برای هر بیمار</li>
                    <li class="mb-2"><i class="fa-solid fa-check text-success ms-2"></i>محیطی آرام، بهداشتی و مناسب خانواده‌ها</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="ratio ratio-16x9 rounded-4 shadow overflow-hidden">
                    <iframe src="https://www.youtube-nocookie.com/embed/ck4cWHu3Xl0" title="Tour" allowfullscreen loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="services" class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title text-center">خدمات تخصصی ما</h2>
        <div class="row g-4">
            <?php foreach ($business['services'] as $service): ?>
                <div class="col-md-4">
                    <div class="service-card p-4 bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-primary-subtle text-primary-emphasis ms-2"><i class="<?= htmlspecialchars($service['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                            <h3 class="h5 mb-0"><?= htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                        <p class="text-muted mb-0"><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="gallery" class="py-5 bg-white">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
            <h2 class="section-title mb-0">نمونه‌کارها و فضای کلینیک</h2>
            <span class="text-muted">برای مشاهدهٔ کامل تصاویر، به گالری گوگل سر بزنید.</span>
        </div>
        <?php if (!empty($business['gallery']['images'])): ?>
            <div id="clinicGallery" class="carousel slide gallery-carousel" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php foreach ($business['gallery']['images'] as $index => $image): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : ''; ?>">
                            <img src="<?= htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8'); ?>" class="d-block w-100" alt="<?= htmlspecialchars($image['alt'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($business['gallery']['images']) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#clinicGallery" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">قبلی</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#clinicGallery" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">بعدی</span>
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="status">برای نمایش گالری، تصاویر خود را در پوشهٔ <code>images</code> بارگذاری کنید.</div>
        <?php endif; ?>
    </div>
</section>

<section id="reviews" class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title text-center">نظرات مشتریان</h2>
        <p class="text-center text-muted mb-5">بخشی از تجربهٔ مراجعان ما را بخوانید یا نظرات کامل را در گوگل دنبال کنید.</p>
        <div class="row g-4">
            <?php foreach ($business['reviews'] as $review): ?>
                <div class="col-md-4">
                    <div class="testimonial h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="monogram" aria-hidden="true"><?= htmlspecialchars(getMonogramLetter($review['author']), ENT_QUOTES, 'UTF-8'); ?></span>
                                <div>
                                    <h3 class="h6 mb-1"><?= htmlspecialchars($review['author'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <span class="text-muted small"><i class="fa-regular fa-clock ms-1"></i><?= htmlspecialchars($review['relativeTime'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                            <span class="rating-stars"><?= renderStars($review['rating']); ?></span>
                        </div>
                        <p class="mb-0"><?= htmlspecialchars($review['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a class="btn btn-outline-primary rounded-pill" href="<?= htmlspecialchars($business['googleUrl'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                مشاهدهٔ همهٔ نظرات در گوگل
            </a>
        </div>
    </div>
</section>

<section id="contact" class="py-5 bg-white">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-6">
                <h2 class="section-title">اطلاعات تماس و ساعات کاری</h2>
                <div class="contact-card p-4 bg-white mb-4">
                    <h3 class="h5 mb-3"><i class="fa-solid fa-clock ms-2 text-primary"></i>ساعات کاری</h3>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($business['openingHours'] as $hours): ?>
                            <li class="mb-2 d-flex justify-content-between border-bottom pb-2">
                                <span><?= htmlspecialchars($hours['day'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span><?= htmlspecialchars($hours['hours'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="contact-card p-4 bg-white">
                    <h3 class="h5 mb-3"><i class="fa-solid fa-info-circle ms-2 text-primary"></i>راه‌های ارتباطی</h3>
                    <p class="mb-2"><i class="fa-solid fa-location-dot text-danger ms-2"></i><?= htmlspecialchars(formatAddress($business['address']), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="mb-2"><i class="fa-solid fa-phone text-success ms-2"></i><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $business['phone']), ENT_QUOTES, 'UTF-8'); ?>" class="link-dark text-decoration-none"><?= htmlspecialchars($business['phone'], ENT_QUOTES, 'UTF-8'); ?></a></p>
                    <p class="mb-2"><i class="fa-solid fa-envelope text-warning ms-2"></i><a href="mailto:<?= htmlspecialchars($business['email'], ENT_QUOTES, 'UTF-8'); ?>" class="link-dark text-decoration-none"><?= htmlspecialchars($business['email'], ENT_QUOTES, 'UTF-8'); ?></a></p>
                    <p class="mb-0"><i class="fa-solid fa-earth-americas text-info ms-2"></i><a href="<?= htmlspecialchars($business['website'], ENT_QUOTES, 'UTF-8'); ?>" class="link-dark text-decoration-none" target="_blank" rel="noopener">وب‌سایت</a></p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="contact-card p-4 bg-white">
                    <h2 class="h5 mb-3">فرم تماس مستقیم</h2>
                    <?php if ($formStatus === 'success'): ?>
                        <div class="alert alert-success" role="alert">پیام شما با موفقیت ارسال شد. از تماس شما سپاسگزاریم.</div>
                    <?php elseif ($formStatus === 'stored'): ?>
                        <div class="alert alert-warning" role="alert">پیام شما ثبت شد اما ارسال ایمیل امکان‌پذیر نبود. لطفاً بعداً دوباره تلاش کنید.</div>
                    <?php endif; ?>
                    <?php if (!empty($formErrors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($formErrors as $error): ?>
                                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="contact_form" value="1">
                        <div class="mb-3">
                            <label for="name" class="form-label">نام و نام خانوادگی</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">ایمیل</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">شماره تماس</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">پیام شما</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill w-100">ارسال پیام</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="map" class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title text-center">مسیر دسترسی</h2>
        <div class="ratio ratio-16x9 shadow rounded-4 overflow-hidden">
            <?php if (!empty($business['mapEmbed'])): ?>
                <iframe src="<?= htmlspecialchars($business['mapEmbed'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            <?php else: ?>
                <iframe src="https://www.google.com/maps?q=<?= htmlspecialchars((string) $business['coordinates']['lat'], ENT_QUOTES, 'UTF-8'); ?>,<?= htmlspecialchars((string) $business['coordinates']['lng'], ENT_QUOTES, 'UTF-8'); ?>&hl=fa&z=16&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            <?php endif; ?>
        </div>
    </div>
</section>

<footer class="py-4">
    <div class="container text-center">
        <div class="mb-2">
            <a href="<?= htmlspecialchars($business['googleUrl'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-pill"><i class="fa-brands fa-google ms-1"></i>صفحهٔ گوگل</a>
            <a href="<?= htmlspecialchars($business['bookingUrl'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm rounded-pill ms-2">رزرو آنلاین</a>
        </div>
        <p class="mb-0">© <?= date('Y'); ?> <?= htmlspecialchars($business['name'], ENT_QUOTES, 'UTF-8'); ?> - تمامی حقوق محفوظ است.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
<?php
function fetchGooglePlaceDetails(string $apiKey, string $placeId, int $cacheTtl): ?array
{
    if ($apiKey === '' || $placeId === '') {
        return readCache();
    }

    $now = time();
    $cached = readCache();
    $cacheFresh = $cached !== null && is_file(CACHE_FILE) && ($now - filemtime(CACHE_FILE)) < $cacheTtl;
    if ($cacheFresh) {
        return $cached;
    }

    $endpoint = 'https://maps.googleapis.com/maps/api/place/details/json';
    $fields = implode(',', [
        'name',
        'rating',
        'user_ratings_total',
        'reviews',
        'photos',
        'formatted_address',
        'international_phone_number',
        'website',
        'opening_hours',
        'geometry',
        'url',
    ]);
    $query = http_build_query([
        'place_id' => $placeId,
        'key' => $apiKey,
        'language' => 'fa',
        'fields' => $fields,
    ]);
    $url = $endpoint . '?' . $query;

    $response = httpGet($url);
    if ($response === null) {
        return $cached;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return $cached;
    }

    if (($decoded['status'] ?? '') !== 'OK') {
        return $cached;
    }

    $result = $decoded['result'] ?? null;
    if ($result === null) {
        return $cached;
    }

    file_put_contents(CACHE_FILE, json_encode($result, JSON_UNESCAPED_UNICODE));

    return $result;
}

function httpGet(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        return $error === '' ? ($data ?: null) : null;
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
        ],
    ]);
    $data = @file_get_contents($url, false, $context);

    return $data === false ? null : $data;
}

function readCache(): ?array
{
    if (!is_readable(CACHE_FILE)) {
        return null;
    }

    $contents = file_get_contents(CACHE_FILE);
    if ($contents === false) {
        return null;
    }

    $data = json_decode($contents, true);

    return is_array($data) ? $data : null;
}

function enrichBusinessData(array $defaults, ?array $placeDetails, string $apiKey): array
{
    $result = $defaults;
    if ($placeDetails) {
        $result['name'] = $placeDetails['name'] ?? $result['name'];
        $result['phone'] = $placeDetails['international_phone_number'] ?? $result['phone'];
        $result['website'] = $placeDetails['website'] ?? $result['website'];
        $result['googleUrl'] = $placeDetails['url'] ?? buildGoogleUrl($placeDetails['name'] ?? $result['name']);
        if (!empty($placeDetails['geometry']['location'])) {
            $result['coordinates']['lat'] = (float) ($placeDetails['geometry']['location']['lat'] ?? $result['coordinates']['lat']);
            $result['coordinates']['lng'] = (float) ($placeDetails['geometry']['location']['lng'] ?? $result['coordinates']['lng']);
        }
        if (!empty($placeDetails['formatted_address'])) {
            $result['address']['formatted'] = $placeDetails['formatted_address'];
        }
    } else {
        $result['googleUrl'] = buildGoogleUrl($result['name']);
    }

    $result['rating'] = $placeDetails['rating'] ?? 4.9;
    $result['reviewCount'] = $placeDetails['user_ratings_total'] ?? 0;
    $result['openingHours'] = normalizeOpeningHours($placeDetails['opening_hours']['weekday_text'] ?? []);
    $result['services'] = getDefaultServices();
    $result['gallery'] = [
        'images' => buildGalleryImages($placeDetails['photos'] ?? [], $apiKey),
    ];
    if (empty($result['gallery']['images'])) {
        $result['gallery']['images'] = localGalleryImages();
    }
    $result['reviews'] = normalizeReviews($placeDetails['reviews'] ?? []);
    if (empty($result['reviews'])) {
        $result['reviews'] = fallbackReviews();
    }

    return $result;
}

function buildGoogleUrl(string $name): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($name);
}

function normalizeOpeningHours(array $weekdayText): array
{
    if (!empty($weekdayText)) {
        return array_map(function ($line) {
            $parts = explode(':', $line, 2);
            return [
                'day' => trim($parts[0] ?? ''),
                'hours' => trim($parts[1] ?? ''),
            ];
        }, $weekdayText);
    }

    return [
        ['day' => 'شنبه تا چهارشنبه', 'hours' => '۰۹:۳۰ تا ۱۸:۰۰'],
        ['day' => 'پنجشنبه', 'hours' => '۰۹:۳۰ تا ۱۳:۰۰'],
        ['day' => 'جمعه', 'hours' => 'تعطیل'],
    ];
}

function getDefaultServices(): array
{
    return [
        [
            'title' => 'ایمپلنت و کاشت دندان',
            'description' => 'بازگرداندن عملکرد و زیبایی لبخند با استفاده از ایمپلنت‌های پیشرفته و برنامه درمان اختصاصی.',
            'icon' => 'fa-solid fa-tooth',
        ],
        [
            'title' => 'ارتودنسی نامرئی',
            'description' => 'تراز کردن دندان‌ها با الاینرهای شفاف و درمان‌های کم‌درد مناسب بزرگسالان و نوجوانان.',
            'icon' => 'fa-solid fa-teeth-open',
        ],
        [
            'title' => 'سفید کردن و زیبایی دندان',
            'description' => 'استفاده از تکنیک‌های نوین سفید کردن و لمینیت برای داشتن لبخندی درخشان و طبیعی.',
            'icon' => 'fa-solid fa-sparkles',
        ],
    ];
}

function getHighlightServices(array $services): array
{
    $items = array_values(array_slice($services, 0, 4));
    if (count($items) < 4) {
        foreach (getDefaultServices() as $fallback) {
            $items[] = $fallback;
            if (count($items) === 4) {
                break;
            }
        }
    }

    return array_slice($items, 0, 4);
}

function buildGalleryImages(array $photos, string $apiKey): array
{
    if (empty($photos) || $apiKey === '') {
        return [];
    }

    $images = [];
    foreach (array_slice($photos, 0, 6) as $photo) {
        if (empty($photo['photo_reference'])) {
            continue;
        }
        $photoUrl = sprintf(
            'https://maps.googleapis.com/maps/api/place/photo?maxwidth=1600&photoreference=%s&key=%s',
            urlencode($photo['photo_reference']),
            urlencode($apiKey)
        );
        $images[] = [
            'url' => $photoUrl,
            'alt' => 'تصویر از کلینیک الَنزا',
        ];
    }

    return $images;
}

function localGalleryImages(): array
{
    $directory = __DIR__ . '/images';
    $files = glob($directory . '/*.{jpg,jpeg,png,webp,avif}', GLOB_BRACE);
    if ($files === false) {
        return [];
    }

    $images = [];
    foreach ($files as $file) {
        $images[] = [
            'url' => 'images/' . basename($file),
            'alt' => 'نمونه‌کار کلینیک الَنزا',
        ];
    }

    return $images;
}

function normalizeReviews(array $reviews): array
{
    $normalized = [];
    foreach (array_slice($reviews, 0, 3) as $review) {
        $normalized[] = [
            'author' => $review['author_name'] ?? 'مراجع گوگل',
            'rating' => (float) ($review['rating'] ?? 5),
            'text' => $review['text'] ?? 'تجربه‌ای عالی و حرفه‌ای.',
            'relativeTime' => $review['relative_time_description'] ?? 'به‌تازگی',
        ];
    }

    return $normalized;
}

function fallbackReviews(): array
{
    return [
        [
            'author' => 'سارا احمدی',
            'rating' => 5,
            'text' => 'محیط بسیار آرام و استریل بود و تیم درمانی با دقت تمام مراحل درمان را توضیح دادند.',
            'relativeTime' => '۱ ماه پیش',
        ],
        [
            'author' => 'محمدرضا کاظمی',
            'rating' => 5,
            'text' => 'از نتیجه ارتودنسی نامرئی بسیار راضی هستم. برنامه درمانی شخصی‌سازی شده بود.',
            'relativeTime' => '۲ ماه پیش',
        ],
        [
            'author' => 'نگار خوشرو',
            'rating' => 4.5,
            'text' => 'پرسنل بسیار خوش‌برخورد بودند و روند سفید کردن دندان بدون درد انجام شد.',
            'relativeTime' => '۳ ماه پیش',
        ],
    ];
}

function renderStars(float $rating): string
{
    $fullStars = (int) floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    $icons = str_repeat('<i class="fa-solid fa-star"></i>', $fullStars);
    if ($halfStar) {
        $icons .= '<i class="fa-solid fa-star-half-stroke"></i>';
    }
    $icons .= str_repeat('<i class="fa-regular fa-star"></i>', $emptyStars);
    return $icons;
}

function formatAddress(array $address): string
{
    if (!empty($address['formatted'])) {
        return $address['formatted'];
    }

    $parts = array_filter([
        $address['street'] ?? '',
        $address['city'] ?? '',
        $address['province'] ?? '',
        $address['postalCode'] ?? '',
        $address['country'] ?? '',
    ]);

    return implode('، ', $parts);
}

function getMonogramLetter(string $text): string
{
    $trimmed = trim($text);
    if ($trimmed === '') {
        return '؟';
    }

    $letter = mb_substr($trimmed, 0, 1);
    return $letter !== '' ? $letter : '؟';
}

function handleContactForm(string $fallbackEmail): array
{
    $status = null;
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['contact_form'])) {
        return [null, $errors];
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') {
        $errors[] = 'لطفاً نام خود را وارد کنید.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'آدرس ایمیل معتبر نیست.';
    }
    if ($message === '') {
        $errors[] = 'متن پیام نمی‌تواند خالی باشد.';
    }

    if (!empty($errors)) {
        return [null, $errors];
    }

    $mailTo = getenv('MAIL_TO') ?: $fallbackEmail;
    $subjectPrefix = getenv('MAIL_SUBJECT_PREFIX') ?: 'پیام جدید از وب‌سایت الَنزا';
    $subject = $subjectPrefix . ' - ' . $name;
    $bodyLines = [
        'نام: ' . $name,
        'ایمیل: ' . $email,
        'تلفن: ' . ($phone !== '' ? $phone : '---'),
        'پیام:',
        $message,
    ];
    $body = implode("\n", $bodyLines);
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . ($mailTo ?: 'no-reply@example.com'),
    ];
    if ($email !== '') {
        $headers[] = 'Reply-To: ' . $email;
    }
    $mailSent = false;
    if ($mailTo && filter_var($mailTo, FILTER_VALIDATE_EMAIL)) {
        $mailSent = @mail($mailTo, $subject, $body, implode("\r\n", $headers));
    }

    if ($mailSent) {
        $_POST = [];
        return ['success', $errors];
    }

    $filename = MESSAGE_DIRECTORY . '/contact-' . date('Ymd-His') . '-' . generateRandomToken() . '.txt';
    file_put_contents($filename, $body);
    $_POST = [];

    return ['stored', $errors];
}

function generateRandomToken(int $bytes = 4): string
{
    try {
        return bin2hex(random_bytes($bytes));
    } catch (Throwable $e) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $fallback = openssl_random_pseudo_bytes($bytes);
            if ($fallback !== false) {
                return bin2hex($fallback);
            }
        }
    }

    $hash = hash('sha256', (string) microtime(true), true);

    return substr(bin2hex($hash), 0, $bytes * 2);
}

function buildStructuredData(array $business): array
{
    $openingSpecs = [];
    foreach ($business['openingHours'] as $item) {
        $hours = $item['hours'] ?? '';
        if (function_exists('mb_stripos')) {
            $isClosed = mb_stripos($hours, 'تعطیل') !== false;
        } else {
            $isClosed = stripos($hours, 'تعطیل') !== false;
        }
        if ($isClosed || $hours === '') {
            continue;
        }
        $segments = explode('تا', $hours);
        $opens = trim($segments[0] ?? '08:00');
        $closes = trim($segments[1] ?? '18:00');
        $openingSpecs[] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => [$item['day']],
            'opens' => $opens,
            'closes' => $closes,
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'Dentist',
        'name' => $business['name'],
        'description' => $business['description'],
        'image' => array_column($business['gallery']['images'], 'url'),
        'url' => $business['website'],
        'telephone' => $business['phone'],
        'email' => $business['email'],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $business['address']['street'],
            'addressLocality' => $business['address']['city'],
            'addressRegion' => $business['address']['province'],
            'postalCode' => $business['address']['postalCode'],
            'addressCountry' => $business['address']['country'],
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $business['coordinates']['lat'],
            'longitude' => $business['coordinates']['lng'],
        ],
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => $business['rating'],
            'reviewCount' => $business['reviewCount'],
        ],
        'openingHoursSpecification' => $openingSpecs,
        'sameAs' => [$business['googleUrl']],
    ];
}
