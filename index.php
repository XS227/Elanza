<?php
declare(strict_types=1);

mb_internal_encoding('UTF-8');

const CACHE_DIRECTORY = __DIR__ . '/storage/cache';
const CACHE_FILE = CACHE_DIRECTORY . '/google_place.json';

function getBusinessGoogleUrl(): string
{
    $configured = getenv('BUSINESS_GOOGLE_URL');

    return $configured !== false && $configured !== ''
        ? $configured
        : 'https://maps.app.goo.gl/cbgjZXmMjpR8kgZT8';
}

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

$businessDefaults = [
    'name' => getenv('BUSINESS_NAME') ?: 'کلینیک دندانپزشکی الَنزا',
    'description' => getenv('BUSINESS_DESCRIPTION') ?: 'کلینیک دندانپزشکی الَنزا با بهره‌گیری از تجهیزات پیشرفته و تیمی متخصص، خدمات جامع دندانپزشکی زیبایی و درمانی را در محیطی حرفه‌ای ارائه می‌دهد.',
    'phone' => getenv('BUSINESS_PHONE') ?: '+989925898954',
    'email' => getenv('BUSINESS_EMAIL') ?: '',
    'website' => getenv('BUSINESS_WEBSITE') ?: 'https://elanza.example.com',
    'googleUrl' => getBusinessGoogleUrl(),
    'address' => [
        'street' => getenv('BUSINESS_STREET') ?: 'بلوار شهید شکری، 932P+CXW',
        'city' => getenv('BUSINESS_CITY') ?: 'کرمانشاه',
        'province' => getenv('BUSINESS_PROVINCE') ?: 'استان کرمانشاه',
        'postalCode' => getenv('BUSINESS_POSTAL_CODE') ?: '',
        'country' => getenv('BUSINESS_COUNTRY') ?: 'ایران',
    ],
    'coordinates' => [
        'lat' => (float) (getenv('BUSINESS_LATITUDE') ?: '35.715298'),
        'lng' => (float) (getenv('BUSINESS_LONGITUDE') ?: '51.404343'),
    ],
    'mapEmbed' => getenv('CONTACT_MAP_EMBED') ?: '',
];

$placesApiKey = getenv('GOOGLE_PLACES_API_KEY') ?: '';
$placeId = getenv('GOOGLE_PLACE_ID') ?: '';
$cacheTtl = (int) (getenv('CACHE_TTL_SECONDS') ?: 43200); // 12 hours by default

$placeDetails = fetchGooglePlaceDetails($placesApiKey, $placeId, $cacheTtl);

$business = enrichBusinessData($businessDefaults, $placeDetails, $placesApiKey);
$business['map'] = buildMapInfo($business, $placeId);
$callLink = buildTelLink($business['phone'] ?? '');

