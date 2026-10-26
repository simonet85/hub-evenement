@extends('layouts.app')

@section('title', 'Connexion — Hub Événement')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12">
        <div class="card card-invitation p-6 md:p-8">
            <p class="eyebrow">Espace organisateur</p>
            <h1 class="mt-2 font-display text-heading text-ink">Se connecter</h1>
            <p class="mt-2 text-caption text-ink-muted">
                Retrouvez vos événements, vos invités et vos réponses.
            </p>

            @if (session('status'))
                <p class="mt-4 text-caption text-success" role="status">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="email" class="label text-ink">Adresse e-mail</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
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
                        autocomplete="current-password"
                        required
                        class="field mt-2 @error('password') field-error @enderror"
                    >
                    @error('password')
                        <p class="error-text mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-caption text-ink-muted">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded-sm border-line-strong">
                        Se souvenir de moi
                    </label>
                    <a href="{{ route('password.request') }}" class="text-caption text-gold underline decoration-line">
                        Mot de passe oublié ?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary w-full">Se connecter</button>
            </form>

            <p class="mt-6 text-caption text-ink-muted">
                Pas encore de compte ?
                <a href="{{ route('register') }}" class="text-gold underline decoration-line">Créer un compte</a>
            </p>
        </div>
    </div>
@endsection
