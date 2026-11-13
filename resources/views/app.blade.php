<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="description" content="BlogVerse Pro adalah platform penerbitan artikel modern dan jurnalistik dengan kurasi konten berkualitas dan dukungan asisten AI.">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">

    <title inertia>{{ config('app.name', 'BlogVerse Pro') }}</title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <!-- Non-blocking Fonts: Preconnect & Asynchronous Loading -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.bunny.net">
    <link rel="preload" as="style" href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|newsreader:400,400i,500,600,700&display=swap">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|newsreader:400,400i,500,600,700&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|newsreader:400,400i,500,600,700&display=swap">
    </noscript>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-paper">
    @inertia
</body>
</html>
