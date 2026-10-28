@extends('layouts.app')

@section('title', 'Galerie photo — '.$event->name.' — Hub Événement')

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
            <span aria-current="page">Galerie photo</span>
        </nav>

        <header class="mt-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="eyebrow">{{ $event->type->label() }}</p>
                <h1 class="mt-1 font-display text-display-lg text-ink">Galerie photo</h1>
                <p class="mt-2 text-body text-ink-muted">
                    Les photos déposées par les organisateurs. Seules les photos publiées apparaissent sur l'invitation des foyers.
                </p>
            </div>
            <a href="{{ route('events.show', $event) }}" class="btn btn-secondary">Retour à l'événement</a>
        </header>

        @if ($errors->has('photos') || $errors->has('photos.*'))
            <div class="card mt-6 p-4" role="alert">
                <p class="error-text">{{ $errors->first('photos') ?: $errors->first('photos.*') }}</p>
            </div>
        @endif

        @if ($errors->has('caption'))
            <div class="card mt-6 p-4" role="alert">
                <p class="error-text">{{ $errors->first('caption') }}</p>
            </div>
        @endif

        <section aria-labelledby="upload-title" class="card mt-8 p-6">
            <h2 id="upload-title" class="font-display text-heading text-ink">Ajouter des photos</h2>
            <p class="mt-1 text-caption text-ink-muted">
                10 photos maximum par envoi, 10 Mo par photo. Formats JPEG ou PNG.
                Les fichiers restent privés et passent par la modération.
            </p>

            <form method="POST" action="{{ route('gallery.store', $event) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf

                <div class="min-w-0 flex-1">
                    <label for="photos" class="label">Photos à envoyer</label>
                    <input
                        id="photos"
                        name="photos[]"
                        type="file"
                        accept="image/jpeg,image/png"
                        multiple
                        required
                        class="field mt-1"
                    >
                </div>

                <button type="submit" class="btn btn-primary">Envoyer les photos</button>
            </form>
        </section>

        <section aria-label="Chiffres clés" class="mt-8 grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['label' => 'En attente', 'count' => $pending->count()],
                ['label' => 'Publiées', 'count' => $approved->count()],
                ['label' => 'Masquées', 'count' => $hidden->count()],
            ] as $card)
                <div class="card p-4">
                    <p class="eyebrow">{{ $card['label'] }}</p>
                    <p class="mt-1 font-display text-heading text-accent">{{ $card['count'] }}</p>
                </div>
            @endforeach
        </section>

        @if ($pending->isEmpty() && $approved->isEmpty() && $hidden->isEmpty())
            <div class="card mt-8 p-8 text-center">
                <h2 class="font-display text-heading text-ink">Aucune photo pour l'instant</h2>
                <p class="mx-auto mt-2 max-w-md text-body text-ink-muted">
                    Envoyez les premières photos de l'événement. Elles apparaîtront ici avant d'être publiées aux foyers.
                </p>
            </div>
        @else
            <div class="mt-10 space-y-10">
                @if ($pending->isNotEmpty())
                    <section aria-labelledby="photos-pending">
                        <h2 id="photos-pending" class="font-display text-heading text-ink">
                            En attente de validation
                            <span class="status-pill status-pill-warning align-middle">{{ $pending->count() }}</span>
                        </h2>
                        <div class="mt-4 space-y-3">
                            @foreach ($pending as $photo)
                                <x-photo-card :photo="$photo" :event="$event" />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($approved->isNotEmpty())
                    <section aria-labelledby="photos-approved">
                        <h2 id="photos-approved" class="font-display text-heading text-ink">
                            Publiées
                            <span class="status-pill status-pill-success align-middle">{{ $approved->count() }}</span>
                        </h2>
                        <div class="mt-4 space-y-3">
                            @foreach ($approved as $photo)
                                <x-photo-card :photo="$photo" :event="$event" />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($hidden->isNotEmpty())
                    <section aria-labelledby="photos-hidden">
                        <h2 id="photos-hidden" class="font-display text-heading text-ink">
                            Masquées
                            <span class="status-pill align-middle">{{ $hidden->count() }}</span>
                        </h2>
                        <div class="mt-4 space-y-3">
                            @foreach ($hidden as $photo)
                                <x-photo-card :photo="$photo" :event="$event" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        @endif
    </div>
@endsection
