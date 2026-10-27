@extends('layouts.app')

@section('title', 'Livre d\'or — '.$event->name.' — Hub Événement')

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
            <span aria-current="page">Livre d'or</span>
        </nav>

        <header class="mt-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="eyebrow">{{ $event->type->label() }}</p>
                <h1 class="mt-1 font-display text-display-lg text-ink">Livre d'or</h1>
                <p class="mt-2 text-body text-ink-muted">
                    Les messages déposés par vos foyers. Seuls les messages publiés apparaissent sur les invitations.
                </p>
            </div>
            <a href="{{ route('events.show', $event) }}" class="btn btn-secondary">Retour à l'événement</a>
        </header>

        <section aria-label="Chiffres clés" class="mt-8 grid gap-4 sm:grid-cols-4">
            @foreach ([
                ['label' => 'En attente', 'count' => $pending->count()],
                ['label' => 'Publiés', 'count' => $approved->count()],
                ['label' => 'Masqués', 'count' => $hidden->count()],
                ['label' => 'Rejetés', 'count' => $rejected->count()],
            ] as $card)
                <div class="card p-4">
                    <p class="eyebrow">{{ $card['label'] }}</p>
                    <p class="mt-1 font-display text-heading text-accent">{{ $card['count'] }}</p>
                </div>
            @endforeach
        </section>

        @if ($pending->isEmpty() && $approved->isEmpty() && $hidden->isEmpty() && $rejected->isEmpty())
            <div class="card mt-8 p-8 text-center">
                <h2 class="font-display text-heading text-ink">Aucun message pour l'instant</h2>
                <p class="mx-auto mt-2 max-w-md text-body text-ink-muted">
                    Les foyers peuvent signer le livre d'or depuis leur invitation personnelle. Les messages apparaîtront ici avant publication.
                </p>
            </div>
        @else
            <div class="mt-10 space-y-10">
                @if ($pending->isNotEmpty())
                    <section aria-labelledby="guestbook-pending">
                        <h2 id="guestbook-pending" class="font-display text-heading text-ink">
                            En attente de validation
                            <span class="status-pill status-pill-warning align-middle">{{ $pending->count() }}</span>
                        </h2>
                        <div class="mt-4 space-y-3">
                            @foreach ($pending as $entry)
                                <x-guestbook-entry :entry="$entry" :event="$event" />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($approved->isNotEmpty())
                    <section aria-labelledby="guestbook-approved">
                        <h2 id="guestbook-approved" class="font-display text-heading text-ink">
                            Publiés
                            <span class="status-pill status-pill-success align-middle">{{ $approved->count() }}</span>
                        </h2>
                        <div class="mt-4 space-y-3">
                            @foreach ($approved as $entry)
                                <x-guestbook-entry :entry="$entry" :event="$event" />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($hidden->isNotEmpty())
                    <section aria-labelledby="guestbook-hidden">
                        <h2 id="guestbook-hidden" class="font-display text-heading text-ink">
                            Masqués
                            <span class="status-pill align-middle">{{ $hidden->count() }}</span>
                        </h2>
                        <div class="mt-4 space-y-3">
                            @foreach ($hidden as $entry)
                                <x-guestbook-entry :entry="$entry" :event="$event" />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($rejected->isNotEmpty())
                    <section aria-labelledby="guestbook-rejected">
                        <h2 id="guestbook-rejected" class="font-display text-heading text-ink">
                            Rejetés
                            <span class="status-pill status-pill-danger align-middle">{{ $rejected->count() }}</span>
                        </h2>
                        <div class="mt-4 space-y-3">
                            @foreach ($rejected as $entry)
                                <x-guestbook-entry :entry="$entry" :event="$event" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        @endif
    </div>
@endsection
