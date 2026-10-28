@extends('layouts.invitation')

@section('title', 'Paiement — '.$contribution->event->name.' — Hub Événement')

@section('content')
    <div class="{{ $contribution->event->type->accentClass() }}">
        <article class="card card-invitation overflow-hidden">
            <div class="h-1 bg-accent" aria-hidden="true"></div>

            <div class="p-6 sm:p-10">
                <p class="eyebrow">Paiement MoneyFusion</p>

                @php
                    $status = $contribution->status;
                    $tone = match ($status) {
                        \App\Enums\ContributionStatus::Paid => 'success',
                        \App\Enums\ContributionStatus::Pending => 'warning',
                        \App\Enums\ContributionStatus::Failed => 'danger',
                        default => 'info',
                    };
                    $title = match ($status) {
                        \App\Enums\ContributionStatus::Paid => 'Merci, votre participation est confirmée.',
                        \App\Enums\ContributionStatus::Pending => 'Votre paiement est en cours de vérification.',
                        \App\Enums\ContributionStatus::Failed => 'Le paiement n’a pas abouti.',
                        \App\Enums\ContributionStatus::Expired => 'Cette contribution a expiré.',
                        \App\Enums\ContributionStatus::Refunded => 'Cette contribution a été remboursée.',
                    };
                @endphp

                <h1 class="mt-2 font-display text-heading text-ink sm:text-display-lg">{{ $title }}</h1>

                <x-alert :tone="$tone" class="mt-5">
                    @if ($status === \App\Enums\ContributionStatus::Pending)
                        MoneyFusion n'a pas encore confirmé ce paiement. Cette page se met à jour à chaque actualisation ; vous pouvez aussi revenir plus tard depuis votre invitation.
                    @elseif ($status === \App\Enums\ContributionStatus::Paid)
                        Votre contribution a été vérifiée auprès de MoneyFusion et ajoutée à la progression.
                    @elseif ($status === \App\Enums\ContributionStatus::Failed)
                        Aucun montant n'a été crédité. Vous pouvez réessayer depuis votre invitation.
                    @elseif ($status === \App\Enums\ContributionStatus::Expired)
                        Aucun paiement confirmé n'a été reçu avant l'expiration. Vous pouvez recommencer depuis votre invitation.
                    @else
                        Le remboursement a été enregistré par les organisateurs.
                    @endif
                </x-alert>

                <dl class="mt-6 space-y-2 text-body">
                    <div class="flex flex-wrap gap-x-2">
                        <dt class="text-ink-muted">Montant :</dt>
                        <dd class="tabular text-ink">{{ \App\Support\Money::format($contribution->amount) }}</dd>
                    </div>
                    <div class="flex flex-wrap gap-x-2">
                        <dt class="text-ink-muted">Destination :</dt>
                        <dd class="text-ink">{{ $contribution->label() }}</dd>
                    </div>
                    <div class="flex flex-wrap gap-x-2">
                        <dt class="text-ink-muted">Référence :</dt>
                        <dd class="font-mono text-caption text-ink">{{ $contribution->reference }}</dd>
                    </div>
                    <div class="flex flex-wrap gap-x-2">
                        <dt class="text-ink-muted">Statut :</dt>
                        <dd><span class="status-pill status-pill-{{ $status->tone() }}">{{ $status->label() }}</span></dd>
                    </div>
                    @if ($contribution->paid_at)
                        <div class="flex flex-wrap gap-x-2">
                            <dt class="text-ink-muted">Confirmé le :</dt>
                            <dd class="text-ink">{{ $contribution->paid_at->translatedFormat('j F Y \à H\hi') }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-8 flex flex-wrap gap-3 border-t border-line pt-6">
                    @if ($contribution->guest)
                        <a href="{{ route('invitations.registry', $contribution->guest->invitation_token) }}" class="btn btn-secondary">
                            Retour aux cadeaux
                        </a>
                        <a href="{{ route('invitations.show', $contribution->guest->invitation_token) }}" class="btn btn-secondary">
                            Voir l'invitation
                        </a>
                    @endif
                    @if ($status === \App\Enums\ContributionStatus::Pending)
                        <a href="{{ url()->current() }}" class="btn btn-secondary">Actualiser</a>
                    @endif
                </div>
            </div>
        </article>
    </div>
@endsection
