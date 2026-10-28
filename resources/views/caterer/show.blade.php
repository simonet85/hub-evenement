@extends('layouts.invitation')

@section('title', 'Dossier traiteur — '.$event->name)
@section('container', 'max-w-4xl')
@section('eyebrow', 'Lecture seule')

@section('content')
    <div class="{{ $event->type->accentClass() }}">
        <header class="text-center">
            <p class="eyebrow">Dossier traiteur</p>
            <h1 class="mt-2 font-display text-display-lg text-ink">{{ $event->name }}</h1>
            <p class="mt-3 text-body-lg text-ink-muted">
                {{ $event->starts_at->translatedFormat('l j F Y') }} à {{ $event->starts_at->format('H\hi') }}
            </p>
            <p class="text-body text-ink-muted">{{ $event->venue_name }}@if ($event->venue_address) — {{ $event->venue_address }}@endif</p>
        </header>

        <x-alert tone="warning" class="mt-6">
            Lien privé en lecture seule, valable jusqu'au
            {{ $expiresAt->translatedFormat('j F Y \à H\hi') }}.
            Aucune coordonnée d'invité n'est transmise.
        </x-alert>

        <section aria-label="Synthèse" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-4">
                <p class="eyebrow">Couverts confirmés</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $summary['coming'] }}</p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Foyers présents</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $summary['attending'] }}</p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Régimes signalés</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $summary['diet_households'] }}</p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Places prévues</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $summary['seats'] }}</p>
            </div>
        </section>

        <section aria-labelledby="telechargements-title" class="card mt-6 p-6">
            <h2 id="telechargements-title" class="font-display text-heading text-ink">Téléchargements</h2>
            <p class="mt-1 text-caption text-ink-muted">
                Les fichiers expirent en même temps que ce lien.
            </p>
            <div class="mt-4 grid gap-2 sm:grid-cols-3">
                <a href="{{ $downloads['csv'] }}" class="btn btn-secondary">Couverts en CSV</a>
                <a href="{{ $downloads['xlsx'] }}" class="btn btn-secondary">Couverts en Excel</a>
                <a href="{{ $downloads['pdf'] }}" class="btn btn-primary">Dossier PDF</a>
            </div>
        </section>

        <section aria-labelledby="couverts-title" class="card mt-6 p-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="couverts-title" class="font-display text-heading text-ink">Foyers présents</h2>
                <span class="status-pill">{{ $households->count() }} foyer{{ $households->count() > 1 ? 's' : '' }}</span>
            </div>

            @if ($households->isEmpty())
                <p class="mt-3 text-body text-ink-muted">
                    Aucun foyer n'a encore confirmé sa présence.
                </p>
            @else
                <div class="mt-4 hidden overflow-x-auto sm:block">
                    <table class="w-full min-w-[36rem] border-collapse text-left">
                        <thead>
                            <tr class="border-b border-line">
                                <th scope="col" class="py-2 pe-3 text-caption font-semibold uppercase tracking-wide text-ink-muted">Foyer</th>
                                <th scope="col" class="py-2 pe-3 text-caption font-semibold uppercase tracking-wide text-ink-muted">Couverts</th>
                                <th scope="col" class="py-2 pe-3 text-caption font-semibold uppercase tracking-wide text-ink-muted">Prénoms</th>
                                <th scope="col" class="py-2 text-caption font-semibold uppercase tracking-wide text-ink-muted">Régime / allergies</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($households as $household)
                                <tr class="border-b border-line align-top">
                                    <th scope="row" class="py-3 pe-3 text-label font-normal text-ink">{{ $household['name'] }}</th>
                                    <td class="py-3 pe-3 text-body text-ink">{{ $household['seats'] }}</td>
                                    <td class="py-3 pe-3 text-caption text-ink-muted">{{ $household['attendees'] ?: '—' }}</td>
                                    <td class="py-3 whitespace-pre-line text-caption text-ink-muted">{{ $household['dietary'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th scope="row" class="py-3 pe-3 text-label text-ink">Total</th>
                                <td class="py-3 pe-3 text-label text-ink">{{ $summary['coming'] }}</td>
                                <td colspan="2" class="py-3 text-caption text-ink-muted">{{ $summary['diet_households'] }} foyer{{ $summary['diet_households'] > 1 ? 's' : '' }} avec régime signalé</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <ul role="list" class="mt-4 space-y-3 sm:hidden">
                    @foreach ($households as $household)
                        <li class="rounded-sm border border-line p-4">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="text-label text-ink">{{ $household['name'] }}</p>
                                <span class="status-pill">{{ $household['seats'] }} couvert{{ $household['seats'] > 1 ? 's' : '' }}</span>
                            </div>
                            <p class="mt-2 text-caption text-ink-muted">
                                <span class="font-semibold text-ink">Prénoms :</span>
                                {{ $household['attendees'] ?: '—' }}
                            </p>
                            <p class="mt-1 whitespace-pre-line text-caption text-ink-muted">
                                <span class="font-semibold text-ink">Régime :</span>
                                {{ $household['dietary'] ?: '—' }}
                            </p>
                        </li>
                    @endforeach
                    <li class="flex flex-wrap items-baseline justify-between gap-2 border-t border-line pt-3">
                        <p class="text-label text-ink">Total</p>
                        <p class="text-caption text-ink-muted">
                            {{ $summary['coming'] }} couvert{{ $summary['coming'] > 1 ? 's' : '' }}
                            · {{ $summary['diet_households'] }} foyer{{ $summary['diet_households'] > 1 ? 's' : '' }} avec régime
                        </p>
                    </li>
                </ul>
            @endif
        </section>

        <p class="mt-6 text-center text-caption text-ink-muted">
            Document confidentiel réservé au prestataire — Hub Événement.
        </p>
    </div>
@endsection
