@extends('layouts.invitation')

@section('title', 'Programme — '.$event->name.' — Hub Événement')

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
                <p class="mt-2 text-caption text-ink-muted">Programme pour {{ $guest->name }}</p>

                <section class="mt-8 border-t border-line pt-6" aria-labelledby="programme-title" data-reveal>
                    <h2 id="programme-title" class="font-display text-heading text-ink">Programme</h2>

                    @if ($event->programSteps->isEmpty())
                        <p class="mt-3 text-body text-ink-muted">
                            Le programme sera communiqué prochainement.
                        </p>
                    @else
                        <ol role="list" class="mt-5">
                            @foreach ($event->programSteps as $step)
                                <li class="relative border-l-2 border-line-strong pb-6 pl-5 last:pb-0">
                                    <span class="absolute -left-[5px] top-1.5 h-2 w-2 rounded-full bg-accent" aria-hidden="true"></span>
                                    <p class="font-display text-heading text-accent">
                                        {{ $step->start_time }}@if ($step->end_time) – {{ $step->end_time }}@endif
                                    </p>
                                    <p class="mt-1 text-label text-ink">{{ $step->title }}</p>
                                    @if ($step->location)
                                        <p class="text-caption text-ink-muted">{{ $step->location }}</p>
                                    @endif
                                    @if ($step->description)
                                        <p class="mt-1 text-caption text-ink-muted">{{ $step->description }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>

                @if ($event->dress_code)
                    <section class="mt-8 border-t border-line pt-6" aria-labelledby="tenue-title" data-reveal>
                        <h2 id="tenue-title" class="font-display text-heading text-ink">Code vestimentaire</h2>
                        <p class="mt-2 text-body text-ink">{{ $event->dress_code }}</p>
                    </section>
                @endif

                <section class="mt-8 border-t border-line pt-6" aria-labelledby="carte-title" data-reveal>
                    <h2 id="carte-title" class="font-display text-heading text-ink">Comment venir</h2>
                    <p class="mt-2 text-body text-ink">{{ $event->venue_name }}</p>
                    @if ($event->venue_address)
                        <p class="text-caption text-ink-muted">{{ $event->venue_address }}</p>
                    @endif

                    @if ($event->hasCoordinates())
                        <div
                            data-program-map
                            data-lat="{{ $event->latitude }}"
                            data-lng="{{ $event->longitude }}"
                            data-venue="{{ $event->venue_name }}"
                            class="mt-4 h-64 w-full rounded-sm border border-line bg-surface-300"
                            role="region"
                            aria-label="Carte du lieu : {{ $event->venue_name }}"
                            aria-describedby="carte-aide"
                        ></div>
                        <p id="carte-aide" class="help mt-2">
                            Carte interactive : utilisez les flèches pour vous déplacer et les boutons + et − pour zoomer.
                            Les liens d'itinéraire ci-dessous ouvrent votre application de navigation.
                        </p>
                        <noscript>
                            <p class="help mt-2">
                                La carte interactive nécessite JavaScript. L'adresse et les liens d'itinéraire ci-dessous restent disponibles.
                            </p>
                        </noscript>
                    @else
                        <p class="mt-3 text-caption text-ink-muted">
                            Le plan n'est pas disponible. Utilisez l'adresse ci-dessus dans votre application de navigation.
                        </p>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-3">
                        <a
                            href="{{ $event->googleMapsUrl() }}"
                            class="btn btn-primary"
                            target="_blank"
                            rel="noopener"
                        >
                            Itinéraire Google Maps
                        </a>
                        @if ($event->wazeUrl())
                            <a
                                href="{{ $event->wazeUrl() }}"
                                class="btn btn-secondary"
                                target="_blank"
                                rel="noopener"
                            >
                                Ouvrir dans Waze
                            </a>
                        @endif
                    </div>
                </section>

                <div class="mt-8 border-t border-line pt-6">
                    <a
                        href="{{ route('invitations.show', $guest->invitation_token) }}"
                        class="text-caption font-semibold text-accent hover:underline"
                    >
                        Retour à l'invitation
                    </a>
                </div>
            </div>
        </article>
    </div>
@endsection
