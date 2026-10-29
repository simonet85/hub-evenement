@extends('layouts.app')

@section('title', 'Hub Événement — invitations, RSVP et cagnotte')
@section('header_cta', 'Créer mon événement')
@section('header_cta_variant', 'btn-secondary')
@section('header_cta_class', 'hidden sm:inline-flex')

@push('head')
    <meta name="description" content="Invitations personnelles, RSVP, programme et cagnotte pour vos grands moments. Un lien par foyer, sans compte pour vos invités.">
    <link rel="canonical" href="{{ url('/') }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Hub Événement">
    <meta property="og:title" content="Hub Événement — invitations, RSVP et cagnotte">
    <meta property="og:description" content="Invitations personnelles, RSVP, programme et cagnotte pour vos grands moments. Un lien par foyer, sans compte pour vos invités.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('images/og-home.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Aperçu d’une invitation Hub Événement pour le mariage d’Awa et Koffi.">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Hub Événement — invitations, RSVP et cagnotte">
    <meta name="twitter:description" content="Invitations personnelles, RSVP, programme et cagnotte pour vos grands moments.">
    <meta name="twitter:image" content="{{ asset('images/og-home.png') }}">
@endpush

@section('content')
    <section class="mx-auto grid max-w-5xl gap-12 px-4 pb-16 pt-10 md:grid-cols-[1.05fr_0.95fr] md:items-center md:pb-24 md:pt-16 lg:gap-16">
        <div>
            <h1 class="max-w-2xl text-display-lg font-display text-ink md:text-display-xl">
                Les grands moments méritent mieux qu’un fichier Excel.
            </h1>
            <p class="mt-6 max-w-xl text-body-lg text-ink-muted">
                Invitations personnelles, RSVP, programme, livre d’or, galerie et cagnotte.
                Un lien par foyer à envoyer sur WhatsApp, des comptes justes, et rien à installer pour vos invités.
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Créer mon événement</a>
                <a href="#apercu" class="btn btn-secondary btn-lg">Voir une invitation</a>
            </div>
            <p class="mt-4 text-caption text-ink-muted">
                Gratuit au lancement · Vos invités répondent sans créer de compte.
            </p>
        </div>

        <div id="apercu" class="scroll-mt-8" tabindex="-1">
            <figure class="card card-invitation overflow-hidden border-line-strong">
                <div class="h-1 bg-accent" aria-hidden="true"></div>
                <div class="p-6 sm:p-8">
                    <div class="flex items-center justify-between gap-3">
                        <x-status-pill>Mariage</x-status-pill>
                        <span class="text-caption text-ink-muted">Aperçu</span>
                    </div>

                    <p class="mt-8 text-center font-display text-display-lg text-ink">
                        Awa &amp; Koffi
                    </p>
                    <p class="mt-7 text-center font-display text-heading text-ink">Samedi 12 décembre 2026</p>
                    <p class="mt-1 text-center text-body text-ink-muted">16h00 · Maison Gbaka, Cocody</p>

                    <div class="rule-gold mx-auto mt-6 w-12" aria-hidden="true"></div>

                    <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-caption text-ink-muted">Invitation pour</p>
                            <p class="font-display text-heading text-ink">Famille Kouassi</p>
                            <p class="text-caption text-ink-muted">2 places réservées</p>
                        </div>
                        <x-status-pill tone="success">Présent</x-status-pill>
                    </div>
                </div>
                <figcaption class="border-t border-line bg-surface px-6 py-4 text-caption text-ink-muted">
                    Données d’exemple. Un lien personnel par foyer, sans compte pour vos invités.
                </figcaption>
            </figure>
        </div>
    </section>

    <section class="border-y border-line bg-surface" aria-labelledby="registre-title">
        <div class="mx-auto grid max-w-5xl gap-10 px-4 py-14 md:grid-cols-[1.1fr_0.9fr] md:items-center md:py-20">
            <div class="order-2 md:order-1">
                <div class="card overflow-hidden border-line-strong">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                        <div>
                            <p class="text-label text-ink">Suivi des réponses</p>
                            <p class="text-caption text-ink-muted">Awa &amp; Koffi · 12 décembre</p>
                        </div>
                        <x-status-pill tone="success">À jour</x-status-pill>
                    </div>

                    <div class="grid grid-cols-3 divide-x divide-line border-b border-line">
                        <div class="px-4 py-4 sm:px-5">
                            <p class="font-display text-heading tabular text-ink">38</p>
                            <p class="text-caption text-ink-muted">foyers</p>
                        </div>
                        <div class="px-4 py-4 sm:px-5">
                            <p class="font-display text-heading tabular text-ink">112</p>
                            <p class="text-caption text-ink-muted">couverts</p>
                        </div>
                        <div class="px-4 py-4 sm:px-5">
                            <p class="font-display text-heading tabular text-ink">6</p>
                            <p class="text-caption text-ink-muted">régimes</p>
                        </div>
                    </div>

                    <div class="border-b border-line px-5 py-4">
                        <div class="flex items-center justify-between gap-3 text-caption text-ink-muted">
                            <span>38 foyers sur 52 ont répondu</span>
                            <span class="tabular">73 %</span>
                        </div>
                        <div
                            class="progress mt-2"
                            role="progressbar"
                            aria-label="Foyers ayant répondu"
                            aria-valuenow="73"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div class="progress-bar" style="--progress: 73%"></div>
                        </div>
                    </div>

                    <ul class="divide-y divide-line">
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="text-body text-ink">Famille Kouassi</span>
                            <span class="flex items-center gap-3">
                                <span class="text-caption text-ink-muted">2 pers.</span>
                                <x-status-pill tone="success">Présent</x-status-pill>
                            </span>
                        </li>
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="text-body text-ink">Famille Traoré</span>
                            <span class="flex items-center gap-3">
                                <span class="text-caption text-ink-muted">4 pers.</span>
                                <x-status-pill tone="success">Présent</x-status-pill>
                            </span>
                        </li>
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="text-body text-ink">M. Diabaté</span>
                            <span class="flex items-center gap-3">
                                <span class="text-caption text-ink-muted">1 pers.</span>
                                <x-status-pill tone="warning" icon="clock">En attente</x-status-pill>
                            </span>
                        </li>
                    </ul>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-surface-muted px-5 py-4">
                        <p class="text-caption text-ink-muted">6 régimes à transmettre au traiteur</p>
                        <x-status-pill tone="success">Export prêt</x-status-pill>
                    </div>
                </div>
            </div>

            <div class="order-1 md:order-2">
                <h2 id="registre-title" class="max-w-md text-display-lg font-display text-ink">
                    Le registre tient tout seul.
                </h2>
                <p class="mt-5 max-w-md text-body-lg text-ink-muted">
                    Chaque réponse met à jour les couverts, les régimes et le pointage.
                    Vous ouvrez le suivi, vous savez où vous en êtes.
                </p>
                <ul class="mt-7 space-y-4">
                    <li class="flex gap-3">
                        <x-icon name="check" class="mt-1 shrink-0 text-primary" />
                        <p class="text-body text-ink">
                            <span class="font-semibold">Couverts justes.</span>
                            <span class="text-ink-muted">Le total se recalcule à chaque réponse, sans relance.</span>
                        </p>
                    </li>
                    <li class="flex gap-3">
                        <x-icon name="check" class="mt-1 shrink-0 text-primary" />
                        <p class="text-body text-ink">
                            <span class="font-semibold">Entrée fluide.</span>
                            <span class="text-ink-muted">Un QR personnel par foyer, scanné au pointage.</span>
                        </p>
                    </li>
                    <li class="flex gap-3">
                        <x-icon name="check" class="mt-1 shrink-0 text-primary" />
                        <p class="text-body text-ink">
                            <span class="font-semibold">Traiteur prêt.</span>
                            <span class="text-ink-muted">Les régimes et les couverts partent en export CSV, Excel ou PDF.</span>
                        </p>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-4 py-14 md:py-20" aria-labelledby="inclus-title">
        <div class="grid gap-8 md:grid-cols-[0.8fr_1.2fr] md:gap-14">
            <h2 id="inclus-title" class="max-w-sm text-heading font-display text-ink md:text-display-lg">
                Et tout le reste du grand moment.
            </h2>
            <ul class="grid gap-x-10 gap-y-5 sm:grid-cols-2">
                <li class="flex gap-3 border-t border-line pt-4">
                    <x-icon name="check" class="mt-1 shrink-0 text-primary" />
                    <div>
                        <p class="text-label text-ink">Quatre thèmes</p>
                        <p class="mt-1 text-caption text-ink-muted">Mariage, baptême, anniversaire ou fête, chacun avec son accent.</p>
                    </div>
                </li>
                <li class="flex gap-3 border-t border-line pt-4">
                    <x-icon name="check" class="mt-1 shrink-0 text-primary" />
                    <div>
                        <p class="text-label text-ink">Programme et carte</p>
                        <p class="mt-1 text-caption text-ink-muted">Les étapes, le lieu et l’itinéraire au même endroit.</p>
                    </div>
                </li>
                <li class="flex gap-3 border-t border-line pt-4">
                    <x-icon name="check" class="mt-1 shrink-0 text-primary" />
                    <div>
                        <p class="text-label text-ink">Livre d’or et galerie</p>
                        <p class="mt-1 text-caption text-ink-muted">Les messages et les photos restent modérés par vous.</p>
                    </div>
                </li>
                <li class="flex gap-3 border-t border-line pt-4">
                    <x-icon name="check" class="mt-1 shrink-0 text-primary" />
                    <div>
                        <p class="text-label text-ink">Cadeaux et cagnotte</p>
                        <p class="mt-1 text-caption text-ink-muted">Les contributions passent par mobile money, vérifiées côté serveur.</p>
                    </div>
                </li>
                <li class="flex gap-3 border-t border-line pt-4">
                    <x-icon name="check" class="mt-1 shrink-0 text-primary" />
                    <div>
                        <p class="text-label text-ink">Co-organisateurs</p>
                        <p class="mt-1 text-caption text-ink-muted">Conjoint, famille ou témoins gèrent avec vous, sans partager votre compte.</p>
                    </div>
                </li>
                <li class="flex gap-3 border-t border-line pt-4">
                    <x-icon name="check" class="mt-1 shrink-0 text-primary" />
                    <div>
                        <p class="text-label text-ink">Exports traiteur</p>
                        <p class="mt-1 text-caption text-ink-muted">Un accès en lecture seule, limité dans le temps.</p>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    <section class="border-t border-line" aria-labelledby="cloture-title">
        <div class="mx-auto max-w-3xl px-4 py-16 text-center md:py-24">
            <h2 id="cloture-title" class="text-display-lg font-display text-ink">
                Le prochain grand moment commence par un lien.
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-body-lg text-ink-muted">
                Créez votre événement, importez vos foyers et laissez les réponses arriver.
            </p>
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg mt-8">Créer mon événement</a>
            <p class="mt-4 text-caption text-ink-muted">
                Gratuit au lancement · Vos invités n’ont pas de compte à créer.
            </p>
            <div class="mt-10" aria-hidden="true">
                <div class="rule-gold mx-auto w-16"></div>
                <p class="mt-4 font-script text-script text-gold">Avec joie</p>
            </div>
        </div>
    </section>
@endsection
