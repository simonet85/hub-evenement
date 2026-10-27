@extends('layouts.app')

@section('title', $event->name.' — Hub Événement')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10 {{ $event->type->accentClass() }}">
        <nav aria-label="Fil d'Ariane" class="text-caption text-ink-muted">
            <a href="{{ route('dashboard') }}" class="hover:text-ink">Mon espace</a>
            <span aria-hidden="true"> / </span>
            <span aria-current="page">{{ $event->name }}</span>
        </nav>

        <header class="mt-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="eyebrow">{{ $event->type->label() }}</p>
                <h1 class="mt-1 font-display text-display-lg text-ink">{{ $event->name }}</h1>
                <p class="mt-3 text-body-lg text-ink-muted">
                    {{ $event->starts_at->translatedFormat('l j F Y') }} à {{ $event->starts_at->format('H\hi') }}
                </p>
                <p class="text-body text-ink-muted">
                    {{ $event->venue_name }}@if ($event->venue_address) — {{ $event->venue_address }}@endif
                </p>
                @if ($event->dress_code)
                    <p class="mt-1 text-caption text-ink-muted">Code vestimentaire : {{ $event->dress_code }}</p>
                @endif
                @if ($event->rsvp_deadline)
                    <p class="mt-1 text-caption text-ink-muted">
                        Réponses attendues avant le {{ $event->rsvp_deadline->translatedFormat('j F Y') }}
                    </p>
                @endif
                @cannot('update', $event)
                    <p class="status-pill mt-3">Co-organisateur</p>
                @endcannot
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('check-in.scan', $event) }}" class="btn btn-primary">
                    Scanner les entrées
                </a>
                <a href="{{ route('reminders.index', $event) }}" class="btn btn-secondary">
                    Relancer les foyers
                </a>
                @can('update', $event)
                    <a href="{{ route('events.edit', $event) }}" class="btn btn-secondary">
                        Modifier l'événement
                    </a>
                @endcan
            </div>
        </header>

        <section aria-label="Chiffres clés" class="mt-8 grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <div class="card p-4">
                <p class="eyebrow">Foyers</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $stats['households'] }}</p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Places prévues</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $stats['seats'] }}</p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Réponses</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $stats['responded'] }}</p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Couverts</p>
                <p class="mt-1 font-display text-heading text-accent">{{ $stats['coming'] }}</p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Foyers pointés</p>
                <p class="mt-1 font-display text-heading text-accent">
                    {{ $stats['checked_in_households'] }} / {{ $stats['households'] }}
                </p>
            </div>
            <div class="card p-4">
                <p class="eyebrow">Couverts pointés</p>
                <p class="mt-1 font-display text-heading text-accent">
                    {{ $stats['checked_in_seats'] }} / {{ $stats['seats'] }}
                </p>
            </div>
        </section>

        <div class="mt-10 grid gap-8 lg:grid-cols-[1.4fr_1fr]">
            <section aria-labelledby="foyers-title" class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="foyers-title" class="font-display text-heading text-ink">Foyers</h2>
                    <span class="status-pill">
                        {{ $event->guests->count() }} foyer{{ $event->guests->count() > 1 ? 's' : '' }}
                    </span>
                </div>

                @if ($event->guests->isEmpty())
                    <p class="mt-3 text-body text-ink-muted">
                        Aucun foyer pour l'instant. Ajoutez-en un ou importez votre liste CSV.
                    </p>
                @else
                    <ul role="list" class="mt-4 divide-y divide-line">
                        @foreach ($event->guests as $guest)
                            <li class="py-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-label text-ink">{{ $guest->name }}</p>
                                        <p class="text-caption text-ink-muted">
                                            {{ collect([$guest->email, $guest->phone])->filter()->implode(' · ') ?: 'Sans contact' }}
                                        </p>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <x-rsvp-status :guest="$guest" />
                                            @if ($guest->hasCheckedIn())
                                                <span class="status-pill status-pill-success">
                                                    Pointé · {{ $guest->checked_in_at->format('H\hi') }}
                                                </span>
                                            @endif
                                        </div>
                                        @if ($guest->hasCheckedIn())
                                            <p class="mt-2 text-caption text-success">
                                                Entrée enregistrée le {{ $guest->checked_in_at->translatedFormat('j F Y \à H\hi') }}@if ($guest->checkedInBy)
                                                    par {{ $guest->checkedInBy->name }}
                                                @endif.
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="status-pill">
                                            {{ $guest->allowed_seats }} place{{ $guest->allowed_seats > 1 ? 's' : '' }}
                                        </span>
                                        <form method="POST" action="{{ route('guests.destroy', [$event, $guest]) }}" onsubmit="return confirm('Supprimer ce foyer ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-caption font-semibold text-danger hover:underline">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                @if ($guest->hasResponded())
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-caption font-semibold text-accent">Détail de la réponse</summary>
                                        <div class="mt-2 space-y-1 text-caption text-ink-muted">
                                            <p>
                                                <span class="font-semibold text-ink">Réponse du :</span>
                                                {{ $guest->rsvp_responded_at->translatedFormat('j F Y') }}
                                                à {{ $guest->rsvp_responded_at->format('H\hi') }}
                                            </p>
                                            @if ($guest->attendee_names)
                                                <p>
                                                    <span class="font-semibold text-ink">Prénoms :</span>
                                                    <span class="whitespace-pre-line">{{ $guest->attendee_names }}</span>
                                                </p>
                                            @endif
                                            @if ($guest->dietary_notes)
                                                <p><span class="font-semibold text-ink">Régime :</span> {{ $guest->dietary_notes }}</p>
                                            @endif
                                            @if ($guest->rsvp_message)
                                                <p><span class="font-semibold text-ink">Message :</span> {{ $guest->rsvp_message }}</p>
                                            @endif
                                        </div>
                                    </details>
                                @endif

                                <details class="mt-2">
                                    <summary class="cursor-pointer text-caption font-semibold text-accent">Invitation et QR d'entrée</summary>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex flex-wrap gap-2">
                                            <input
                                                id="guest-link-{{ $guest->id }}"
                                                type="text"
                                                readonly
                                                value="{{ $guest->invitationUrl() }}"
                                                class="field min-w-0 flex-1 text-caption"
                                                onclick="this.select()"
                                            >
                                            <button type="button" class="btn btn-secondary" data-copy-target="guest-link-{{ $guest->id }}">
                                                Copier
                                            </button>
                                        </div>
                                        <a href="{{ route('check-in.qr', [$event, $guest]) }}" class="inline-block text-caption font-semibold text-accent hover:underline">
                                            Télécharger le QR d'entrée (SVG)
                                        </a>
                                        <form method="POST" action="{{ route('guests.invitation.regenerate', [$event, $guest]) }}" onsubmit="return confirm('Regénérer le lien ? L’ancien ne fonctionnera plus.')">
                                            @csrf
                                            <button type="submit" class="text-caption font-semibold text-danger hover:underline">
                                                Regénérer le lien
                                            </button>
                                        </form>
                                    </div>
                                </details>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <aside class="space-y-8">
                <section aria-labelledby="ajout-title" class="card p-6">
                    <h2 id="ajout-title" class="font-display text-heading text-ink">Ajouter un foyer</h2>

                    <form method="POST" action="{{ route('guests.store', $event) }}" class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label for="guest_name" class="label">Nom du foyer</label>
                            <input
                                id="guest_name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                maxlength="160"
                                required
                                placeholder="Famille Koné"
                                class="field mt-1 @error('name') field-error @enderror"
                                @error('name') aria-invalid="true" aria-describedby="guest_name-error" @enderror
                            >
                            @error('name')
                                <p id="guest_name-error" class="error-text mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="guest_email" class="label">
                                E-mail <span class="font-normal text-ink-muted">(facultatif)</span>
                            </label>
                            <input
                                id="guest_email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                maxlength="190"
                                placeholder="foyer@example.com"
                                class="field mt-1 @error('email') field-error @enderror"
                                @error('email') aria-invalid="true" aria-describedby="guest_email-error" @enderror
                            >
                            @error('email')
                                <p id="guest_email-error" class="error-text mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="guest_phone" class="label">
                                Téléphone <span class="font-normal text-ink-muted">(facultatif)</span>
                            </label>
                            <input
                                id="guest_phone"
                                name="phone"
                                type="tel"
                                value="{{ old('phone') }}"
                                maxlength="30"
                                placeholder="+225 07 00 00 00 00"
                                class="field mt-1 @error('phone') field-error @enderror"
                                @error('phone') aria-invalid="true" aria-describedby="guest_phone-error" @enderror
                            >
                            @error('phone')
                                <p id="guest_phone-error" class="error-text mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="allowed_seats" class="label">Places</label>
                            <input
                                id="allowed_seats"
                                name="allowed_seats"
                                type="number"
                                value="{{ old('allowed_seats', 2) }}"
                                min="1"
                                max="20"
                                required
                                class="field mt-1 @error('allowed_seats') field-error @enderror"
                                @error('allowed_seats') aria-invalid="true" aria-describedby="allowed_seats-error" @enderror
                            >
                            <p class="help mt-1">Entre 1 et 20 places par foyer.</p>
                            @error('allowed_seats')
                                <p id="allowed_seats-error" class="error-text mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-full">Ajouter le foyer</button>
                    </form>
                </section>

                <section aria-labelledby="import-title" class="card p-6">
                    <h2 id="import-title" class="font-display text-heading text-ink">Importer un CSV</h2>
                    <p class="mt-2 text-caption text-ink-muted">
                        Colonnes : nom, e-mail, téléphone, places. Séparateur virgule ou point-virgule,
                        en-tête facultative. Lignes invalides ignorées et signalées.
                    </p>

                    <form method="POST" action="{{ route('guests.import', $event) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                        @csrf

                        <div>
                            <label for="import_file" class="label">Fichier CSV (2 Mo max)</label>
                            <input
                                id="import_file"
                                name="file"
                                type="file"
                                accept=".csv,.txt,text/csv,text/plain"
                                required
                                class="field mt-1 @error('file') field-error @enderror"
                                @error('file') aria-invalid="true" aria-describedby="import_file-error" @enderror
                            >
                            @error('file')
                                <p id="import_file-error" class="error-text mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-secondary w-full">Importer la liste</button>
                    </form>

                    @if (session('import_errors'))
                        <div class="mt-4 rounded-sm border border-line bg-surface-300 p-3" role="alert">
                            <p class="text-label text-warning">Lignes ignorées</p>
                            <ul class="mt-1 space-y-1 text-caption text-ink-muted">
                                @foreach (session('import_errors') as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>

                @can('update', $event)
                    <section aria-labelledby="coorganisateurs-title" class="card p-6">
                        <h2 id="coorganisateurs-title" class="font-display text-heading text-ink">Co-organisateurs</h2>
                        <p class="mt-2 text-caption text-ink-muted">
                            Ils consultent l'événement et gèrent les foyers, sans modifier les réglages.
                            La personne doit déjà avoir un compte Hub Événement.
                        </p>

                        <ul role="list" class="mt-4 divide-y divide-line">
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <span class="text-body text-ink">{{ $event->owner->name }}</span>
                                <span class="status-pill">Propriétaire</span>
                            </li>
                            @foreach ($event->roles as $role)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                    <div>
                                        <p class="text-body text-ink">{{ $role->user->name }}</p>
                                        <p class="text-caption text-ink-muted">{{ $role->user->email }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="status-pill">Co-organisateur</span>
                                        <form method="POST" action="{{ route('co-organizers.destroy', [$event, $role->user]) }}" onsubmit="return confirm('Retirer ce co-organisateur ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-caption font-semibold text-danger hover:underline">
                                                Retirer
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <form method="POST" action="{{ route('co-organizers.store', $event) }}" class="mt-4 space-y-3">
                            @csrf

                            <div>
                                <label for="co_email" class="label">Ajouter par e-mail</label>
                                <input
                                    id="co_email"
                                    name="co_email"
                                    type="email"
                                    value="{{ old('co_email') }}"
                                    required
                                    placeholder="amis@example.com"
                                    class="field mt-1 @error('co_email') field-error @enderror"
                                    @error('co_email') aria-invalid="true" aria-describedby="co_email-error" @enderror
                                >
                                @error('co_email')
                                    <p id="co_email-error" class="error-text mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-secondary w-full">Ajouter le co-organisateur</button>
                        </form>
                    </section>
                @endcan
            </aside>
        </div>

        <section aria-labelledby="programme-title" class="card mt-8 p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="programme-title" class="font-display text-heading text-ink">Programme</h2>
                    <p class="mt-1 text-caption text-ink-muted">
                        {{ $event->programSteps->count() }} étape{{ $event->programSteps->count() > 1 ? 's' : '' }}
                        @if ($event->dress_code)
                            · code vestimentaire renseigné
                        @endif
                    </p>
                </div>
                @if ($event->guests->isNotEmpty())
                    <a
                        href="{{ route('invitations.programme', $event->guests->first()->invitation_token) }}"
                        class="btn btn-secondary"
                        target="_blank"
                        rel="noopener"
                    >
                        Voir la page programme
                    </a>
                @endif
            </div>

            @if ($event->programSteps->isEmpty())
                <p class="mt-4 text-body text-ink-muted">
                    Aucune étape pour l'instant. Ajoutez la cérémonie, la réception, le dîner…
                </p>
            @else
                <ol role="list" class="mt-6 divide-y divide-line">
                    @foreach ($event->programSteps as $step)
                        <li class="py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-caption font-semibold text-accent">
                                        {{ $step->start_time }}@if ($step->end_time) – {{ $step->end_time }}@endif
                                    </p>
                                    <p class="text-label text-ink">{{ $step->title }}</p>
                                    @if ($step->location)
                                        <p class="text-caption text-ink-muted">{{ $step->location }}</p>
                                    @endif
                                    @if ($step->description)
                                        <p class="mt-1 text-caption text-ink-muted">{{ $step->description }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="POST" action="{{ route('program-steps.move', [$event, $step]) }}">
                                        @csrf
                                        <input type="hidden" name="direction" value="up">
                                        <button
                                            type="submit"
                                            class="text-caption font-semibold text-accent hover:underline disabled:opacity-40"
                                            @disabled($loop->first)
                                            aria-label="Monter {{ $step->title }}"
                                        >
                                            Monter
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('program-steps.move', [$event, $step]) }}">
                                        @csrf
                                        <input type="hidden" name="direction" value="down">
                                        <button
                                            type="submit"
                                            class="text-caption font-semibold text-accent hover:underline disabled:opacity-40"
                                            @disabled($loop->last)
                                            aria-label="Descendre {{ $step->title }}"
                                        >
                                            Descendre
                                        </button>
                                    </form>
                                    <form
                                        method="POST"
                                        action="{{ route('program-steps.destroy', [$event, $step]) }}"
                                        onsubmit="return confirm('Supprimer cette étape ?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-caption font-semibold text-danger hover:underline">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <details class="mt-2">
                                <summary class="cursor-pointer text-caption font-semibold text-accent">
                                    Modifier l'étape
                                </summary>
                                <form
                                    method="POST"
                                    action="{{ route('program-steps.update', [$event, $step]) }}"
                                    class="mt-3 grid gap-3 sm:grid-cols-2"
                                >
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <label for="step_{{ $step->id }}_start_time" class="label">Début</label>
                                        <input
                                            id="step_{{ $step->id }}_start_time"
                                            name="start_time"
                                            type="time"
                                            value="{{ old('start_time', $step->start_time) }}"
                                            required
                                            class="field mt-1"
                                        >
                                    </div>

                                    <div>
                                        <label for="step_{{ $step->id }}_end_time" class="label">
                                            Fin <span class="font-normal text-ink-muted">(facultatif)</span>
                                        </label>
                                        <input
                                            id="step_{{ $step->id }}_end_time"
                                            name="end_time"
                                            type="time"
                                            value="{{ old('end_time', $step->end_time) }}"
                                            class="field mt-1"
                                        >
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="step_{{ $step->id }}_title" class="label">Titre</label>
                                        <input
                                            id="step_{{ $step->id }}_title"
                                            name="title"
                                            type="text"
                                            value="{{ old('title', $step->title) }}"
                                            maxlength="120"
                                            required
                                            class="field mt-1"
                                        >
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="step_{{ $step->id }}_location" class="label">
                                            Lieu <span class="font-normal text-ink-muted">(facultatif)</span>
                                        </label>
                                        <input
                                            id="step_{{ $step->id }}_location"
                                            name="location"
                                            type="text"
                                            value="{{ old('location', $step->location) }}"
                                            maxlength="120"
                                            class="field mt-1"
                                        >
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="step_{{ $step->id }}_description" class="label">
                                            Description <span class="font-normal text-ink-muted">(facultatif)</span>
                                        </label>
                                        <textarea
                                            id="step_{{ $step->id }}_description"
                                            name="description"
                                            rows="2"
                                            maxlength="500"
                                            class="field mt-1"
                                        >{{ old('description', $step->description) }}</textarea>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <button type="submit" class="btn btn-primary">Enregistrer l'étape</button>
                                    </div>
                                </form>
                            </details>
                        </li>
                    @endforeach
                </ol>
            @endif

            <form
                method="POST"
                action="{{ route('program-steps.store', $event) }}"
                class="mt-6 grid gap-3 border-t border-line pt-6 sm:grid-cols-2"
            >
                @csrf
                <p class="text-label text-ink sm:col-span-2">Ajouter une étape</p>

                <div>
                    <label for="step_start_time" class="label">Début</label>
                    <input
                        id="step_start_time"
                        name="start_time"
                        type="time"
                        value="{{ old('start_time') }}"
                        required
                        class="field mt-1 @error('start_time') field-error @enderror"
                    >
                    @error('start_time')
                        <p class="error-text mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="step_end_time" class="label">
                        Fin <span class="font-normal text-ink-muted">(facultatif)</span>
                    </label>
                    <input
                        id="step_end_time"
                        name="end_time"
                        type="time"
                        value="{{ old('end_time') }}"
                        class="field mt-1 @error('end_time') field-error @enderror"
                    >
                    @error('end_time')
                        <p class="error-text mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="step_title" class="label">Titre</label>
                    <input
                        id="step_title"
                        name="title"
                        type="text"
                        value="{{ old('title') }}"
                        maxlength="120"
                        required
                        placeholder="Cérémonie, réception, dîner…"
                        class="field mt-1 @error('title') field-error @enderror"
                    >
                    @error('title')
                        <p class="error-text mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="step_location" class="label">
                        Lieu <span class="font-normal text-ink-muted">(facultatif)</span>
                    </label>
                    <input
                        id="step_location"
                        name="location"
                        type="text"
                        value="{{ old('location') }}"
                        maxlength="120"
                        class="field mt-1"
                    >
                </div>

                <div class="sm:col-span-2">
                    <label for="step_description" class="label">
                        Description <span class="font-normal text-ink-muted">(facultatif)</span>
                    </label>
                    <textarea
                        id="step_description"
                        name="description"
                        rows="2"
                        maxlength="500"
                        class="field mt-1"
                    >{{ old('description') }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <button type="submit" class="btn btn-secondary">Ajouter l'étape</button>
                </div>
            </form>

            @if ($event->guests->isEmpty())
                <p class="mt-4 text-caption text-ink-muted">
                    Ajoutez un foyer pour partager la page programme avec vos invités.
                </p>
            @endif
        </section>
    </div>
@endsection
