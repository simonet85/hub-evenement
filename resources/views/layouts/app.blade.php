<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf6ee">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', config('app.name', 'Hub Événement'))</title>

    <link rel="preload" href="{{ asset('fonts/cormorant-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/dm-sans-latin.woff2') }}" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-surface-100 text-ink">
    <a href="#contenu" class="sr-only-focusable absolute left-4 top-4 z-50 inline-flex min-h-11 items-center bg-surface px-4 text-label text-foreground">
        Aller au contenu
    </a>

    <header class="border-b border-line">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap font-display text-xl text-ink">
                Hub Événement
            </a>

            <nav class="ms-auto flex items-center gap-4" aria-label="Navigation principale">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center text-label text-ink-muted hover:text-ink">
                        Mon espace
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 items-center text-label text-ink-muted hover:text-ink">
                            Se déconnecter
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center text-label text-ink-muted hover:text-ink">
                        Se connecter
                    </a>
                    <a href="{{ route('register') }}" class="btn @yield('header_cta_variant', 'btn-primary') @yield('header_cta_class')">
                        @yield('header_cta', 'Créer un compte')
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="contenu" class="flex-1" tabindex="-1">
        @if (session('status') || session('error'))
            <div class="mx-auto max-w-5xl space-y-3 px-4 pt-6">
                @if (session('status'))
                    <x-alert tone="success">{{ session('status') }}</x-alert>
                @endif
                @if (session('error'))
                    <x-alert tone="danger">{{ session('error') }}</x-alert>
                @endif
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-line">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-2 px-4 py-6">
            <p class="text-caption text-ink-muted">Hub Événement — Abidjan, Côte d’Ivoire</p>
            <p class="text-caption text-ink-muted">Invitations, RSVP et cagnotte pour vos grands moments.</p>
        </div>
    </footer>
</body>
</html>