$primaryGalleryImage = firstGalleryImageUrl($business['gallery']['images'] ?? []);
$metaImage = $primaryGalleryImage ?? 'https://via.placeholder.com/1200x630.png?text=Elanza+Dental';
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1 0 auto;
        }
        body.dark-theme {
            background-color: #0f172a;
            color: #e2e8f0;
            color-scheme: dark;
        }
        .navbar {
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
            background-color: #ffffff;
        }
        body.dark-theme .navbar {
            background-color: #111827 !important;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.3);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
        }
        body.dark-theme .navbar-brand {
            color: #f8fafc;
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
        .navbar .nav-link {
            transition: color 0.2s ease;
        }
        body.dark-theme .navbar .nav-link {
            color: #e2e8f0;
        }
        body.dark-theme .navbar .nav-link:hover,
        body.dark-theme .navbar .nav-link:focus {
            color: #38bdf8;
        }
        .hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.12), rgba(59, 130, 246, 0.08));
            padding: 7rem 0 5rem;
        }
        body.dark-theme .hero {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.35), rgba(8, 145, 178, 0.15));
            color: #f8fafc;
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
        body.dark-theme .hero-badge {
            background: rgba(125, 211, 252, 0.18);
            color: #bae6fd;
        }
        .hero p.lead {
            color: #4b5563;
        }
        body.dark-theme .hero p.lead {
            color: #cbd5f5;
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
        body.dark-theme .hero-meta-card {
            background-color: #111827;
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.5);
            color: #e2e8f0;
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
        body.dark-theme .hero-meta-icon {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.18), rgba(14, 165, 233, 0.12));
            color: #38bdf8;
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
        body.dark-theme .hero-illustration {
            background: linear-gradient(180deg, #0f172a 10%, #1e293b 100%);
            box-shadow: 0 32px 70px rgba(15, 23, 42, 0.6);
        }
        .hero-circle {
            position: absolute;
            inset: 15% 20% 15% 20%;
            border-radius: 40%;
            background: radial-gradient(circle at top, rgba(59, 130, 246, 0.15), transparent 70%);
        }
        body.dark-theme .hero-circle {
            background: radial-gradient(circle at top, rgba(56, 189, 248, 0.2), transparent 70%);
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
        body.dark-theme .floating-card {
            background: #111827;
            color: #f1f5f9;
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.5);
        }
        .floating-card i {
            color: #0ea5e9;
        }
        body.dark-theme .floating-card i {
            color: #38bdf8;
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
        body.dark-theme .category-card {
            background-color: #111827;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.5);
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
        body.dark-theme .category-icon {
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.2), rgba(14, 165, 233, 0.18));
            color: #38bdf8;
        }
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        body.dark-theme .section-title {
            color: #f8fafc;
        }
        .service-card {
            border-radius: 1.25rem;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            border: 1px solid rgba(37, 99, 235, 0.08);
        }
        body.dark-theme .service-card {
            background-color: #111827;
            border-color: rgba(56, 189, 248, 0.15);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.5);
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
        .gallery-carousel .carousel-item .gallery-streetview-wrapper {
            border-radius: 1.25rem;
            overflow: hidden;
        }
        .gallery-carousel .carousel-item .gallery-streetview-wrapper iframe {
            border: 0;
            border-radius: 1.25rem;
        }
        .testimonial {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            padding: 2rem;
            height: 100%;
        }
        body.dark-theme .testimonial {
            background-color: #111827;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.5);
            color: #e2e8f0;
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
        body.dark-theme .monogram {
            background: linear-gradient(135deg, #1e3a8a, #0284c7);
        }
        body.dark-theme .bg-white {
            background-color: #111827 !important;
        }
        body.dark-theme .bg-light {
            background-color: #0b1220 !important;
        }
        body.dark-theme .text-dark {
            color: #e2e8f0 !important;
        }
        body.dark-theme .btn-primary {
            color: #0f172a;
        }
        body.dark-theme .btn-outline-secondary {
            color: #e2e8f0;
            border-color: rgba(226, 232, 240, 0.4);
        }
        body.dark-theme .btn-outline-secondary:hover {
            background-color: rgba(226, 232, 240, 0.1);
        }
        .btn-toggle-icon {
            transition: transform 0.2s ease;
        }
        #theme-toggle[aria-pressed="true"] .btn-toggle-icon {
            transform: rotate(-20deg);
        }
        footer {
            background-color: #0f172a;
            color: #e2e8f0;
            margin-top: auto;
        }
        footer a {
            color: inherit;
            text-decoration: none;
        }
        .contact-card {
            border-radius: 1rem;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }
        body.dark-theme .contact-card {
            background-color: #0f172a !important;
            color: #e2e8f0;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.4);
        }
        body.dark-theme .contact-card .text-muted {
            color: rgba(203, 213, 225, 0.75) !important;
        }
        body.dark-theme .contact-card a {
            color: #bae6fd !important;
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
        @media (max-width: 575.98px) {
            .hero {
                text-align: center;
                padding: 4.5rem 0 3.5rem;
            }
            .hero h1 {
                font-size: clamp(1.9rem, 6vw + 1rem, 2.5rem);
            }
            .hero p.lead {
                font-size: 1.05rem;
            }
            .hero-actions {
                justify-content: center;
            }
            .hero-meta {
                grid-template-columns: 1fr;
            }
            .hero-illustration {
                margin-top: 2.5rem;
                margin-inline: auto;
                max-width: 320px;
                padding: 2rem;
            }
            .floating-card {
                position: relative;
                inset: auto;
                margin: 0.75rem auto 0;
                width: min(100%, 280px);
                justify-content: center;
            }
            .floating-card.top,
            .floating-card.bottom {
                top: auto;
                bottom: auto;
                inset-inline-start: auto;
                inset-inline-end: auto;
            }
            .floating-card.top {
                margin-top: 0;
            }
            .primary-categories {
                margin-top: -1.5rem;
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
            </ul>
            <a class="btn btn-primary rounded-pill ms-lg-3" href="<?= htmlspecialchars($callLink, ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fa-solid fa-phone-volume ms-2"></i>
                تماس تلفنی
            </a>
        </div>
    </div>
</nav>

<main>
<header id="hero" class="hero text-dark">
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <span class="hero-badge"><i class="fa-solid fa-tooth ms-2"></i>کلینیک تخصصی خانواده</span>
                <h1 class="display-5 mb-3">تحول لبخند با دقت و آرامش در کلینیک الَنزا</h1>
                <p class="lead mb-4">از معاینه‌های دوره‌ای تا درمان‌های زیبایی پیشرفته، تیم متخصص الَنزا در کنار خانوادهٔ شماست تا لبخندی سالم و درخشان بسازید.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary btn-lg" href="<?= htmlspecialchars($callLink, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-solid fa-phone-volume ms-2"></i> تماس فوری با کلینیک
                    </a>
                    <a class="btn btn-outline-primary btn-lg" href="#contact">
                        <i class="fa-solid fa-circle-info ms-2"></i> مشاهدهٔ اطلاعات تماس
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
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="category-card h-100">
                        <span class="category-icon"><i class="<?= htmlspecialchars($service['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                        <h3 class="h6 fw-bold mb-2"><?= htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="text-muted small mb-3"><?= htmlspecialchars((string) ($service['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if (!empty($service['items']) && is_array($service['items'])): ?>
                            <ul class="list-unstyled text-muted small mb-0">
                                <?php foreach ($service['items'] as $item): ?>
                                    <li class="d-flex align-items-start mb-1">
                                        <i class="fa-solid fa-circle-dot text-primary ms-2 mt-1"></i>
                                        <span><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
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
                    <iframe src="https://www.youtube-nocookie.com/embed/URaaGnH5pq0" title="Tour" allowfullscreen loading="lazy"></iframe>
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
                        <p class="text-muted mb-3"><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if (!empty($service['items']) && is_array($service['items'])): ?>
                            <ul class="list-unstyled text-muted small mb-0">
                                <?php foreach ($service['items'] as $item): ?>
                                    <li class="d-flex align-items-start mb-1">
                                        <i class="fa-solid fa-circle-check text-success ms-2 mt-1"></i>
                                        <span><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
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
            <span class="text-muted">برای مشاهدهٔ کامل تصاویر، به <a href="<?= htmlspecialchars($business['googleUrl'], ENT_QUOTES, 'UTF-8'); ?>" class="link-primary text-decoration-none" target="_blank" rel="noopener">گالری گوگل</a> سر بزنید.</span>
        </div>
        <?php if (!empty($business['gallery']['images'])): ?>
            <div id="clinicGallery" class="carousel slide gallery-carousel" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php foreach ($business['gallery']['images'] as $index => $image): ?>
                        <?php $itemType = $image['type'] ?? 'image'; ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : ''; ?>">
                            <?php if ($itemType === 'streetview' && !empty($image['embedUrl'])): ?>
                                <div class="ratio ratio-16x9 gallery-streetview-wrapper">
                                    <iframe src="<?= htmlspecialchars($image['embedUrl'], ENT_QUOTES, 'UTF-8'); ?>" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?= htmlspecialchars($image['alt'] ?? 'نمای Street View', ENT_QUOTES, 'UTF-8'); ?>"></iframe>
                                </div>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($image['url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="d-block w-100" alt="<?= htmlspecialchars($image['alt'] ?? 'تصویر کلینیک', ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                            <?php endif; ?>
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
        <div class="row g-4 align-items-stretch mb-4">
            <div class="col-lg-8 col-xl-6">
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
                    <?php if (!empty($business['phone'])): ?>
                        <p class="mb-2 d-flex align-items-center gap-2 flex-wrap">
                            <i class="fa-solid fa-phone text-success ms-2"></i>
                            <a href="<?= htmlspecialchars($callLink, ENT_QUOTES, 'UTF-8'); ?>" class="link-dark text-decoration-none">
                                <?= htmlspecialchars(formatPhoneDisplay($business['phone']), ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($business['email'])): ?>
                        <p class="mb-2 d-flex align-items-center gap-2 flex-wrap">
                            <i class="fa-solid fa-envelope text-primary ms-2"></i>
                            <a href="<?= htmlspecialchars(buildMailtoLink($business['email']), ENT_QUOTES, 'UTF-8'); ?>" class="link-dark text-decoration-none">
                                <?= htmlspecialchars($business['email'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($business['website'])): ?>
                        <?php $websiteUrl = ensureAbsoluteUrl($business['website']); ?>
                        <?php if ($websiteUrl !== ''): ?>
                            <p class="mb-2 d-flex align-items-center gap-2 flex-wrap">
                                <i class="fa-solid fa-globe text-info ms-2"></i>
                                <a href="<?= htmlspecialchars($websiteUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="link-dark text-decoration-none">
                                    <?= htmlspecialchars(formatWebsiteLabel($websiteUrl), ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php $formattedAddress = formatAddress($business['address']); ?>
                    <?php if ($formattedAddress !== ''): ?>
                        <p class="mb-0 d-flex align-items-start gap-2">
                            <i class="fa-solid fa-location-dot text-danger ms-2 mt-1"></i>
                            <span><?= htmlspecialchars($formattedAddress, ENT_QUOTES, 'UTF-8'); ?></span>
                        </p>
                    <?php endif; ?>
                    <?php $hasQuickLinks = ($callLink !== '#') || !empty($business['googleUrl']); ?>
                    <?php if ($hasQuickLinks): ?>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <?php if ($callLink !== '#'): ?>
                                <a class="btn btn-primary btn-sm rounded-pill" href="<?= htmlspecialchars($callLink, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa-solid fa-phone-volume ms-1"></i>تماس سریع
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($business['googleUrl'])): ?>
                                <a class="btn btn-outline-primary btn-sm rounded-pill" href="<?= htmlspecialchars($business['googleUrl'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                    <i class="fa-brands fa-google ms-1"></i>صفحهٔ گوگل
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="contact-card p-4 bg-white text-center">
                    <h2 class="h5 mb-3">هماهنگی و نوبت‌دهی تلفنی</h2>
                    <p class="text-muted mb-4">در حال حاضر درخواست‌های نوبت و مشاوره تنها از طریق تماس تلفنی ثبت می‌شود. همکاران ما در ساعات کاری پاسخگوی شما خواهند بود.</p>
                    <div class="d-grid gap-2">
                        <a class="btn btn-primary rounded-pill" href="<?= htmlspecialchars($callLink, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fa-solid fa-phone-volume ms-2"></i> تماس با پذیرش کلینیک
                        </a>
                    </div>
                    <div class="bg-light rounded-4 p-3 mt-4 text-muted small">
                        <i class="fa-solid fa-clock ms-2"></i>در صورت عدم پاسخگویی، لطفاً در نزدیک‌ترین زمان کاری دوباره با ما تماس بگیرید.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<footer class="py-4">
    <div class="container text-center">
        <p class="mb-3">
            <i class="fa-solid fa-location-dot text-danger ms-2"></i>
            <?= htmlspecialchars(formatAddress($business['address']), ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <div class="mb-3">
            <a href="<?= htmlspecialchars($business['googleUrl'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-pill"><i class="fa-brands fa-google ms-1"></i>صفحهٔ گوگل</a>
            <a href="<?= htmlspecialchars($callLink, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-sm rounded-pill ms-2"><i class="fa-solid fa-phone-volume ms-1"></i>تماس تلفنی</a>
        </div>
        <p class="mb-0">© <?= date('Y'); ?> <?= htmlspecialchars($business['name'], ENT_QUOTES, 'UTF-8'); ?> - تمامی حقوق محفوظ است.</p>
    </div>
</footer>

<script>
    (function () {
        const body = document.body;
        const toggleButton = document.getElementById('theme-toggle');
        if (!toggleButton) {
            return;
        }
        const label = document.getElementById('theme-toggle-label');
        const icon = toggleButton.querySelector('.btn-toggle-icon');
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

        const applyTheme = (mode) => {
            const isDark = mode === 'dark';
            body.classList.toggle('dark-theme', isDark);
            toggleButton.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            if (icon) {
                icon.classList.toggle('fa-moon', !isDark);
                icon.classList.toggle('fa-sun', isDark);
            }
            if (label) {
                label.textContent = isDark ? 'حالت روز' : 'حالت شب';
            }
        };

        const savedTheme = localStorage.getItem('theme-mode');
        applyTheme(savedTheme ? savedTheme : (prefersDark ? 'dark' : 'light'));

        toggleButton.addEventListener('click', () => {
            const isCurrentlyDark = body.classList.contains('dark-theme');
            const nextTheme = isCurrentlyDark ? 'light' : 'dark';
            applyTheme(nextTheme);
            try {
                localStorage.setItem('theme-mode', nextTheme);
            } catch (err) {
                console.warn('Cannot persist theme preference', err);
            }
        });
    })();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
<?php
function buildTelLink(?string $phone): string
{
    $phone = trim((string) $phone);
    if ($phone === '') {
        return '#';
    }

    if (str_starts_with($phone, 'tel:')) {
        $phone = substr($phone, 4) ?: '';
    }

    $hasPlus = str_starts_with($phone, '+');
    $digits = preg_replace('/\D+/u', '', $phone);
    if (!is_string($digits) || $digits === '') {
        return '#';
    }

    if ($hasPlus) {
        $digits = '+' . ltrim($digits, '+');
    }

    return 'tel:' . $digits;
}

function buildMailtoLink(?string $email): string
{
    $email = trim((string) $email);
    if ($email === '') {
        return '#';
    }

    if (stripos($email, 'mailto:') === 0) {
        $email = substr($email, 7) ?: '';
    }

    $email = preg_replace('/\s+/u', '', $email);
    if ($email === '' || $email === false) {
        return '#';
    }

    $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!is_string($sanitized) || $sanitized === '') {
        return '#';
    }

    return 'mailto:' . $sanitized;
}

function ensureAbsoluteUrl(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//iu', $url)) {
        return $url;
    }

    if (str_starts_with($url, '//')) {
        return 'https:' . $url;
    }

    return 'https://' . ltrim($url, '/');
}

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
        if (!empty($placeDetails['geometry']['location'])) {
            $result['coordinates']['lat'] = (float) ($placeDetails['geometry']['location']['lat'] ?? $result['coordinates']['lat']);
            $result['coordinates']['lng'] = (float) ($placeDetails['geometry']['location']['lng'] ?? $result['coordinates']['lng']);
        }
        if (!empty($placeDetails['formatted_address'])) {
            $result['address']['formatted'] = $placeDetails['formatted_address'];
        }
    }

    $configuredGoogleUrl = getBusinessGoogleUrl();
    if ($configuredGoogleUrl !== '') {
        $result['googleUrl'] = $configuredGoogleUrl;
    } elseif (is_array($placeDetails) && !empty($placeDetails['url'])) {
        $result['googleUrl'] = $placeDetails['url'];
    } else {
        $result['googleUrl'] = buildGoogleUrl($result['name']);
    }

    $result['rating'] = $placeDetails['rating'] ?? 4.9;
    $result['reviewCount'] = $placeDetails['user_ratings_total'] ?? 0;
    $result['openingHours'] = normalizeOpeningHours($placeDetails['opening_hours']['weekday_text'] ?? []);
    $result['services'] = getDefaultServices();
    $galleryItems = buildGalleryImages($placeDetails['photos'] ?? [], $apiKey);
    if (empty($galleryItems)) {
        $galleryItems = localGalleryImages();
    }
    $streetViewItem = buildStreetViewGalleryItem($result['coordinates']);
    if ($streetViewItem !== null) {
        array_unshift($galleryItems, $streetViewItem);
    }
    $result['gallery'] = [
        'images' => $galleryItems,
    ];
    $result['reviews'] = normalizeReviews($placeDetails['reviews'] ?? []);
    if (empty($result['reviews'])) {
        $result['reviews'] = fallbackReviews();
    }

    return $result;
}

function buildGoogleUrl(string $name): string
{
    $configured = getBusinessGoogleUrl();
    if ($configured !== '') {
        return $configured;
    }

    return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($name);
}

function normalizeOpeningHours(array $weekdayText): array
{
    if (!empty($weekdayText)) {
        $items = array_map(function ($line) {
            $parts = explode(':', $line, 2);
            return [
                'day' => trim($parts[0] ?? ''),
                'hours' => trim($parts[1] ?? ''),
            ];
        }, $weekdayText);

        $grouped = [];
        $currentGroup = null;

        foreach ($items as $item) {
            if ($currentGroup === null) {
                $currentGroup = [
                    'days' => [$item['day']],
                    'hours' => $item['hours'],
                ];
                continue;
            }

            if ($currentGroup['hours'] === $item['hours']) {
                $currentGroup['days'][] = $item['day'];
            } else {
                $grouped[] = $currentGroup;
                $currentGroup = [
                    'days' => [$item['day']],
                    'hours' => $item['hours'],
                ];
            }
        }

        if ($currentGroup !== null) {
            $grouped[] = $currentGroup;
        }

        return array_map(function ($group) {
            $days = $group['days'];
            $label = count($days) > 1
                ? reset($days) . ' تا ' . end($days)
                : ($days[0] ?? '');

            return [
                'day' => $label,
                'hours' => $group['hours'],
            ];
        }, $grouped);
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
            'title' => 'زیبایی و اصلاح طرح لبخند',
            'description' => 'تکنیک‌های تخصصی برای درخشندگی و هماهنگی لبخند.',
            'icon' => 'fa-solid fa-wand-magic-sparkles',
            'items' => [
                'کامپوزیت و ونیر کامپوزیت',
                'لیفت لثه',
            ],
        ],
        [
            'title' => 'درمان‌های ترمیمی و ریشه',
            'description' => 'بازسازی ساختار دندان و جلوگیری از گسترش آسیب.',
            'icon' => 'fa-solid fa-tooth',
            'items' => [
                'پر کردن دندان',
                'عصب‌کشی دقیق و بدون درد',
            ],
        ],
        [
            'title' => 'روکش و مراقبت‌های دوره‌ای',
            'description' => 'حفظ استحکام و عملکرد دندان‌ها با راهکارهای اختصاصی.',
            'icon' => 'fa-solid fa-shield-heart',
            'items' => [
                'روکش دندان',
                'بروزرسانی و پایش منظم سرویس‌ها',
            ],
        ],
        [
            'title' => 'ایمپلنت و جراحی دندان',
            'description' => 'بازگرداندن عملکرد طبیعی و زیبایی با روش‌های پیشرفته.',
            'icon' => 'fa-solid fa-screwdriver-wrench',
            'items' => [
                'ایمپلنت کامل و واحد',
                'انواع جراحی‌های مرتبط با دندان',
            ],
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
            'type' => 'image',
            'url' => $photoUrl,
            'alt' => 'تصویر از کلینیک الَنزا',
        ];
    }

    return $images;
}

function buildStreetViewGalleryItem(array $coordinates): ?array
{
    $lat = $coordinates['lat'] ?? null;
    $lng = $coordinates['lng'] ?? null;
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return null;
    }

    $lat = (float) $lat;
    $lng = (float) $lng;
    $embedUrl = sprintf(
        'https://www.google.com/maps/embed?pb=&q=&layer=c&cbll=%1$.6f,%2$.6f&cbp=11,0,0,0,0',
        $lat,
        $lng
    );

    return [
        'type' => 'streetview',
        'embedUrl' => $embedUrl,
        'alt' => 'نمای خیابانی کلینیک الَنزا',
    ];
}

function buildMapInfo(array $business, string $placeId): array
{
    $mapEmbed = trim((string) ($business['mapEmbed'] ?? ''));
    $coordinates = $business['coordinates'] ?? [];
    $lat = isset($coordinates['lat']) && is_numeric($coordinates['lat']) ? (float) $coordinates['lat'] : null;
    $lng = isset($coordinates['lng']) && is_numeric($coordinates['lng']) ? (float) $coordinates['lng'] : null;

    $embedUrl = '';
    if ($mapEmbed !== '') {
        if (filter_var($mapEmbed, FILTER_VALIDATE_URL)) {
            $embedUrl = $mapEmbed;
        } elseif (preg_match('/src="([^"]+)"/u', $mapEmbed, $matches)) {
            $embedUrl = $matches[1];
        }
    }

    if ($embedUrl === '' && $lat !== null && $lng !== null) {
        $embedUrl = sprintf('https://www.google.com/maps?q=%1$.6f,%2$.6f&z=16&output=embed', $lat, $lng);
    }

    $directionsParams = [];
    if ($placeId !== '') {
        $directionsParams['destination_place_id'] = $placeId;
    } elseif ($lat !== null && $lng !== null) {
        $directionsParams['destination'] = sprintf('%1$.6f,%2$.6f', $lat, $lng);
    } else {
        $formattedAddress = formatAddress($business['address'] ?? []);
        if ($formattedAddress !== '') {
            $directionsParams['destination'] = $formattedAddress;
        }
    }

    $directionsUrl = '';
    if (!empty($directionsParams)) {
        $directionsUrl = 'https://www.google.com/maps/dir/?api=1&' . http_build_query($directionsParams);
    }

    $viewUrl = $business['googleUrl'] ?? '';
    if ($viewUrl === '' && $lat !== null && $lng !== null) {
        $viewUrl = sprintf('https://www.google.com/maps/place/%1$.6f,%2$.6f', $lat, $lng);
    }

    return [
        'embedUrl' => $embedUrl,
        'directionsUrl' => $directionsUrl,
        'viewUrl' => $viewUrl,
    ];
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
            'type' => 'image',
            'url' => 'images/' . basename($file),
            'alt' => 'نمونه‌کار کلینیک الَنزا',
        ];
    }

    return $images;
}

function firstGalleryImageUrl(array $items): ?string
{
    foreach ($items as $item) {
        if (($item['type'] ?? 'image') === 'image' && !empty($item['url'])) {
            return $item['url'];
        }
    }

    return null;
}

function galleryImageUrls(array $items): array
{
    $urls = [];
    foreach ($items as $item) {
        if (($item['type'] ?? 'image') === 'image' && !empty($item['url'])) {
            $urls[] = $item['url'];
        }
    }

    return $urls;
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
    $formatted = '';

    if (!empty($address['formatted']) && !containsLatinLetters($address['formatted'])) {
        $formatted = $address['formatted'];
    } else {
        $parts = array_filter([
            $address['street'] ?? '',
            $address['city'] ?? '',
            $address['province'] ?? '',
            $address['postalCode'] ?? '',
            $address['country'] ?? '',
        ]);
        $formatted = implode('، ', $parts);

        if ($formatted === '' && !empty($address['formatted'])) {
            $formatted = $address['formatted'];
        }
    }

    return convertToPersianDigits($formatted);
}

function formatPhoneDisplay(string $phone): string
{
    $trimmed = trim($phone);
    if ($trimmed === '') {
        return '';
    }

    return convertToPersianDigits($trimmed);
}

function formatWebsiteLabel(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST);
    if (is_string($host) && $host !== '') {
        return convertToPersianDigits($host);
    }

    $clean = preg_replace('/^https?:\/\//iu', '', $url);
    if (!is_string($clean) || $clean === '') {
        $clean = $url;
    }

    $clean = rtrim($clean, '/');

    return convertToPersianDigits($clean);
}

function containsLatinLetters(string $text): bool
{
    return preg_match('/[A-Za-z]/u', $text) === 1;
}

function convertToPersianDigits(string $value): string
{
    $westernDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    return str_replace($westernDigits, $persianDigits, $value);
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

    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Dentist',
        'name' => $business['name'],
        'description' => $business['description'],
        'image' => galleryImageUrls($business['gallery']['images'] ?? []),
        'url' => $business['website'],
        'telephone' => $business['phone'],
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

    if (!empty($business['email'])) {
        $data['email'] = $business['email'];
    }

    return $data;
}
