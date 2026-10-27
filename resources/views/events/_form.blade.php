@php
    $event = $event ?? null;
    $currentType = old('type', $event?->type?->value);
@endphp

<form method="POST" action="{{ $action }}" class="card card-invitation mt-8 p-6 sm:p-8">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="label">Nom de l'événement</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $event?->name) }}"
                maxlength="160"
                required
                class="field mt-1 @error('name') field-error @enderror"
                @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
            >
            @error('name')
                <p id="name-error" class="error-text mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="type" class="label">Type d'événement</label>
            <select
                id="type"
                name="type"
                required
                class="field mt-1 @error('type') field-error @enderror"
                @error('type') aria-invalid="true" aria-describedby="type-error" @enderror
            >
                @foreach (\App\Enums\EventType::cases() as $type)
                    <option value="{{ $type->value }}" @selected($currentType === $type->value)>
                        {{ $type->label() }}
                    </option>
                @endforeach
            </select>
            @error('type')
                <p id="type-error" class="error-text mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="starts_at" class="label">Date et heure</label>
            <input
                id="starts_at"
                name="starts_at"
                type="datetime-local"
                value="{{ old('starts_at', $event?->starts_at?->format('Y-m-d\TH:i')) }}"
                required
                class="field mt-1 @error('starts_at') field-error @enderror"
                @error('starts_at') aria-invalid="true" aria-describedby="starts_at-error" @enderror
            >
            @error('starts_at')
                <p id="starts_at-error" class="error-text mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="venue_name" class="label">Lieu</label>
            <input
                id="venue_name"
                name="venue_name"
                type="text"
                value="{{ old('venue_name', $event?->venue_name) }}"
                maxlength="160"
                required
                placeholder="Espace Latrille, salle familiale…"
                class="field mt-1 @error('venue_name') field-error @enderror"
                @error('venue_name') aria-invalid="true" aria-describedby="venue_name-error" @enderror
            >
            @error('venue_name')
                <p id="venue_name-error" class="error-text mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="venue_address" class="label">
                Adresse <span class="font-normal text-ink-muted">(facultatif)</span>
            </label>
            <input
                id="venue_address"
                name="venue_address"
                type="text"
                value="{{ old('venue_address', $event?->venue_address) }}"
                maxlength="255"
                placeholder="Quartier, ville"
                class="field mt-1 @error('venue_address') field-error @enderror"
                @error('venue_address') aria-invalid="true" aria-describedby="venue_address-error" @enderror
            >
            @error('venue_address')
                <p id="venue_address-error" class="error-text mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="rsvp_deadline" class="label">
                Date limite de réponse <span class="font-normal text-ink-muted">(facultatif)</span>
            </label>
            <input
                id="rsvp_deadline"
                name="rsvp_deadline"
                type="date"
                value="{{ old('rsvp_deadline', $event?->rsvp_deadline?->format('Y-m-d')) }}"
                class="field mt-1 @error('rsvp_deadline') field-error @enderror"
                @error('rsvp_deadline') aria-invalid="true" aria-describedby="rsvp_deadline-error" @enderror
            >
            <p class="help mt-1">Elle doit précéder la date de l'événement.</p>
            @error('rsvp_deadline')
                <p id="rsvp_deadline-error" class="error-text mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-8 flex flex-wrap gap-3">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ $cancelUrl }}" class="btn btn-secondary">Annuler</a>
    </div>
</form>
