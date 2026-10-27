@extends('layouts.app')

@section('title', 'Relances — '.$event->name.' — Hub Événement')

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
            <span aria-current="page">Relances</span>
        </nav>

        <header class="mt-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="eyebrow">Relances</p>
                <h1 class="mt-1 font-display text-display-lg text-ink">Relancer les foyers</h1>
                <p class="mt-2 text-body text-ink-muted">
                    {{ $event->name }} — {{ $event->starts_at->translatedFormat('j F Y') }}
                    @if ($event->rsvp_deadline)
                        · réponses attendues avant le {{ $event->rsvp_deadline->translatedFormat('j F Y') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('events.show', $event) }}" class="btn btn-secondary">Retour à l'événement</a>
        </header>

        <section aria-label="Chiffres clés" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-4">
                <p class="text-caption text-ink-muted">Sans réponse</p>
                <p class="font-display text-heading text-ink">{{ $pending->count() }}</p>
            </div>
            <div class="card p-4">
                <p class="text-caption text-ink-muted">Éligibles</p>
                <p class="font-display text-heading text-ink">{{ $eligible }}</p>
            </div>
            <div class="card p-4">
                <p class="text-caption text-ink-muted">Relances envoyées</p>
                <p class="font-display text-heading text-ink">
                    {{ $remindersSent }}@if ($remindersQueued > 0)<span class="text-caption text-ink-muted"> + {{ $remindersQueued }} en file</span>@endif
                </p>
            </div>
            <div class="card p-4">
                <p class="text-caption text-ink-muted">Désinscrits</p>
                <p class="font-display text-heading text-ink">{{ $optedOut }}</p>
            </div>
        </section>

        <section aria-labelledby="relances-title" class="card mt-8 p-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="relances-title" class="font-display text-heading text-ink">Foyers sans réponse</h2>
                    <p class="mt-1 text-caption text-ink-muted">
                        Deux relances maximum par foyer, espacées de 24 heures. Les envois partent progressivement par la file d'attente.
                    </p>
                </div>
                <a href="{{ route('events.show', $event) }}#foyers-title" class="text-caption font-semibold text-accent hover:underline">
                    Gérer les foyers
                </a>
            </div>

            @if ($pending->isEmpty())
                <p class="mt-6 text-body text-ink-muted">
                    Tous les foyers ont répondu. Il n'y a personne à relancer.
                </p>
            @else
                <form method="POST" action="{{ route('reminders.store', $event) }}" class="mt-6">
                    @csrf

                    @error('guest_ids')
                        <p class="error-text mb-3" role="alert">{{ $message }}</p>
                    @enderror

                    <ul role="list" class="divide-y divide-line border-y border-line">
                        @foreach ($pending as $guest)
                            @php
                                $eligibleGuest = $guest->canReceiveReminder();
                                $count = $guest->reminderCount();
                                $last = $guest->reminders->last();
                                $next = $guest->nextReminderAt();
                            @endphp
                            <li class="flex gap-3 py-4">
                                <input
                                    type="checkbox"
                                    name="guest_ids[]"
                                    value="{{ $guest->id }}"
                                    id="guest_{{ $guest->id }}"
                                    class="mt-1 h-5 w-5 shrink-0"
                                    @disabled(! $eligibleGuest)
                                    aria-describedby="guest_{{ $guest->id }}_statut"
                                >

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <label for="guest_{{ $guest->id }}" class="text-label text-ink">
                                            {{ $guest->name }}
                                        </label>
                                        @if ($guest->hasOptedOutOfReminders())
                                            <span class="status-pill status-pill-danger text-caption">Désinscrit</span>
                                        @endif
                                        @if (! $guest->email)
                                            <span class="status-pill status-pill-warning text-caption">Sans e-mail</span>
                                        @endif
                                        @if ($count >= \App\Models\Guest::MAX_REMINDERS)
                                            <span class="status-pill text-caption">Limite atteinte</span>
                                        @endif
                                    </div>

                                    <p class="mt-1 text-caption text-ink-muted">
                                        {{ $guest->allowed_seats }} place{{ $guest->allowed_seats > 1 ? 's' : '' }}
                                        @if ($guest->email)
                                            · {{ $guest->email }}
                                        @endif
                                        @if ($guest->phone)
                                            · {{ $guest->phone }}
                                        @endif
                                    </p>

                                    <p id="guest_{{ $guest->id }}_statut" class="mt-1 text-caption text-ink-muted">
                                        @if ($guest->hasOptedOutOfReminders())
                                            Ce foyer a refusé les relances pour cet événement.
                                        @elseif (! $guest->email)
                                            Aucun e-mail : relance impossible, utilisez WhatsApp ou contactez le foyer.
                                        @elseif ($count >= \App\Models\Guest::MAX_REMINDERS)
                                            {{ $count }} relances sur {{ \App\Models\Guest::MAX_REMINDERS }} utilisées.
                                        @else
                                            {{ $count }} / {{ \App\Models\Guest::MAX_REMINDERS }} relances utilisées.
                                        @endif

                                        @if ($last)
                                            Dernière relance le {{ $last->created_at->translatedFormat('j M Y à H\hi') }}
                                            @if (! $last->sent_at)
                                                (en file)
                                            @elseif ($last->user)
                                                par {{ $last->user->name }}
                                            @endif.
                                        @endif

                                        @if (! $guest->hasOptedOutOfReminders() && $next && $next->isFuture())
                                            Prochaine relance possible le {{ $next->translatedFormat('j M Y à H\hi') }}.
                                        @endif
                                    </p>

                                    @if ($guest->whatsappUrl() && ! $guest->hasOptedOutOfReminders())
                                        <p class="mt-2">
                                            <a
                                                href="{{ $guest->whatsappUrl() }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="text-caption font-semibold text-accent hover:underline"
                                            >
                                                Ouvrir WhatsApp pré-rempli
                                            </a>
                                        </p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="submit" class="btn btn-primary" @disabled($eligible === 0)>
                            Relancer la sélection
                        </button>
                        <button type="submit" name="all" value="1" class="btn btn-secondary" @disabled($eligible === 0)>
                            Relancer tous les foyers éligibles
                        </button>
                        <p class="text-caption text-ink-muted">
                            {{ $eligible }} foyer{{ $eligible > 1 ? 's' : '' }} éligible{{ $eligible > 1 ? 's' : '' }}
                        </p>
                    </div>
                </form>
            @endif
        </section>
    </div>
@endsection
