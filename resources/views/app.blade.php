<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- ── PWA Core ──────────────────────────────────────────────────────── -->
    <link rel="manifest" href="/build/manifest.webmanifest">
    <meta name="theme-color" content="#6C33A0">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- ── PWA iOS (Safari) ────────────────────────────────────────────── -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SALMA AI">
    <link rel="apple-touch-icon" href="/icons/icon-180x180.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/icon-180x180.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/icons/icon-192x192.png">
    <link rel="apple-touch-startup-image" href="/icons/icon-512x512.png">

    <!-- ── Favicon & Windows ───────────────────────────────────────────── -->
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-96x96.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/x-icon" href="{{ asset(env('APP_ICON')) }}">
    <meta name="msapplication-TileImage" content="/icons/icon-144x144.png">
    <meta name="msapplication-TileColor" content="#6C33A0">
    <meta name="msapplication-tap-highlight" content="no">

    <!-- ── SEO / Social ────────────────────────────────────────────────── -->
    <meta name="description"
        content="Asisten digital resmi Bapenda Samsat Lamongan — informasi pajak kendaraan, jadwal layanan, dan prosedur samsat kapan saja.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="SALMA AI — Asisten Samsat Lamongan">
    <meta property="og:description" content="Tanya jadwal, tarif, dan prosedur Samsat Lamongan kapan saja.">
    <meta property="og:image" content="/icons/icon-512x512.png">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
