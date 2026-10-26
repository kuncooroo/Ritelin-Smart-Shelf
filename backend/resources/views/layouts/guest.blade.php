<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} — {{ config('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
    <link rel="preload" as="image" href="{{ asset('images/login.webp') }}" fetchpriority="high">
</head>
<body class="min-h-screen text-white antialiased selection:bg-orange-500 selection:text-white">
    <div class="relative min-h-screen overflow-hidden">
        <img src="{{ asset('images/login.webp') }}" alt="" width="1600" height="900" fetchpriority="high" decoding="async" class="absolute inset-0 h-full w-full object-cover">
        <main class="relative z-10 mx-auto flex min-h-screen max-w-md items-center justify-center px-4 py-10">
            <div class="w-full rounded-3xl border border-white/20 bg-white/10 p-8 shadow-2xl backdrop-blur-xl">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
