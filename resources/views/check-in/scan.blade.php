@extends('layouts.app')

@section('title', 'Pointage — '.$event->name)

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div
        class="mx-auto max-w-2xl px-4 py-8 {{ $event->type->accentClass() }}"
        data-scan
        data-record-url="{{ route('check-in.record', $event) }}"
    >
        <nav aria-label="Fil d'Ariane" class="text-caption text-ink-muted">
            <a href="{{ route('events.show', $event) }}" class="hover:text-ink">Retour à l'événement</a>
        </nav>

        <header class="mt-4">
            <p class="eyebrow">Pointage</p>
            <h1 class="mt-1 font-display text-heading text-ink sm:text-display-lg">{{ $event->name }}</h1>
            <p class="mt-1 text-caption text-ink-muted">
                {{ $event->starts_at->translatedFormat('l j F Y') }} — {{ $event->venue_name }}
            </p>
        </header>

        <section aria-label="Compteurs de pointage" class="mt-6 grid grid-cols-2 gap-3">
            <div class="card p-4">
                <p class="eyebrow">Foyers pointés</p>
                <p class="mt-1 font-display text-heading text-accent" data-counter-households>
                    {{ $counters['checked_in_households'] }} / {{ $counters['households'] }}
                </p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Couverts pointés</p>
                <p class="mt-1 font-display text-heading text-accent" data-counter-seats>
                    {{ $counters['checked_in_seats'] }} / {{ $counters['seats'] }}
                </p>
            </div>
        </section>

        <section aria-labelledby="scan-title" class="card mt-6 p-4 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="scan-title" class="font-display text-heading text-ink">Scanner un QR</h2>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-primary" data-camera-start>
                        Démarrer la caméra
                    </button>
                    <button type="button" class="btn btn-secondary" data-camera-stop hidden>
                        Arrêter
                    </button>
                </div>
            </div>

            <div id="qr-reader" data-qr-reader class="mt-3 overflow-hidden rounded-sm"></div>
            <p class="mt-2 text-caption text-ink-muted" data-camera-status aria-live="polite">
                La caméra reste inactive jusqu'à votre accord.
            </p>

            <noscript>
                <p class="mt-3 text-caption text-danger">
                    Activez JavaScript pour utiliser la caméra, ou ouvrez directement le lien du QR code.
                </p>
            </noscript>

            <form class="mt-6 border-t border-line pt-5" data-manual-form>
                <label for="scan-code" class="label">Saisir le code ou l'URL</label>
                <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                    <input
                        id="scan-code"
                        type="text"
                        inputmode="text"
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        placeholder="Collez le lien du QR ou saisissez le code"
                        class="field min-w-0 flex-1"
                        data-code-input
                    >
                    <button type="submit" class="btn btn-secondary sm:w-auto">Vérifier</button>
                </div>
                <p class="help mt-2">
                    Utile si la caméra est refusée ou indisponible. Le code figure sous le QR de l'invitation.
                </p>
            </form>
        </section>

        <section
            class="card mt-6 p-4 sm:p-6"
            aria-labelledby="result-title"
            data-result
            aria-live="polite"
            hidden
        >
            <p class="text-caption" data-result-message></p>

            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                <h2 id="result-title" class="font-display text-heading text-ink" data-guest-name></h2>
                <span class="status-pill" data-guest-status></span>
            </div>

            <dl class="mt-4 space-y-2 text-body">
                <div class="flex flex-wrap gap-x-2">
                    <dt class="text-ink-muted">Places :</dt>
                    <dd class="text-ink" data-guest-seats></dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Prénoms :</dt>
                    <dd class="whitespace-pre-line text-ink" data-guest-names></dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Entrée :</dt>
                    <dd class="text-ink" data-guest-check-in></dd>
                </div>
            </dl>

            <div class="mt-5 flex flex-col gap-2 border-t border-line pt-4 sm:flex-row sm:flex-wrap">
                <button type="button" class="btn btn-primary" data-confirm hidden>
                    Pointer malgré le statut
                </button>
                <button type="button" class="btn btn-secondary" data-undo hidden>
                    Annuler le pointage
                </button>
                <button type="button" class="btn btn-secondary" data-next>
                    Scanner le suivant
                </button>
            </div>
        </section>
    </div>
@endsection
