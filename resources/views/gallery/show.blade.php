@extends('layouts.invitation')

@section('title', 'Galerie photo — '.$event->name.' — Hub Événement')

@section('content')
    <div class="{{ $event->type->accentClass() }}">
        <article class="card card-invitation overflow-hidden">
            <div class="h-1 bg-accent" aria-hidden="true"></div>

            <div class="p-6 sm:p-10">
                <p class="eyebrow">{{ $event->type->label() }}</p>
                <div class="mt-2 flex flex-wrap items-end justify-between gap-2">
                    <h1 class="font-display text-heading text-ink sm:text-display-lg">Galerie photo</h1>
                    @if ($photos->total() > 0)
                        <span class="status-pill">
                            {{ $photos->total() }} photo{{ $photos->total() > 1 ? 's' : '' }}
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-body text-ink-muted">
                    {{ $event->name }} · {{ $event->starts_at->translatedFormat('j F Y') }}
                </p>

                @if ($photos->isEmpty())
                    <p class="mt-8 rounded-sm border border-line bg-surface-300 p-4 text-body text-ink-muted">
                        Aucune photo publiée pour l'instant. Revenez après l'événement.
                    </p>
                @else
                    <ul role="list" class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3">
                        @foreach ($photos as $photo)
                            <li>
                                <figure>
                                    <a
                                        href="{{ URL::temporarySignedRoute('gallery.photo', now()->addMinutes(30), ['photo' => $photo, 'variant' => 'web']) }}"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <img
                                            src="{{ URL::temporarySignedRoute('gallery.photo', now()->addMinutes(30), ['photo' => $photo, 'variant' => 'thumb']) }}"
                                            alt="{{ $photo->caption ?: 'Photo de l’événement '.$event->name }}"
                                            loading="lazy"
                                            class="aspect-square w-full rounded-sm border border-line object-cover"
                                        >
                                    </a>
                                    @if ($photo->caption)
                                        <figcaption class="mt-2 text-caption text-ink-muted">{{ $photo->caption }}</figcaption>
                                    @endif
                                </figure>
                            </li>
                        @endforeach
                    </ul>

                    @if ($photos->hasPages())
                        <div class="mt-6">{{ $photos->links() }}</div>
                    @endif
                @endif

                <div class="mt-8 flex flex-wrap gap-3 border-t border-line pt-6">
                    <a href="{{ route('invitations.show', $guest->invitation_token) }}" class="btn btn-secondary">
                        Retour à l'invitation
                    </a>
                    <a href="{{ route('invitations.programme', $guest->invitation_token) }}" class="btn btn-secondary">
                        Programme et carte
                    </a>
                </div>
            </div>
        </article>

        <p class="mt-6 text-center text-caption text-ink-muted">
            Ce lien est personnel et confidentiel. Merci de ne pas le partager.
        </p>
    </div>
@endsection
