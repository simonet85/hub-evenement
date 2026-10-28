@extends('layouts.app')

@section('title', 'Pilotage — '.$event->name)

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10 {{ $event->type->accentClass() }}">
        <nav aria-label="Fil d'Ariane" class="text-caption text-ink-muted">
            <a href="{{ route('dashboard') }}" class="hover:text-ink">Mon espace</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('events.show', $event) }}" class="hover:text-ink">{{ $event->name }}</a>
            <span aria-hidden="true"> / </span>
            <span aria-current="page">Pilotage</span>
        </nav>

        <header class="mt-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="eyebrow">Pilotage</p>
                <h1 class="mt-1 font-display text-display-lg text-ink">{{ $event->name }}</h1>
                <p class="mt-2 text-body text-ink-muted">
                    {{ $event->starts_at->translatedFormat('l j F Y') }} à {{ $event->starts_at->format('H\hi') }}
                    · {{ $event->venue_name }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('events.show', $event) }}" class="btn btn-secondary">Retour à l'événement</a>
                <a href="{{ route('check-in.scan', $event) }}" class="btn btn-primary">Scanner les entrées</a>
            </div>
        </header>

        <section aria-label="Indicateurs" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-4">
                <p class="eyebrow">Foyers</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $summary['households'] }}</p>
                <p class="mt-1 text-caption text-ink-muted">
                    {{ $summary['attending'] }} présent{{ $summary['attending'] > 1 ? 's' : '' }} ·
                    {{ $summary['declined'] }} absent{{ $summary['declined'] > 1 ? 's' : '' }} ·
                    {{ $summary['pending'] }} en attente
                </p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Couverts confirmés</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $summary['coming'] }}</p>
                <p class="mt-1 text-caption text-ink-muted">sur {{ $summary['seats'] }} place{{ $summary['seats'] > 1 ? 's' : '' }} prévue{{ $summary['seats'] > 1 ? 's' : '' }}</p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Réponses</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $summary['responded'] }}</p>
                <p class="mt-1 text-caption text-ink-muted">
                    @if ($summary['late_responses'] > 0)
                        dont {{ $summary['late_responses'] }} hors délai
                    @else
                        aucune réponse hors délai
                    @endif
                </p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Régimes signalés</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $summary['diet_households'] }}</p>
                <p class="mt-1 text-caption text-ink-muted">foyer{{ $summary['diet_households'] > 1 ? 's' : '' }} présent{{ $summary['diet_households'] > 1 ? 's' : '' }}</p>
            </div>
        </section>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1.5fr_1fr]">
            <section aria-labelledby="evolution-title" class="card min-w-0 p-6">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 id="evolution-title" class="font-display text-heading text-ink">Évolution des réponses</h2>
                        <p class="mt-1 text-caption text-ink-muted">
                            14 derniers jours · du {{ $evolution['days'][0]['label'] }} au {{ $evolution['days'][13]['label'] }}
                            · {{ $evolution['total'] }} réponse{{ $evolution['total'] > 1 ? 's' : '' }} sur la période
                        </p>
                    </div>
                    <span class="status-pill">{{ $summary['responded'] }} / {{ $summary['households'] }} foyers</span>
                </div>

                <p class="sr-only">
                    Réponses par jour :
                    @foreach ($evolution['days'] as $day)
                        {{ $day['title'] }} : {{ $day['count'] }}@if (! $loop->last), @endif
                    @endforeach.
                </p>

                <div class="mt-5" aria-hidden="true">
                    <div class="flex items-end gap-1">
                        @foreach ($evolution['days'] as $day)
                            <div class="flex min-w-0 flex-1 flex-col items-center gap-1">
                                <span class="text-caption tabular text-ink-muted">{{ $day['count'] > 0 ? $day['count'] : '' }}</span>
                                <div class="flex h-28 w-full items-end rounded-sm bg-surface-300" title="{{ $day['title'] }} : {{ $day['count'] }} réponse{{ $day['count'] > 1 ? 's' : '' }}, cumul {{ $day['cumulative'] }}">
                                    <div
                                        class="w-full rounded-sm {{ $day['count'] > 0 ? 'bg-accent' : 'bg-line' }}"
                                        style="height: {{ $day['count'] > 0 ? max(6, $day['percent']) : 3 }}%"
                                    ></div>
                                </div>
                                <span class="text-caption text-ink-muted">{{ $day['date']->format('d') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <p class="mt-4 text-caption text-ink-muted">
                    Cumul à la fin de la période : {{ $summary['responded'] }} réponse{{ $summary['responded'] > 1 ? 's' : '' }}.
                </p>
            </section>

            <section aria-labelledby="moderation-title" class="card min-w-0 p-6">
                <h2 id="moderation-title" class="font-display text-heading text-ink">À modérer</h2>
                <ul role="list" class="mt-4 divide-y divide-line">
                    <li class="flex items-center justify-between gap-3 py-3">
                        <a href="{{ route('guestbook.index', $event) }}" class="text-label text-ink hover:text-accent">Messages du livre d'or</a>
                        <span class="status-pill {{ $queues['guestbook'] > 0 ? 'status-pill-warning' : '' }}">{{ $queues['guestbook'] }}</span>
                    </li>
                    <li class="flex items-center justify-between gap-3 py-3">
                        <a href="{{ route('gallery.index', $event) }}" class="text-label text-ink hover:text-accent">Photos en attente</a>
                        <span class="status-pill {{ $queues['photos'] > 0 ? 'status-pill-warning' : '' }}">{{ $queues['photos'] }}</span>
                    </li>
                    <li class="flex items-center justify-between gap-3 py-3">
                        <a href="{{ route('registry.index', $event) }}" class="text-label text-ink hover:text-accent">Paiements à vérifier</a>
                        <span class="status-pill {{ $queues['contributions'] > 0 ? 'status-pill-warning' : '' }}">{{ $queues['contributions'] }}</span>
                    </li>
                </ul>
                @if ($queues['guestbook'] + $queues['photos'] + $queues['contributions'] === 0)
                    <p class="mt-3 text-caption text-ink-muted">Rien à modérer pour l'instant.</p>
                @endif
            </section>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_1fr]">
            <section aria-labelledby="regimes-title" class="card min-w-0 p-6">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <h2 id="regimes-title" class="font-display text-heading text-ink">Régimes et allergies</h2>
                    <span class="status-pill">{{ $dietary->count() }} foyer{{ $dietary->count() > 1 ? 's' : '' }}</span>
                </div>

                @if ($dietary->isEmpty())
                    <p class="mt-3 text-body text-ink-muted">Aucun régime signalé par les foyers présents.</p>
                @else
                    <ul role="list" class="mt-4 divide-y divide-line">
                        @foreach ($dietary as $guest)
                            <li class="py-3">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="text-label text-ink">{{ $guest->name }}</p>
                                    <span class="text-caption text-ink-muted">{{ $guest->confirmedSeats() }} couvert{{ $guest->confirmedSeats() > 1 ? 's' : '' }}</span>
                                </div>
                                <p class="mt-1 whitespace-pre-line text-caption text-ink-muted">{{ $guest->dietary_notes }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section aria-labelledby="exports-title" class="card min-w-0 p-6">
                <h2 id="exports-title" class="font-display text-heading text-ink">Exports</h2>
                <p class="mt-1 text-caption text-ink-muted">
                    La liste complète contient les coordonnées. Le dossier traiteur ne contient que les foyers présents, sans coordonnées.
                </p>

                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <a href="{{ route('exports.guests.csv', $event) }}" class="btn btn-secondary">Invités en CSV</a>
                    <a href="{{ route('exports.guests.xlsx', $event) }}" class="btn btn-secondary">Invités en Excel</a>
                    <a href="{{ route('exports.caterer.pdf', $event) }}" class="btn btn-primary sm:col-span-2">Dossier traiteur (PDF)</a>
                </div>

                <div class="mt-6 border-t border-line pt-5">
                    <h3 class="font-display text-heading text-ink">Lien traiteur</h3>
                    <p class="mt-1 text-caption text-ink-muted">
                        Un lien signé, sans compte, qui donne accès aux couverts et régimes sans les coordonnées.
                        Il expire automatiquement.
                    </p>

                    <form method="POST" action="{{ route('pilotage.caterer-link', $event) }}" class="mt-3 flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="min-w-40 flex-1">
                            <label for="caterer_days" class="label">Durée du lien</label>
                            <select
                                id="caterer_days"
                                name="days"
                                class="field mt-1 @error('days') field-error @enderror"
                                @error('days') aria-invalid="true" aria-describedby="caterer_days-error" @enderror
                            >
                                <option value="1" @selected(old('days') == 1)>24 heures</option>
                                <option value="7" @selected(old('days', 7) == 7)>7 jours</option>
                                <option value="30" @selected(old('days') == 30)>30 jours</option>
                            </select>
                            @error('days')
                                <p id="caterer_days-error" class="error-text mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-secondary">Générer le lien</button>
                    </form>

                    @if (session('caterer_link'))
                        <div class="mt-4 rounded-sm border border-line bg-surface-300 p-3">
                            <p class="text-label text-ink">Lien traiteur prêt</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <input
                                    id="caterer_link"
                                    type="text"
                                    readonly
                                    value="{{ session('caterer_link') }}"
                                    class="field min-w-0 flex-1 text-caption"
                                    onclick="this.select()"
                                >
                                <button type="button" class="btn btn-secondary" data-copy-target="caterer_link">Copier</button>
                            </div>
                            @if (session('caterer_expires_at'))
                                <p class="mt-2 text-caption text-ink-muted">
                                    Expire le {{ \Illuminate\Support\Carbon::parse(session('caterer_expires_at'))->translatedFormat('j F Y \à H\hi') }}.
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
