<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf6ee">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title') — Hub Événement</title>

    <link rel="preload" href="{{ asset('fonts/cormorant-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/dm-sans-latin.woff2') }}" as="font" type="font/woff2" crossorigin>

    @vite('resources/css/app.css')
</head>
<body class="flex min-h-screen flex-col bg-surface-100 text-ink">
    <a href="#contenu" class="sr-only-focusable absolute left-4 top-4 z-50 bg-surface px-4 py-2 text-label text-foreground">
        Aller au contenu
    </a>

    <header class="border-b border-line">
        <div class="mx-auto flex max-w-2xl items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap font-display text-xl text-ink">
                Hub Événement
            </a>
            <span class="eyebrow">Erreur @yield('code')</span>
        </div>
    </header>

    <main id="contenu" class="flex flex-1 items-center" tabindex="-1">
        <div class="mx-auto w-full max-w-xl px-4 py-12">
            <div class="card p-6 text-center sm:p-10">
                <p class="eyebrow">@yield('eyebrow', 'Un imprévu')</p>
                <h1 class="mt-2 font-display text-heading text-ink">@yield('title')</h1>
                <div class="mt-4 space-y-3 text-body text-ink-muted">
                    @yield('message')
                </div>
                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('home') }}" class="btn btn-primary">Retour à l'accueil</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="border-t border-line">
        <div class="mx-auto max-w-2xl px-4 py-6 text-center">
            <p class="text-caption text-ink-muted">Hub Événement — Abidjan, Côte d'Ivoire</p>
        </div>
    </footer>
</body>
</html>
