<form method="POST" action="{{ route('invitations.rsvp', $guest->invitation_token) }}" class="mt-4 space-y-5" data-rsvp-form>
    @csrf

    <fieldset @error('rsvp_status') aria-describedby="rsvp_status-error" @enderror>
        <legend class="label">Serez-vous présent ?</legend>
        <div class="mt-2 space-y-2">
            <label for="rsvp_attending" class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-sm border border-line-strong bg-surface-300 px-3 py-2">
                <input
                    id="rsvp_attending"
                    name="rsvp_status"
                    type="radio"
                    value="attending"
                    required
                    @if ($errors->has('rsvp_status')) autofocus @endif
                    @error('rsvp_status') aria-invalid="true" @enderror
                    @checked(old('rsvp_status', $guest->rsvp_status->value) === \App\Enums\RsvpStatus::Attending->value)
                >
                <span class="text-body text-ink">{{ $guest->allowed_seats > 1 ? 'Oui, nous serons présents' : 'Oui, je serai présent' }}</span>
            </label>
            <label for="rsvp_declined" class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-sm border border-line-strong bg-surface-300 px-3 py-2">
                <input
                    id="rsvp_declined"
                    name="rsvp_status"
                    type="radio"
                    value="declined"
                    @error('rsvp_status') aria-invalid="true" @enderror
                    @checked(old('rsvp_status', $guest->rsvp_status->value) === \App\Enums\RsvpStatus::Declined->value)
                >
                <span class="text-body text-ink">{{ $guest->allowed_seats > 1 ? 'Non, nous ne pourrons pas venir' : 'Non, je ne pourrai pas venir' }}</span>
            </label>
        </div>
        @error('rsvp_status')
            <p id="rsvp_status-error" class="error-text mt-1" role="alert">{{ $message }}</p>
        @enderror
    </fieldset>

    <div data-attendance-fields class="space-y-5">
        <div>
            <label for="rsvp_seats" class="label">Nombre de personnes</label>
            <select
                id="rsvp_seats"
                name="rsvp_seats"
                class="field mt-1"
                @if ($errors->has('rsvp_seats')) autofocus @endif
                aria-describedby="{{ $errors->has('rsvp_seats') ? 'rsvp_seats-help rsvp_seats-error' : 'rsvp_seats-help' }}"
                @error('rsvp_seats') aria-invalid="true" @enderror
            >
                @for ($seats = 1; $seats <= $guest->allowed_seats; $seats++)
                    <option value="{{ $seats }}" @selected((int) old('rsvp_seats', $guest->rsvp_seats ?? 1) === $seats)>
                        {{ $seats }} personne{{ $seats > 1 ? 's' : '' }}
                    </option>
                @endfor
            </select>
            <p id="rsvp_seats-help" class="help mt-1">Dans la limite des {{ $guest->allowed_seats }} places de votre foyer.</p>
            @error('rsvp_seats')
                <p id="rsvp_seats-error" class="error-text mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="attendee_names" class="label">
                Prénoms des personnes <span class="font-normal text-ink-muted">(facultatif)</span>
            </label>
            <textarea
                id="attendee_names"
                name="attendee_names"
                rows="3"
                maxlength="1000"
                class="field mt-1"
                @if ($errors->has('attendee_names')) autofocus @endif
                aria-describedby="{{ $errors->has('attendee_names') ? 'attendee_names-help attendee_names-error' : 'attendee_names-help' }}"
                @error('attendee_names') aria-invalid="true" @enderror
            >{{ old('attendee_names', $guest->attendee_names) }}</textarea>
            <p id="attendee_names-help" class="help mt-1">Un prénom par ligne, au plus le nombre de personnes indiqué.</p>
            @error('attendee_names')
                <p id="attendee_names-error" class="error-text mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="dietary_notes" class="label">
                Régime alimentaire <span class="font-normal text-ink-muted">(facultatif)</span>
            </label>
            <textarea
                id="dietary_notes"
                name="dietary_notes"
                rows="2"
                maxlength="500"
                class="field mt-1"
                placeholder="Allergies, repas sans porc, végétarien…"
                @if ($errors->has('dietary_notes')) autofocus @endif
                @error('dietary_notes') aria-invalid="true" aria-describedby="dietary_notes-error" @enderror
            >{{ old('dietary_notes', $guest->dietary_notes) }}</textarea>
            @error('dietary_notes')
                <p id="dietary_notes-error" class="error-text mt-1" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="rsvp_message" class="label">
            Un mot pour les organisateurs <span class="font-normal text-ink-muted">(facultatif)</span>
        </label>
        <textarea
            id="rsvp_message"
            name="rsvp_message"
            rows="3"
            maxlength="1000"
            class="field mt-1"
            @if ($errors->has('rsvp_message')) autofocus @endif
            @error('rsvp_message') aria-invalid="true" aria-describedby="rsvp_message-error" @enderror
        >{{ old('rsvp_message', $guest->rsvp_message) }}</textarea>
        @error('rsvp_message')
            <p id="rsvp_message-error" class="error-text mt-1" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary w-full">
        {{ $guest->hasResponded() ? 'Enregistrer ma réponse' : 'Envoyer ma réponse' }}
    </button>
</form>
