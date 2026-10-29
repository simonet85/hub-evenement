@extends('layouts.app')

@section('title', 'Hub Événement — invitations, RSVP et cagnotte')
@section('header_cta', 'Créer mon événement')
@section('header_cta_variant', 'btn-secondary')
@section('header_cta_class', 'hidden sm:inline-flex')

@push('head')
    <link
        rel="preload"
        as="image"
        href="{{ asset('images/accueil-reception.jpg') }}"
        imagesrcset="{{ asset('images/accueil-reception-768.jpg') }} 768w, {{ asset('images/accueil-reception.jpg') }} 1264w"
        imagesizes="(min-width: 1024px) 480px, (min-width: 768px) 45vw, calc(100vw - 32px)"
        fetchpriority="high"
    >
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
        <div class="min-w-0">
            <h1 class="max-w-2xl text-display-lg font-display text-ink min-[360px]:text-display-xl">
                Les grands moments méritent mieux qu’un fichier Excel.
            </h1>
            <p class="mt-6 max-w-xl text-body-lg text-ink-muted">
                Invitations personnelles, RSVP, programme, livre d’or, galerie et cagnotte.
                Un lien par foyer à envoyer sur WhatsApp, des comptes justes, et rien à installer pour vos invités.
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row md:flex-col lg:flex-row">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Créer mon événement</a>
                <a href="{{ $demoInvitationUrl ?? '#apercu' }}" class="btn btn-secondary btn-lg">Voir une invitation</a>
            </div>
            <p class="mt-4 text-caption text-ink-muted">
                Gratuit au lancement · Vos invités répondent sans créer de compte.
            </p>
        </div>

        <div class="relative min-w-0">
            <figure class="overflow-hidden rounded-md border border-line-strong bg-surface">
                <img
                    src="{{ asset('images/accueil-reception.jpg') }}"
                    srcset="{{ asset('images/accueil-reception-768.jpg') }} 768w, {{ asset('images/accueil-reception.jpg') }} 1264w"
                    sizes="(min-width: 1024px) 480px, (min-width: 768px) 45vw, calc(100vw - 32px)"
                    alt="Illustration d’un couple souriant à une réception, assis à une longue table dressée sous des guirlandes lumineuses."
                    width="1264"
                    height="848"
                    fetchpriority="high"
                    decoding="async"
                    class="aspect-[3/2] w-full object-cover"
                >
            </figure>

            <div id="apercu" class="relative z-10 -mt-16 scroll-mt-8 sm:-mt-20 lg:-mt-24 lg:px-6" tabindex="-1">
                <figure class="card card-invitation overflow-hidden border-line-strong">
                    <div class="h-1 bg-accent" aria-hidden="true"></div>
                    <div class="p-6 sm:p-8">
                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
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

            <p class="mt-4 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 text-caption text-ink-muted">
                <span>Le jour J, côté réception.</span>
                <span>Image d’illustration.</span>
            </p>
        </div>
    </section>

    <section class="border-y border-line bg-surface" aria-labelledby="registre-title">
        <div class="mx-auto grid max-w-5xl gap-10 px-4 py-14 md:grid-cols-[1.1fr_0.9fr] md:items-center md:py-20">
            <div class="order-2 min-w-0 md:order-1">
                <div class="card overflow-hidden border-line-strong">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                        <div>
                            <p class="text-label text-ink">Suivi des réponses</p>
                            <p class="text-caption text-ink-muted">Awa &amp; Koffi · 12 décembre</p>
                        </div>
                        <x-status-pill tone="success">Suivi à jour</x-status-pill>
                    </div>

                    <div class="grid grid-cols-[repeat(auto-fit,minmax(6.5em,1fr))] divide-x divide-line border-b border-line">
                        <div class="px-4 py-4 sm:px-5">
                            <p class="text-heading tabular text-ink">38</p>
                            <p class="text-caption text-ink-muted">foyers</p>
                        </div>
                        <div class="px-4 py-4 sm:px-5">
                            <p class="text-heading tabular text-ink">112</p>
                            <p class="text-caption text-ink-muted">couverts</p>
                        </div>
                        <div class="px-4 py-4 sm:px-5">
                            <p class="text-heading tabular text-ink">6</p>
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
                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-5 py-3">
                            <span class="text-body text-ink">Famille Kouassi</span>
                            <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="text-caption text-ink-muted">2 personnes</span>
                                <x-status-pill tone="success">Présent</x-status-pill>
                            </span>
                        </li>
                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-5 py-3">
                            <span class="text-body text-ink">Famille Traoré</span>
                            <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="text-caption text-ink-muted">4 personnes</span>
                                <x-status-pill tone="success">Présent</x-status-pill>
                            </span>
                        </li>
                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-5 py-3">
                            <span class="text-body text-ink">M. Diabaté</span>
                            <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="text-caption text-ink-muted">1 personne</span>
                                <x-status-pill tone="warning" icon="clock">En attente</x-status-pill>
                            </span>
                        </li>
                    </ul>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-surface-muted px-5 py-4">
                        <p class="text-caption text-ink-muted">6 régimes à transmettre au traiteur</p>
                        <x-status-pill tone="warning" icon="clock">À exporter</x-status-pill>
                    </div>

                    <p class="border-t border-line px-5 py-3 text-caption text-ink-muted">
                        Aperçu — données d’exemple. Les chiffres illustrent le suivi, pas un événement réel.
                    </p>
                </div>
            </div>

            <div class="order-1 min-w-0 md:order-2">
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
                <a href="{{ route('register') }}" class="mt-6 inline-flex min-h-11 items-center gap-2 text-label text-primary transition-colors hover:text-primary-hover">
                    Créer mon événement
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-4 py-14 md:py-20" aria-labelledby="preuves-title">
        <div class="max-w-xl">
            <h2 id="preuves-title" class="text-display-lg font-display text-ink">
                Du lien WhatsApp à l’assiette du traiteur.
            </h2>
            <p class="mt-4 text-body-lg text-ink-muted">
                Trois moments où un fichier Excel craque. Voici ce qui prend le relais.
            </p>
        </div>

        <div class="mt-10 grid gap-4 md:grid-cols-[1.1fr_0.9fr]">
            <article class="card flex min-w-0 flex-col overflow-hidden">
                <div class="border-b border-line px-5 py-3">
                    <p class="text-caption text-ink-muted">WhatsApp · message envoyé</p>
                </div>
                <div class="flex flex-1 flex-col justify-center bg-surface-muted px-4 py-5 sm:px-5">
                    <div class="ml-auto max-w-[36ch] rounded-sm bg-primary px-4 py-3 text-on-primary">
                        <p class="text-body">
                            Bonjour Famille Kouassi, voici l’invitation pour le mariage d’Awa &amp; Koffi, le samedi 12 décembre.
                        </p>
                        <p class="mt-2 flex items-center gap-1 text-caption">
                            <x-icon name="check" :size="12" />
                            Envoyé · lien personnel
                        </p>
                    </div>
                    <div class="mt-3 mr-auto max-w-[30ch] rounded-sm border border-line bg-background px-4 py-3">
                        <p class="text-body text-ink">Nous serons 2. Merci beaucoup !</p>
                    </div>
                    <p class="mt-2 text-caption text-ink-muted">Réponse enregistrée · 2 places</p>
                </div>
                <div class="border-t border-line px-5 py-4">
                    <h3 class="font-sans text-label text-ink">Un lien par foyer.</h3>
                    <p class="mt-1 text-caption text-ink-muted">
                        Chaque foyer reçoit son lien personnel. Vos invités répondent sans créer de compte.
                    </p>
                </div>
            </article>

            <div class="grid min-w-0 gap-4">
                <article class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-3">
                        <p class="text-caption text-ink-muted">Cagnotte · mobile money</p>
                    </div>
                    <div class="px-5 py-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-heading tabular text-ink">
                                25 000 <span class="text-label font-normal text-ink-muted">F CFA</span>
                            </p>
                            <x-status-pill tone="success">Confirmé</x-status-pill>
                        </div>
                        <p class="mt-2 text-caption text-ink-muted">Famille Traoré · reçu à 18 h 42</p>
                    </div>
                    <div class="border-t border-line px-5 py-4">
                        <h3 class="font-sans text-label text-ink">Chaque franc confirmé.</h3>
                        <p class="mt-1 text-caption text-ink-muted">
                            Paiement vérifié côté serveur, jamais décidé par le navigateur.
                        </p>
                    </div>
                </article>

                <article class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-3">
                        <p class="text-caption text-ink-muted">Entrée et traiteur</p>
                    </div>
                    <ul class="divide-y divide-line">
                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-5 py-3">
                            <span class="flex items-center gap-3">
                                <x-icon name="qr" class="text-ink-muted" />
                                <span class="text-body text-ink">Famille Kouassi</span>
                            </span>
                            <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="text-caption text-ink-muted">2 personnes</span>
                                <x-status-pill tone="success">Pointé</x-status-pill>
                            </span>
                        </li>
                        <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-5 py-3">
                            <span class="text-body text-ink">Export traiteur</span>
                            <span class="text-caption text-ink-muted">CSV · 6 régimes</span>
                        </li>
                    </ul>
                    <div class="border-t border-line px-5 py-4">
                        <h3 class="font-sans text-label text-ink">Un QR à l’entrée.</h3>
                        <p class="mt-1 text-caption text-ink-muted">
                            Le pointage prend un scan. Les régimes partent au traiteur en CSV.
                        </p>
                    </div>
                </article>
            </div>
        </div>

        <p class="mt-4 text-caption text-ink-muted">Aperçus de l’interface. Données d’exemple.</p>

        <div class="mt-10 grid gap-4 border-t border-line pt-6 md:grid-cols-[10rem_1fr]">
            <p class="text-label text-ink">Aussi inclus</p>
            <ul class="grid gap-x-8 gap-y-2 text-caption text-ink-muted sm:grid-cols-2">
                <li class="flex items-baseline gap-2"><span class="text-gold" aria-hidden="true">·</span>Import CSV des foyers</li>
                <li class="flex items-baseline gap-2"><span class="text-gold" aria-hidden="true">·</span>Quatre thèmes, chacun avec son accent</li>
                <li class="flex items-baseline gap-2"><span class="text-gold" aria-hidden="true">·</span>Programme, carte et itinéraire</li>
                <li class="flex items-baseline gap-2"><span class="text-gold" aria-hidden="true">·</span>Livre d’or et galerie modérés par vous</li>
                <li class="flex items-baseline gap-2"><span class="text-gold" aria-hidden="true">·</span>Co-organisateurs, sans partage de compte</li>
            </ul>
        </div>
    </section>

    <section class="border-t border-line bg-surface" aria-labelledby="confiance-title">
        <div class="mx-auto max-w-5xl px-4 py-10 md:py-14">
            <div class="md:flex md:items-end md:justify-between md:gap-8">
                <h2 id="confiance-title" class="text-heading font-display text-ink">
                    Avant de confier vos invités.
                </h2>
                <p class="mt-3 text-caption text-ink-muted md:mt-0 md:text-right">
                    Gratuit au lancement. Aucun tarif n’est fixé à ce jour.
                </p>
            </div>

            <ul class="mt-7 grid gap-6 sm:grid-cols-3 sm:gap-8">
                <li class="border-t border-line pt-4">
                    <h3 class="font-sans text-label text-ink">Privé par défaut.</h3>
                    <p class="mt-1 text-caption text-ink-muted">
                        Chaque foyer a son lien personnel. Les invitations ne sont pas indexées par les moteurs de recherche.
                    </p>
                </li>
                <li class="border-t border-line pt-4">
                    <h3 class="font-sans text-label text-ink">Aucun compte invité.</h3>
                    <p class="mt-1 text-caption text-ink-muted">
                        Vos proches répondent avec leur lien, sans mot de passe ni application à installer.
                    </p>
                </li>
                <li class="border-t border-line pt-4">
                    <h3 class="font-sans text-label text-ink">Argent vérifié.</h3>
                    <p class="mt-1 text-caption text-ink-muted">
                        Chaque contribution est confirmée côté serveur avant d’entrer dans le total.
                    </p>
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
                Vos invités n’ont pas de compte à créer.
            </p>
            <div class="mt-10" aria-hidden="true">
                <div class="rule-gold mx-auto w-16"></div>
                <p class="mt-4 font-script text-script text-gold">Avec joie</p>
            </div>
        </div>
    </section>
@endsection
