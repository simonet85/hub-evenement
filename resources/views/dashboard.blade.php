@extends('layouts.app')

@section('title', 'Mon espace — Hub Événement')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-12">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Espace organisateur</p>
                <h1 class="mt-2 font-display text-display-lg text-ink">Bonjour, {{ auth()->user()->name }}</h1>
                <p class="mt-2 text-body text-ink-muted">
                    Retrouvez vos événements, leurs foyers et leurs places prévues.
                </p>
            </div>
            <a href="{{ route('events.create') }}" class="btn btn-primary">
                Créer un événement
            </a>
        </div>

        @if ($events->isEmpty())
            <div class="card mt-8 p-8 text-center">
                <p class="font-script text-script text-accent">Bienvenue</p>
                <h2 class="mt-2 font-display text-heading text-ink">Aucun événement pour l'instant</h2>
                <p class="mx-auto mt-2 max-w-md text-body text-ink-muted">
                    Créez votre premier événement pour commencer à réunir vos foyers.
                </p>
                <a href="{{ route('events.create') }}" class="btn btn-primary mt-6">
                    Créer mon premier événement
                </a>
            </div>
        @else
            <ul class="mt-8 grid gap-4 sm:grid-cols-2" role="list">
                @foreach ($events as $event)
                    <li>
                        <article class="card card-invitation {{ $event->type->accentClass() }} flex h-full flex-col p-6">
                            <div class="-mx-6 -mt-6 mb-4 h-1 bg-accent" aria-hidden="true"></div>

                            <p class="eyebrow">{{ $event->type->label() }}</p>
                            <h2 class="mt-1 font-display text-heading">
                                <a href="{{ route('events.show', $event) }}" class="text-ink hover:text-accent">
                                    {{ $event->name }}
                                </a>
                            </h2>

                            <dl class="mt-4 space-y-1 text-body text-ink-muted">
                                <div class="flex gap-2">
                                    <dt class="sr-only">Date</dt>
                                    <dd>
                                        {{ $event->starts_at->translatedFormat('l j F Y') }}
                                        à {{ $event->starts_at->format('H\hi') }}
                                    </dd>
                                </div>
                                <div class="flex gap-2">
                                    <dt class="sr-only">Lieu</dt>
                                    <dd>{{ $event->venue_name }}</dd>
                                </div>
                            </dl>

                            <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="status-pill">
                                        {{ $event->guests_count }} foyer{{ $event->guests_count > 1 ? 's' : '' }}
                                    </span>
                                    @if ($event->pending_guestbook_count > 0)
                                        <a href="{{ route('guestbook.index', $event) }}" class="status-pill status-pill-warning">
                                            {{ $event->pending_guestbook_count }} message{{ $event->pending_guestbook_count > 1 ? 's' : '' }} à modérer
                                        </a>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4">
                                    @can('update', $event)
                                        <a href="{{ route('events.edit', $event) }}" class="text-label text-ink-muted hover:text-ink">
                                            Modifier
                                        </a>
                                    @endcan
                                    <a href="{{ route('events.show', $event) }}" class="text-label text-accent hover:underline">
                                        Ouvrir
                                    </a>
                                </div>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
