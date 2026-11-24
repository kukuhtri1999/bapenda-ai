@php
    $pageSeo = $seo ?? app(\App\Services\SeoService::class)->resolveFallback(request(), $page ?? []);
    $metaTitle = $pageSeo['title'] ?? config('app.name', 'KB Samsat Lamongan — SALMA AI');
    $metaDesc = $pageSeo['description'] ?? 'Portal layanan resmi KB Samsat Lamongan & asisten cerdas SALMA AI untuk informasi pajak kendaraan bermotor dan jadwal Samsat.';
    $metaKeywords = $pageSeo['keywords'] ?? '';
    $metaRobots = $pageSeo['robots'] ?? 'index, follow';
    $canonicalUrl = $pageSeo['canonical'] ?? url('/');
    $ogType = $pageSeo['og_type'] ?? 'website';
    $ogImage = $pageSeo['og_image'] ?? url('/images/og-samsat-lamongan.png');
    $ogImageAlt = $pageSeo['og_image_alt'] ?? $metaTitle;
    $schemas = $pageSeo['schemas'] ?? [];
    $noscriptMeta = $pageSeo['noscript_meta'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="U2b8LtcV2OaWO0Fhlwk75pIquUB_N4b38iFUgt1oZNc" />

    <!-- ── PWA Core ──────────────────────────────────────────────────────── -->
    <link rel="manifest" href="/build/manifest.webmanifest?v=20261124">
    <meta name="theme-color" content="#C0392B">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- ── PWA iOS (Safari) ────────────────────────────────────────────── -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SALMA AI">
    <link rel="apple-touch-icon" href="/icons/icon-180x180.png?v=20261124">
    <link rel="apple-touch-icon" sizes="152x152" href="/icons/icon-152x152.png?v=20261124">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/icon-180x180.png?v=20261124">
    <link rel="apple-touch-icon" sizes="167x167" href="/icons/icon-192x192.png?v=20261124">
    <link rel="apple-touch-startup-image" href="/icons/icon-512x512.png?v=20261124">

    <!-- ── Favicon & Windows ───────────────────────────────────────────── -->
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-96x96.png?v=20261124">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png?v=20261124">
    <link rel="shortcut icon" href="/favicon.ico?v=20261124">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=20261124">
    <meta name="msapplication-TileImage" content="/icons/icon-144x144.png?v=20261124">
    <meta name="msapplication-TileColor" content="#C0392B">
    <meta name="msapplication-tap-highlight" content="no">

    <!-- ── SEO / Canonical / Geo / Social ──────────────────────────────── -->
    <title inertia>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDesc }}">
    @if(!empty($metaKeywords))
    <meta name="keywords" content="{{ $metaKeywords }}">
    @endif
    <meta name="robots" content="{{ $metaRobots }}">
    <meta name="author" content="KB Samsat Lamongan — Bapenda Provinsi Jawa Timur">
    <meta name="geo.region" content="ID-JI">
    <meta name="geo.placename" content="Lamongan">
    <meta name="geo.position" content="-7.1190;112.4167">
    <meta name="ICBM" content="-7.1190, 112.4167">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <!-- ── Open Graph & Twitter Cards ──────────────────────────────────── -->
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="KB Samsat Lamongan — SALMA AI">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDesc }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <!-- ── Structured Data (JSON-LD) ───────────────────────────────────── -->
    @foreach($schemas as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endforeach

    <!-- Fonts (Preconnected for Core Web Vitals) -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @if(config('services.recaptcha.enabled'))
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}" async defer></script>
    <style>
        .grecaptcha-badge {
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            z-index: -9999 !important;
        }
    </style>
    @endif
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia

    @if(!empty($noscriptMeta))
    <noscript>
        <div style="max-width:960px;margin:2rem auto;padding:1.5rem;font-family:sans-serif;line-height:1.6;">
            <h1>{{ $noscriptMeta['agency'] ?? 'KB Samsat Lamongan' }} — Layanan Resmi Pajak Kendaraan Bermotor</h1>
            <p><strong>Alamat:</strong> {{ $noscriptMeta['address'] ?? '' }} | <strong>Telepon:</strong> {{ $noscriptMeta['phone'] ?? '' }}</p>
            <p><strong>Jam Layanan:</strong> {{ $noscriptMeta['hours'] ?? '' }}</p>
            <nav>
                <a href="{{ url('/panduan') }}">Panduan &amp; FAQ Pajak Kendaraan</a> |
                <a href="{{ url('/wajib-pajak') }}">Tanya SALMA AI (24 Jam)</a> |
                <a href="{{ url('/lotre-undian') }}">Apresiasi Wajib Pajak Patuh</a>
            </nav>
            @if(!empty($noscriptMeta['faqs']))
            <h2>Pertanyaan yang Sering Diajukan</h2>
            <ul>
                @foreach($noscriptMeta['faqs'] as $faq)
                <li>
                    <strong><a href="{{ $faq['url'] }}">{{ $faq['question'] }}</a></strong>
                    <p>{{ $faq['answer'] }}</p>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </noscript>
    @endif
</body>

</html>
