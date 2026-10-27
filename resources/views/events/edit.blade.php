@extends('layouts.app')

@section('title', 'Modifier '.$event->name.' — Hub Événement')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10">
        <nav aria-label="Fil d'Ariane" class="text-caption text-ink-muted">
            <a href="{{ route('dashboard') }}" class="hover:text-ink">Mon espace</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('events.show', $event) }}" class="hover:text-ink">{{ $event->name }}</a>
        </nav>

        <p class="eyebrow mt-6">Réglages</p>
        <h1 class="mt-2 font-display text-display-lg text-ink">Modifier l'événement</h1>

        @include('events._form', [
            'event' => $event,
            'action' => route('events.update', $event),
            'method' => 'PUT',
            'submitLabel' => 'Enregistrer les modifications',
            'cancelUrl' => route('events.show', $event),
        ])
    </div>
@endsection
