@extends('layouts.app')

@section('title', 'Mot de passe oublié — Hub Événement')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12">
        <div class="card card-invitation p-6 md:p-8">
            <p class="eyebrow">Espace organisateur</p>
            <h1 class="mt-2 font-display text-heading text-ink">Mot de passe oublié</h1>
            <p class="mt-2 text-caption text-ink-muted">
                Indiquez votre adresse : si un compte existe, vous recevrez un lien de réinitialisation.
            </p>

            @if (session('status'))
                <p class="mt-4 text-caption text-success" role="status">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
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

                <button type="submit" class="btn btn-primary w-full">Envoyer le lien</button>
            </form>

            <p class="mt-6 text-caption text-ink-muted">
                <a href="{{ route('login') }}" class="text-gold underline decoration-line">Revenir à la connexion</a>
            </p>
        </div>
    </div>
@endsection
