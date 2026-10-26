@extends('layouts.app')

@section('title', 'Créer un compte — Hub Événement')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12">
        <div class="card card-invitation p-6 md:p-8">
            <p class="eyebrow">Espace organisateur</p>
            <h1 class="mt-2 font-display text-heading text-ink">Créer un compte</h1>
            <p class="mt-2 text-caption text-ink-muted">
                Un compte, plusieurs événements. Commencez par votre premier grand moment.
            </p>

            <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="name" class="label text-ink">Votre nom</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        autocomplete="name"
                        required
                        autofocus
                        class="field mt-2 @error('name') field-error @enderror"
                    >
                    @error('name')
                        <p class="error-text mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="label text-ink">Adresse e-mail</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        class="field mt-2 @error('email') field-error @enderror"
                    >
                    @error('email')
                        <p class="error-text mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="label text-ink">Mot de passe</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="field mt-2 @error('password') field-error @enderror"
                    >
                    @error('password')
                        <p class="error-text mt-1">{{ $message }}</p>
                    @enderror
                    <p class="help mt-1">Au moins 8 caractères.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="label text-ink">Confirmer le mot de passe</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="field mt-2"
                    >
                </div>

                <button type="submit" class="btn btn-primary w-full">Créer mon compte</button>
            </form>

            <p class="mt-6 text-caption text-ink-muted">
                Vous avez déjà un compte ?
                <a href="{{ route('login') }}" class="text-gold underline decoration-line">Se connecter</a>
            </p>
        </div>
    </div>
@endsection
