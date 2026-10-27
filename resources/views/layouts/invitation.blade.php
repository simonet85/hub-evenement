<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', config('app.name', 'Hub Événement'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:wght@400;500;600&family=Pinyon+Script&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-surface-100 text-ink">
    <header class="border-b border-line">
        <div class="mx-auto flex max-w-2xl items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="font-display text-xl text-ink">
                Hub Événement
            </a>
            <span class="eyebrow">Invitation privée</span>
        </div>
    </header>

    <main id="contenu" class="flex-1">
        <div class="mx-auto max-w-2xl px-4 py-8 sm:py-12">
            @if (session('status'))
                <p class="card mb-4 p-4 text-caption text-success" role="status">{{ session('status') }}</p>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="border-t border-line">
        <div class="mx-auto max-w-2xl px-4 py-6 text-center">
            <p class="text-caption text-ink-muted">Hub Événement — Abidjan, Côte d'Ivoire</p>
        </div>
    </footer>
</body>
</html>
