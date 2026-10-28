@extends('layouts.invitation')

@section('title', 'Paiement simulé — Hub Événement')

@section('content')
    <article class="card card-invitation p-6 sm:p-10">
        <p class="eyebrow">Simulateur local</p>
        <h1 class="mt-2 font-display text-heading text-ink">Paiement MoneyFusion simulé</h1>
        <p class="mt-2 text-body text-ink-muted">
            Cette page remplace la page MoneyFusion en développement. Aucun argent n'est débité.
        </p>

        <dl class="mt-6 space-y-2 text-body">
            <div class="flex flex-wrap gap-x-2">
                <dt class="text-ink-muted">Montant :</dt>
                <dd class="tabular text-ink">{{ \App\Support\Money::format($contribution->amount) }}</dd>
            </div>
            <div class="flex flex-wrap gap-x-2">
                <dt class="text-ink-muted">Payeur :</dt>
                <dd class="text-ink">{{ $contribution->contributor_name }}</dd>
            </div>
            <div class="flex flex-wrap gap-x-2">
                <dt class="text-ink-muted">Destination :</dt>
                <dd class="text-ink">{{ $contribution->label() }}</dd>
            </div>
            <div class="flex flex-wrap gap-x-2">
                <dt class="text-ink-muted">Téléphone :</dt>
                <dd class="text-ink">{{ $contribution->phone }}</dd>
            </div>
        </dl>

        <div class="mt-8 flex flex-wrap gap-3 border-t border-line pt-6">
            <form method="POST" action="{{ route('payments.simulator.complete', $contribution->provider_token) }}">
                @csrf
                <input type="hidden" name="action" value="paid">
                <button type="submit" class="btn btn-primary">Simuler un paiement réussi</button>
            </form>

            <form method="POST" action="{{ route('payments.simulator.complete', $contribution->provider_token) }}">
                @csrf
                <input type="hidden" name="action" value="failed">
                <button type="submit" class="btn btn-secondary">Simuler un échec</button>
            </form>
        </div>
    </article>
@endsection
