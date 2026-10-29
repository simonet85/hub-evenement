@extends('layouts.invitation')

@section('title', 'Invitation — '.$event->name.' — Hub Événement')
@section('eyebrow', $isDemo ? 'Démonstration' : 'Invitation privée')

@section('content')
    <div class="{{ $event->type->accentClass() }}">
        @if ($isDemo)
            <div class="mb-4 rounded-sm border border-line bg-surface-300 px-4 py-3 text-caption text-ink-muted" role="status">
                <span class="font-semibold text-ink">Invitation de démonstration.</span>
                Les réponses sont enregistrées sur un événement fictif. Aucune donnée réelle n’est utilisée.
            </div>
        @endif

        <article class="card card-invitation overflow-hidden">
            <div class="h-1 bg-accent" aria-hidden="true"></div>

            <div class="p-6 sm:p-10">
                <p class="eyebrow">{{ $event->type->label() }}</p>
                <h1 class="mt-2 font-display text-display-lg text-ink sm:text-display-xl">
                    {{ $event->name }}
                </h1>

                <p class="mt-4 text-body-lg text-ink-muted">
                    {{ $event->starts_at->translatedFormat('l j F Y') }} à {{ $event->starts_at->format('H\hi') }}
                </p>
                <p class="text-body text-ink-muted">
                    {{ $event->venue_name }}@if ($event->venue_address) — {{ $event->venue_address }}@endif
                </p>
                @if ($event->dress_code)
                    <p class="mt-1 text-caption text-ink-muted">Code vestimentaire : {{ $event->dress_code }}</p>
                @endif

                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('invitations.programme', $guest->invitation_token) }}" class="btn btn-secondary">
                        Programme et carte
                    </a>
                    @if ($galleryCount > 0)
                        <a href="{{ route('invitations.gallery', $guest->invitation_token) }}" class="btn btn-secondary">
                            Galerie photo ({{ $galleryCount }})
                        </a>
                    @endif
                    @if ($hasRegistry)
                        <a href="{{ route('invitations.registry', $guest->invitation_token) }}" class="btn btn-secondary">
                            Cadeaux et cagnotte
                        </a>
                    @endif
                </div>

                <div class="mt-8 border-t border-line pt-6" data-reveal>
                    <p class="eyebrow">Invitation pour</p>
                    <p class="mt-1 font-display text-heading text-ink">{{ $guest->name }}</p>
                    <p class="text-caption text-ink-muted">
                        {{ $guest->allowed_seats }} place{{ $guest->allowed_seats > 1 ? 's' : '' }} réservée{{ $guest->allowed_seats > 1 ? 's' : '' }} pour votre foyer.
                    </p>
                    @if ($event->rsvp_deadline && ! $guest->hasResponded() && ! $guest->isPastDeadline())
                        <p class="mt-2 text-caption text-ink-muted">
                            Merci de répondre d'ici le {{ $event->rsvp_deadline->translatedFormat('j F Y') }}.
                        </p>
                    @endif
                </div>

                @if ($guest->isPastDeadline() && ! $guest->hasResponded())
                    <p class="mt-4 rounded-sm border border-line bg-surface-300 p-3 text-caption text-warning" role="status">
                        La date limite est dépassée. Votre réponse reste possible, mais sera signalée comme hors délai.
                    </p>
                @endif

                @if ($guest->hasResponded())
                    <section class="mt-8 border-t border-line pt-6" aria-labelledby="reponse-title" data-reveal>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 id="reponse-title" class="font-display text-heading text-ink">Votre réponse</h2>
                            <x-rsvp-status :guest="$guest" />
                        </div>

                        <dl class="mt-4 space-y-2 text-body">
                            @if ($guest->rsvp_status === \App\Enums\RsvpStatus::Attending)
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="text-ink-muted">Personnes :</dt>
                                    <dd class="text-ink">{{ $guest->rsvp_seats }}</dd>
                                </div>
                                @if ($guest->attendee_names)
                                    <div>
                                        <dt class="text-ink-muted">Prénoms :</dt>
                                        <dd class="text-ink">{!! nl2br(e($guest->attendee_names)) !!}</dd>
                                    </div>
                                @endif
                                @if ($guest->dietary_notes)
                                    <div>
                                        <dt class="text-ink-muted">Régime :</dt>
                                        <dd class="text-ink">{{ $guest->dietary_notes }}</dd>
                                    </div>
                                @endif
                            @endif
                            @if ($guest->rsvp_message)
                                <div>
                                    <dt class="text-ink-muted">Message :</dt>
                                    <dd class="text-ink">{!! nl2br(e($guest->rsvp_message)) !!}</dd>
                                </div>
                            @endif
                            <div class="flex flex-wrap gap-x-2">
                                <dt class="text-ink-muted">Réponse enregistrée le :</dt>
                                <dd class="text-ink">
                                    {{ $guest->rsvp_responded_at->translatedFormat('j F Y') }}
                                    à {{ $guest->rsvp_responded_at->format('H\hi') }}
                                </dd>
                            </div>
                        </dl>

                        @if ($guest->rsvp_is_late)
                            <p class="mt-3 text-caption text-warning">
                                Cette réponse a été transmise après la date limite.
                            </p>
                        @endif
                    </section>
                @endif

                @if ($guest->hasResponded() && $guest->rsvp_status === \App\Enums\RsvpStatus::Attending)
                    <section class="mt-8 border-t border-line pt-6 text-center" aria-labelledby="qr-title" data-reveal>
                        <h2 id="qr-title" class="font-display text-heading text-ink">Présentez ce code à l'entrée</h2>
                        <img
                            src="{{ $qrCode }}"
                            alt="QR code d'entrée pour {{ $guest->name }}"
                            width="192"
                            height="192"
                            class="mx-auto mt-4 h-48 w-48 rounded-sm border border-line bg-[#fffaf2] p-2"
                        >
                        <a
                            href="{{ $qrCode }}"
                            download="qr-entree-foyer-{{ $guest->id }}.svg"
                            class="btn btn-secondary mt-4"
                        >
                            Télécharger le QR
                        </a>
                        <p class="help mt-2">Un QR personnel par foyer. Il sert uniquement au pointage à l'entrée.</p>
                    </section>
                @endif

                @if ($guest->canRespond())
                    @if ($guest->hasResponded())
                        <details
                            class="group mt-8 border-t border-line pt-6"
                            data-reveal
                            @if ($errors->hasAny(['rsvp_status', 'rsvp_seats', 'attendee_names', 'dietary_notes', 'rsvp_message'])) open @endif
                        >
                            <summary class="btn btn-secondary flex w-full cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
                                Modifier votre réponse
                                <svg
                                    class="h-4 w-4 shrink-0 motion-safe:transition-transform motion-safe:duration-200 group-open:rotate-180"
                                    viewBox="0 0 16 16"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    aria-hidden="true"
                                >
                                    <path d="m4 6 4 4 4-4" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </summary>
                            @include('invitations._rsvp-form')
                        </details>
                    @else
                        <section class="mt-8 border-t border-line pt-6" aria-labelledby="rsvp-title" data-reveal>
                            <h2 id="rsvp-title" class="font-display text-heading text-ink">Répondre à l’invitation</h2>
                            @include('invitations._rsvp-form')
                        </section>
                    @endif
                @else
                    <p class="mt-8 border-t border-line pt-6 text-caption text-ink-muted">
                        La réponse n'est plus modifiable. Contactez les organisateurs pour toute correction.
                    </p>
                @endif

                @if ($guest->canRespond() && ! $guest->hasResponded())
                    <p class="mt-8 border-t border-line pt-6 text-caption text-ink-muted">
                        Votre QR d'entrée apparaîtra dès que vous aurez confirmé votre présence.
                    </p>
                @endif

                <section id="livre-dor" class="mt-8 border-t border-line pt-6" aria-labelledby="livre-dor-title" data-reveal>
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <p class="eyebrow">Livre d'or</p>
                            <h2 id="livre-dor-title" class="mt-1 font-display text-heading text-ink">Vos vœux et vos souvenirs</h2>
                        </div>
                        @if ($guestbookEntries->total() > 0)
                            <span class="status-pill">
                                {{ $guestbookEntries->total() }} message{{ $guestbookEntries->total() > 1 ? 's' : '' }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse ($guestbookEntries as $entry)
                            <article class="rounded-sm border border-line bg-surface-300 p-4">
                                <p class="whitespace-pre-line text-body text-ink">{{ $entry->message }}</p>
                                <p class="mt-3 text-caption text-ink-muted">
                                    {{ $entry->author_name }} · {{ $entry->created_at->translatedFormat('j F Y') }}
                                </p>
                            </article>
                        @empty
                            <p class="text-body text-ink-muted">Aucun message publié pour l'instant. Soyez le premier à signer.</p>
                        @endforelse
                    </div>

                    @if ($guestbookEntries->hasPages())
                        <div class="mt-4">{{ $guestbookEntries->links() }}</div>
                    @endif

                    <form method="POST" action="{{ route('guestbook.store', $guest->invitation_token) }}" class="mt-6 space-y-5">
                        @csrf

                        <div class="hidden" aria-hidden="true">
                            <label for="website_url">Site web</label>
                            <input id="website_url" name="website_url" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div>
                            <label for="author_name" class="label">Votre nom</label>
                            <input
                                id="author_name"
                                name="author_name"
                                type="text"
                                maxlength="80"
                                required
                                autocomplete="name"
                                class="field mt-1"
                                value="{{ old('author_name', $guest->name) }}"
                                @error('author_name') aria-invalid="true" aria-describedby="author_name-error" @enderror
                            >
                            @error('author_name')
                                <p id="author_name-error" class="error-text mt-1" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="guestbook_message" class="label">Votre message</label>
                            <textarea
                                id="guestbook_message"
                                name="message"
                                rows="4"
                                maxlength="1000"
                                required
                                class="field mt-1"
                                placeholder="Félicitations, souvenir, vœu…"
                                aria-describedby="{{ $errors->has('message') ? 'guestbook_message-help guestbook_message-error' : 'guestbook_message-help' }}"
                                @error('message') aria-invalid="true" @enderror
                            >{{ old('message') }}</textarea>
                            <p id="guestbook_message-help" class="help mt-1">1 000 caractères maximum. Votre message sera visible après validation par les organisateurs.</p>
                            @error('message')
                                <p id="guestbook_message-error" class="error-text mt-1" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-secondary w-full">Signer le livre d'or</button>
                    </form>
                </section>

                <div class="mt-10 text-center" aria-hidden="true">
                    <div class="rule-gold mx-auto w-16"></div>
                    <p class="mt-4 font-script text-script text-gold">Avec joie</p>
                </div>
            </div>
        </article>

        <p class="mt-6 text-center text-caption text-ink-muted">
            @if ($isDemo)
                Cette invitation est une démonstration publique. Les réponses sont fictives.
            @else
                Ce lien est personnel et confidentiel. Merci de ne pas le partager.
            @endif
        </p>
    </div>
@endsection
