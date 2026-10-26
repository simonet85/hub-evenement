<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Hub Événement'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:wght@400;500;600&family=Pinyon+Script&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-surface-100 text-ink">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-sm focus:bg-surface-200 focus:px-4 focus:py-2 focus:text-label focus:text-ink">
        Aller au contenu
    </a>

    <header class="border-b border-line">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="font-display text-xl text-ink">
                Hub Événement
            </a>

            <nav class="flex items-center gap-4" aria-label="Navigation principale">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-label text-ink-muted hover:text-ink">
                        Mon espace
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-label text-ink-muted hover:text-ink">
                            Se déconnecter
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-label text-ink-muted hover:text-ink">
                        Se connecter
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-primary">
                        Créer un compte
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="contenu" class="flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-line">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-2 px-4 py-6">
            <p class="text-caption text-ink-muted">Hub Événement — Abidjan, Côte d'Ivoire</p>
            <p class="text-caption text-ink-muted">Invitations, RSVP et cagnotte pour vos grands moments.</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
