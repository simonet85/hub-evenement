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

        <div class="sm:col-span-2">
            <label for="dress_code" class="label">
                Code vestimentaire <span class="font-normal text-ink-muted">(facultatif)</span>
            </label>
            <input
                id="dress_code"
                name="dress_code"
                type="text"
                value="{{ old('dress_code', $event?->dress_code) }}"
                maxlength="255"
                placeholder="Tenue de cérémonie, couleurs claires…"
                class="field mt-1 @error('dress_code') field-error @enderror"
                @error('dress_code') aria-invalid="true" aria-describedby="dress_code-error" @enderror
            >
            @error('dress_code')
                <p id="dress_code-error" class="error-text mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2">
            <span class="label">Repère sur la carte</span>
            <p class="help mt-1">Cliquez sur la carte pour placer le lieu de l'événement.</p>
            <div
                data-map-picker
                data-lat-input="latitude"
                data-lng-input="longitude"
                class="mt-2 h-72 w-full rounded-sm border border-line bg-surface-300"
                role="application"
                aria-label="Carte OpenStreetMap pour placer le lieu"
            ></div>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <p data-map-status class="text-caption text-ink-muted">Aucun repère placé.</p>
                <button type="button" data-map-clear class="text-caption font-semibold text-danger hover:underline">
                    Retirer le repère
                </button>
            </div>
            <noscript>
                <p class="help mt-2">La carte interactive nécessite JavaScript. Saisissez les coordonnées ci-dessous.</p>
            </noscript>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="latitude" class="label">Latitude</label>
                    <input
                        id="latitude"
                        name="latitude"
                        type="number"
                        step="0.000001"
                        min="-90"
                        max="90"
                        value="{{ old('latitude', $event?->latitude) }}"
                        class="field mt-1 @error('latitude') field-error @enderror"
                        @error('latitude') aria-invalid="true" aria-describedby="latitude-error" @enderror
                    >
                    @error('latitude')
                        <p id="latitude-error" class="error-text mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="longitude" class="label">Longitude</label>
                    <input
                        id="longitude"
                        name="longitude"
                        type="number"
                        step="0.000001"
                        min="-180"
                        max="180"
                        value="{{ old('longitude', $event?->longitude) }}"
                        class="field mt-1 @error('longitude') field-error @enderror"
                        @error('longitude') aria-invalid="true" aria-describedby="longitude-error" @enderror
                    >
                    @error('longitude')
                        <p id="longitude-error" class="error-text mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <p class="help mt-1">Facultatif. Sans repère, la carte n'apparaît pas sur la page programme.</p>
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
