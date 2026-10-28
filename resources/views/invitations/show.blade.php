@extends('layouts.invitation')

@section('title', 'Invitation — '.$event->name.' — Hub Événement')

@section('content')
    <div class="{{ $event->type->accentClass() }}">
        <article class="card card-invitation overflow-hidden">
            <div class="h-1 bg-accent" aria-hidden="true"></div>

            <div class="p-6 sm:p-10">
                <p class="eyebrow">{{ $event->type->label() }}</p>
                <h1 class="mt-2 font-display text-heading text-ink sm:text-display-lg">
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
                </div>

                <div class="mt-8 border-t border-line pt-6">
                    <p class="eyebrow">Invitation pour</p>
                    <p class="mt-1 font-display text-heading text-ink">{{ $guest->name }}</p>
                    <p class="text-caption text-ink-muted">
                        {{ $guest->allowed_seats }} place{{ $guest->allowed_seats > 1 ? 's' : '' }} réservée{{ $guest->allowed_seats > 1 ? 's' : '' }} pour votre foyer.
                    </p>
                    @if ($event->rsvp_deadline)
                        <p class="mt-2 text-caption text-ink-muted">
                            Merci de répondre avant le {{ $event->rsvp_deadline->translatedFormat('j F Y') }}.
                        </p>
                    @endif
                </div>

                <section class="mt-8 border-t border-line pt-6 text-center" aria-labelledby="qr-title">
                    <p class="eyebrow">Votre QR d'entrée</p>
                    <h2 id="qr-title" class="mt-1 font-display text-heading text-ink">Présentez ce code à l'entrée</h2>
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

                @if ($guest->isPastDeadline() && ! $guest->hasResponded())
                    <p class="mt-4 rounded-sm border border-line bg-surface-300 p-3 text-caption text-warning" role="status">
                        La date limite est dépassée. Votre réponse reste possible, mais sera signalée comme hors délai.
                    </p>
                @endif

                @if ($guest->hasResponded())
                    <section class="mt-8 border-t border-line pt-6" aria-labelledby="reponse-title">
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

                @if ($guest->canRespond())
                    <section class="mt-8 border-t border-line pt-6" aria-labelledby="rsvp-title">
                        <h2 id="rsvp-title" class="font-display text-heading text-ink">
                            {{ $guest->hasResponded() ? 'Modifier votre réponse' : 'Répondre à l’invitation' }}
                        </h2>

                        <form method="POST" action="{{ route('invitations.rsvp', $guest->invitation_token) }}" class="mt-4 space-y-5" data-rsvp-form>
                            @csrf

                            <fieldset>
                                <legend class="label">Serez-vous présent ?</legend>
                                <div class="mt-2 space-y-2">
                                    <label for="rsvp_attending" class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-sm border border-line-strong bg-surface-300 px-3 py-2">
                                        <input
                                            id="rsvp_attending"
                                            name="rsvp_status"
                                            type="radio"
                                            value="attending"
                                            required
                                            @checked(old('rsvp_status', $guest->rsvp_status->value) === \App\Enums\RsvpStatus::Attending->value)
                                        >
                                        <span class="text-body text-ink">Oui, nous serons présents</span>
                                    </label>
                                    <label for="rsvp_declined" class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-sm border border-line-strong bg-surface-300 px-3 py-2">
                                        <input
                                            id="rsvp_declined"
                                            name="rsvp_status"
                                            type="radio"
                                            value="declined"
                                            @checked(old('rsvp_status', $guest->rsvp_status->value) === \App\Enums\RsvpStatus::Declined->value)
                                        >
                                        <span class="text-body text-ink">Non, nous ne pourrons pas venir</span>
                                    </label>
                                </div>
                                @error('rsvp_status')
                                    <p class="error-text mt-1">{{ $message }}</p>
                                @enderror
                            </fieldset>

                            <div data-attendance-fields class="space-y-5">
                                <div>
                                    <label for="rsvp_seats" class="label">Nombre de personnes</label>
                                    <select id="rsvp_seats" name="rsvp_seats" class="field mt-1">
                                        @for ($seats = 1; $seats <= $guest->allowed_seats; $seats++)
                                            <option value="{{ $seats }}" @selected((int) old('rsvp_seats', $guest->rsvp_seats ?? 1) === $seats)>
                                                {{ $seats }} personne{{ $seats > 1 ? 's' : '' }}
                                            </option>
                                        @endfor
                                    </select>
                                    <p class="help mt-1">Dans la limite des {{ $guest->allowed_seats }} places de votre foyer.</p>
                                    @error('rsvp_seats')
                                        <p class="error-text mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="attendee_names" class="label">
                                        Prénoms des personnes <span class="font-normal text-ink-muted">(facultatif)</span>
                                    </label>
                                    <textarea id="attendee_names" name="attendee_names" rows="3" maxlength="1000" class="field mt-1">{{ old('attendee_names', $guest->attendee_names) }}</textarea>
                                    <p class="help mt-1">Un prénom par ligne, au plus le nombre de personnes indiqué.</p>
                                    @error('attendee_names')
                                        <p class="error-text mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="dietary_notes" class="label">
                                    Régime alimentaire <span class="font-normal text-ink-muted">(facultatif)</span>
                                </label>
                                <textarea id="dietary_notes" name="dietary_notes" rows="2" maxlength="500" class="field mt-1" placeholder="Allergies, repas sans porc, végétarien…">{{ old('dietary_notes', $guest->dietary_notes) }}</textarea>
                                @error('dietary_notes')
                                    <p class="error-text mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="rsvp_message" class="label">
                                    Un mot pour les organisateurs <span class="font-normal text-ink-muted">(facultatif)</span>
                                </label>
                                <textarea id="rsvp_message" name="rsvp_message" rows="3" maxlength="1000" class="field mt-1">{{ old('rsvp_message', $guest->rsvp_message) }}</textarea>
                                @error('rsvp_message')
                                    <p class="error-text mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-full">
                                {{ $guest->hasResponded() ? 'Enregistrer ma réponse' : 'Envoyer ma réponse' }}
                            </button>
                        </form>
                    </section>
                @else
                    <p class="mt-8 border-t border-line pt-6 text-caption text-ink-muted">
                        La réponse n'est plus modifiable. Contactez les organisateurs pour toute correction.
                    </p>
                @endif

                <section id="livre-dor" class="mt-8 border-t border-line pt-6" aria-labelledby="livre-dor-title">
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
                            <input id="author_name" name="author_name" type="text" maxlength="80" required class="field mt-1" value="{{ old('author_name', $guest->name) }}">
                            @error('author_name')
                                <p class="error-text mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="guestbook_message" class="label">Votre message</label>
                            <textarea id="guestbook_message" name="message" rows="4" maxlength="1000" required class="field mt-1" placeholder="Félicitations, souvenir, vœu…">{{ old('message') }}</textarea>
                            <p class="help mt-1">1 000 caractères maximum. Votre message sera visible après validation par les organisateurs.</p>
                            @error('message')
                                <p class="error-text mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-full">Signer le livre d'or</button>
                    </form>
                </section>
            </div>
        </article>

        <p class="mt-6 text-center text-caption text-ink-muted">
            Ce lien est personnel et confidentiel. Merci de ne pas le partager.
        </p>
    </div>
@endsection
