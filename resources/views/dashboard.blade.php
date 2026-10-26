@extends('layouts.app')

@section('title', 'Mon espace — Hub Événement')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-12">
        <p class="eyebrow">Espace organisateur</p>
        <h1 class="mt-2 font-display text-display-lg text-ink">Bonjour, {{ auth()->user()->name }}</h1>
        <p class="mt-2 text-body text-ink-muted">
            Vos événements, vos invités et vos réponses apparaîtront ici.
        </p>

        <div class="card mt-8 p-8 text-center">
            <p class="font-script text-script text-accent">Bienvenue</p>
            <h2 class="mt-2 font-display text-heading text-ink">Aucun événement pour l'instant</h2>
            <p class="mx-auto mt-2 max-w-md text-body text-ink-muted">
                Le socle est en place. La création d'événements, les foyers et l'import CSV
                arrivent à l'étape suivante.
            </p>
        </div>
    </div>
@endsection
