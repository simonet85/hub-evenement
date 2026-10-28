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
    <a href="#contenu" class="sr-only-focusable absolute left-4 top-4 z-50 bg-surface px-4 py-2 text-label text-foreground">
        Aller au contenu
    </a>

    <header class="border-b border-line">
        <div class="mx-auto flex @yield('container', 'max-w-2xl') items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap font-display text-xl text-ink">
                Hub Événement
            </a>
            <span class="eyebrow">@yield('eyebrow', 'Invitation privée')</span>
        </div>
    </header>

    <main id="contenu" class="flex-1">
        <div class="mx-auto @yield('container', 'max-w-2xl') px-4 py-8 sm:py-12">
            @if (session('status'))
                <x-alert tone="success" class="mb-4">{{ session('status') }}</x-alert>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="border-t border-line">
        <div class="mx-auto @yield('container', 'max-w-2xl') px-4 py-6 text-center">
            <p class="text-caption text-ink-muted">Hub Événement — Abidjan, Côte d'Ivoire</p>
        </div>
    </footer>
</body>
</html>
