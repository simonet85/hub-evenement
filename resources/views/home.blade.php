@extends('layouts.app')

@section('title', 'Hub Événement — invitations, RSVP et cagnotte')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12 md:py-20">
        <p class="eyebrow">Plateforme d'événements privés · Abidjan</p>
        <h1 class="mt-4 max-w-3xl text-display-lg font-display text-ink md:text-display-xl">
            Les grands moments méritent mieux qu'un fichier Excel.
        </h1>
        <p class="mt-6 max-w-2xl text-body-lg text-ink-muted">
            Invitations personnalisées, RSVP, programme, livre d'or, galerie et cagnotte :
            tout ce qui fait un bel événement, réuni au même endroit.
        </p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('register') }}" class="btn btn-primary">Créer mon événement</a>
            <a href="{{ route('login') }}" class="btn btn-secondary">Se connecter</a>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-4 pb-20" aria-label="Ce que vous pouvez préparer">
        <div class="grid gap-4 md:grid-cols-3">
            <article class="card p-6">
                <p class="eyebrow">Inviter</p>
                <h2 class="mt-3 text-heading font-display text-ink">Une invitation qui vous ressemble</h2>
                <p class="mt-2 text-body text-ink-muted">
                    Un lien personnel par foyer, un QR code pour l'entrée, et quatre thèmes
                    pour le mariage, le baptême, l'anniversaire ou la fête.
                </p>
            </article>
            <article class="card p-6">
                <p class="eyebrow">Répondre</p>
                <h2 class="mt-3 text-heading font-display text-ink">Des réponses sans relance</h2>
                <p class="mt-2 text-body text-ink-muted">
                    Présence, nombre de personnes, régime alimentaire et message :
                    le nombre de couverts se met à jour tout seul.
                </p>
            </article>
            <article class="card p-6">
                <p class="eyebrow">Partager</p>
                <h2 class="mt-3 text-heading font-display text-ink">Un événement qui reste</h2>
                <p class="mt-2 text-body text-ink-muted">
                    Livre d'or modéré, galerie photo et cagnotte mobile money,
                    avec des exports prêts pour le traiteur.
                </p>
            </article>
        </div>
    </section>
@endsection
