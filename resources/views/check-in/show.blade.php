@extends('layouts.app')

@section('title', 'Pointage — '.$guest->name)

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="mx-auto max-w-xl px-4 py-8 {{ $event->type->accentClass() }}">
        <nav aria-label="Fil d'Ariane" class="text-caption text-ink-muted">
            <a href="{{ route('events.show', $event) }}" class="hover:text-ink">Retour à l'événement</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('check-in.scan', $event) }}" class="hover:text-ink">Scanner les entrées</a>
        </nav>

        <header class="mt-4">
            <p class="eyebrow">Pointage d'entrée</p>
            <h1 class="mt-1 font-display text-heading text-ink sm:text-display-lg">{{ $event->name }}</h1>
            <p class="mt-1 text-caption text-ink-muted">
                {{ $event->starts_at->translatedFormat('l j F Y') }} — {{ $event->venue_name }}
            </p>
        </header>

        <article class="card card-invitation mt-6 overflow-hidden">
            <div class="h-1 bg-accent" aria-hidden="true"></div>

            <div class="p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="eyebrow">Foyer</p>
                        <h2 class="mt-1 font-display text-heading text-ink">{{ $guest->name }}</h2>
                    </div>
                    <x-rsvp-status :guest="$guest" />
                </div>

                <dl class="mt-5 space-y-2 text-body">
                    <div class="flex flex-wrap gap-x-2">
                        <dt class="text-ink-muted">Places :</dt>
                        <dd class="text-ink">
                            @if ($guest->rsvp_status === \App\Enums\RsvpStatus::Attending)
                                {{ $guest->rsvp_seats ?? $guest->allowed_seats }} confirmée{{ ($guest->rsvp_seats ?? $guest->allowed_seats) > 1 ? 's' : '' }}
                                sur {{ $guest->allowed_seats }} autorisée{{ $guest->allowed_seats > 1 ? 's' : '' }}
                            @else
                                {{ $guest->allowed_seats }} autorisée{{ $guest->allowed_seats > 1 ? 's' : '' }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Prénoms :</dt>
                        <dd class="whitespace-pre-line text-ink">{{ $guest->attendee_names ?: 'Non renseignés' }}</dd>
                    </div>
                    @if ($guest->dietary_notes)
                        <div>
                            <dt class="text-ink-muted">Régime :</dt>
                            <dd class="text-ink">{{ $guest->dietary_notes }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6 border-t border-line pt-5">
                    @if ($guest->hasCheckedIn())
                        <p class="text-caption text-success" role="status">
                            Entrée enregistrée le {{ $guest->checked_in_at->translatedFormat('j F Y \à H\hi') }}@if ($guest->checkedInBy)
                                par {{ $guest->checkedInBy->name }}
                            @endif.
                        </p>

                        <form method="POST" action="{{ route('check-in.undo.form', $guest->entry_token) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full">
                                Annuler le pointage
                            </button>
                        </form>
                    @else
                        @if ($guest->rsvp_status !== \App\Enums\RsvpStatus::Attending)
                            <p class="rounded-sm border border-line bg-surface-300 p-3 text-caption text-warning">
                                Ce foyer n'a pas confirmé sa présence ({{ mb_strtolower($guest->rsvp_status->label()) }}).
                                Le pointage reste possible après confirmation explicite.
                            </p>
                        @endif

                        <form method="POST" action="{{ route('check-in.store', $guest->entry_token) }}" class="mt-4">
                            @csrf
                            @if ($guest->rsvp_status !== \App\Enums\RsvpStatus::Attending)
                                <input type="hidden" name="confirm" value="1">
                            @endif
                            <button type="submit" class="btn btn-primary w-full">
                                {{ $guest->rsvp_status === \App\Enums\RsvpStatus::Attending ? 'Confirmer l’entrée' : 'Pointer malgré le statut' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </article>

        <p class="mt-6 text-center text-caption text-ink-muted">
            {{ $counters['checked_in_households'] }} / {{ $counters['households'] }} foyers pointés ·
            {{ $counters['checked_in_seats'] }} / {{ $counters['seats'] }} couverts
        </p>
    </div>
@endsection
