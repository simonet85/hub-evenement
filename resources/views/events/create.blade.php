@extends('layouts.app')

@section('title', 'Nouvel événement — Hub Événement')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10">
        <p class="eyebrow">Nouvel événement</p>
        <h1 class="mt-2 font-display text-display-lg text-ink">Créer un événement</h1>
        <p class="mt-2 text-body text-ink-muted">
            Quelques informations suffisent pour commencer. Vous pourrez les modifier plus tard.
        </p>

        @include('events._form', [
            'action' => route('events.store'),
            'method' => 'POST',
            'submitLabel' => 'Créer l’événement',
            'cancelUrl' => route('dashboard'),
        ])
    </div>
@endsection
