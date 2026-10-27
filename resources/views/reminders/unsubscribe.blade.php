@extends('layouts.invitation')

@section('title', 'Relances — '.$event->name.' — Hub Événement')

@section('content')
    <div class="{{ $event->type->accentClass() }}">
        <article class="card card-invitation overflow-hidden">
            <div class="h-1 bg-accent" aria-hidden="true"></div>

            <div class="p-6 sm:p-10">
                <p class="eyebrow">{{ $event->type->label() }}</p>
                <h1 class="mt-2 font-display text-heading text-ink sm:text-display-lg">{{ $event->name }}</h1>
                <p class="mt-3 text-body text-ink-muted">
                    {{ $event->starts_at->translatedFormat('l j F Y') }} à {{ $event->starts_at->format('H\hi') }}
                </p>
                <p class="text-caption text-ink-muted">Relances pour {{ $guest->name }}</p>

                <section class="mt-8 border-t border-line pt-6" aria-labelledby="desinscription-title">
                    <h2 id="desinscription-title" class="font-display text-heading text-ink">Relances</h2>

                    @if ($guest->hasOptedOutOfReminders())
                        <p class="mt-3 text-body text-ink">
                            Vous ne recevrez plus de relances par e-mail pour cet événement.
                        </p>
                        <p class="mt-2 text-caption text-ink-muted">
                            Votre invitation et votre QR d'entrée restent valables. Vous pouvez répondre à tout moment.
                        </p>
                    @else
                        <p class="mt-3 text-body text-ink">
                            Vous ne souhaitez plus recevoir de rappels pour cet événement ?
                        </p>
                        <p class="mt-2 text-caption text-ink-muted">
                            Votre invitation, le programme et votre QR d'entrée restent accessibles. Seules les relances sont arrêtées.
                        </p>

                        <form method="POST" action="{{ $unsubscribeAction }}" class="mt-5">
                            @csrf
                            <button type="submit" class="btn btn-secondary">Ne plus recevoir de relances</button>
                        </form>
                    @endif

                    <p class="mt-6">
                        <a href="{{ $guest->invitationUrl() }}" class="text-caption font-semibold text-accent hover:underline">
                            Revenir à l'invitation
                        </a>
                    </p>
                </section>
            </div>
        </article>
    </div>
@endsection
