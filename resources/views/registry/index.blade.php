@extends('layouts.app')

@section('title', 'Cadeaux et cagnotte — '.$event->name.' — Hub Événement')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10 {{ $event->type->accentClass() }}">
        <nav aria-label="Fil d'Ariane" class="text-caption text-ink-muted">
            <a href="{{ route('dashboard') }}" class="hover:text-ink">Mon espace</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('events.show', $event) }}" class="hover:text-ink">{{ $event->name }}</a>
            <span aria-hidden="true"> / </span>
            <span aria-current="page">Cadeaux et cagnotte</span>
        </nav>

        <header class="mt-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="eyebrow">{{ $event->type->label() }}</p>
                <h1 class="mt-1 font-display text-display-lg text-ink">Cadeaux et cagnotte</h1>
                <p class="mt-2 text-body text-ink-muted">
                    Seuls les paiements confirmés comptent dans les totaux. Les paiements en attente retiennent le montant disponible des cadeaux jusqu'à leur expiration.
                </p>
            </div>
            <a href="{{ route('events.show', $event) }}" class="btn btn-secondary">Retour à l'événement</a>
        </header>

        @if ($errors->any())
            <x-alert tone="danger" class="mt-6">{{ $errors->first() }}</x-alert>
        @endif

        <section aria-label="Totaux" class="mt-8 grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['label' => 'Confirmé', 'value' => $collected],
                ['label' => 'En attente', 'value' => $pending],
                ['label' => 'Remboursé', 'value' => $refunded],
            ] as $card)
                <div class="card p-4">
                    <p class="eyebrow">{{ $card['label'] }}</p>
                    <p class="mt-1 font-display text-heading text-ink">{{ \App\Support\Money::format($card['value']) }}</p>
                </div>
            @endforeach
        </section>

        <section aria-labelledby="cagnotte-settings-title" class="card mt-8 p-6">
            <h2 id="cagnotte-settings-title" class="font-display text-heading text-ink">Cagnotte</h2>
            <p class="mt-1 text-caption text-ink-muted">
                Collecté pour la cagnotte : {{ \App\Support\Money::format($cagnottePaid) }}
                @if ($event->cagnotte_goal)
                    sur un objectif de {{ \App\Support\Money::format($event->cagnotte_goal) }}
                @endif
            </p>

            <form method="POST" action="{{ route('cagnotte.update', $event) }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <label class="flex min-h-[44px] items-center gap-2 text-label">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" @checked($event->cagnotte_enabled)>
                    Ouvrir la cagnotte aux invités
                </label>

                <div>
                    <label for="cagnotte_goal" class="label">Objectif <span class="font-normal text-ink-muted">(facultatif)</span></label>
                    <input
                        id="cagnotte_goal"
                        name="goal"
                        type="number"
                        min="1000"
                        max="100000000"
                        step="1000"
                        inputmode="numeric"
                        value="{{ old('goal', $event->cagnotte_goal) }}"
                        class="field mt-1"
                    >
                </div>

                <div>
                    <label for="cagnotte_message" class="label">Message d'accueil <span class="font-normal text-ink-muted">(facultatif)</span></label>
                    <textarea
                        id="cagnotte_message"
                        name="message"
                        rows="3"
                        maxlength="500"
                        class="field mt-1"
                        placeholder="Votre présence est notre plus beau cadeau. Pour ceux qui souhaitent participer…"
                    >{{ old('message', $event->cagnotte_message) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer la cagnotte</button>
            </form>
        </section>

        <section aria-labelledby="gift-add-title" class="card mt-8 p-6">
            <h2 id="gift-add-title" class="font-display text-heading text-ink">Ajouter un cadeau</h2>

            <form method="POST" action="{{ route('gifts.store', $event) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf

                <div class="sm:col-span-2">
                    <label for="gift_name" class="label">Nom du cadeau</label>
                    <input id="gift_name" name="name" type="text" required maxlength="160" value="{{ old('name') }}" class="field mt-1" placeholder="Service de table, machine à coudre…">
                </div>

                <div class="sm:col-span-2">
                    <label for="gift_description" class="label">Description <span class="font-normal text-ink-muted">(facultative)</span></label>
                    <input id="gift_description" name="description" type="text" maxlength="500" value="{{ old('description') }}" class="field mt-1">
                </div>

                <div>
                    <label for="gift_price" class="label">Prix unitaire (FCFA)</label>
                    <input id="gift_price" name="price" type="number" required min="500" max="10000000" step="500" inputmode="numeric" value="{{ old('price') }}" class="field mt-1">
                </div>

                <div>
                    <label for="gift_quantity" class="label">Quantité</label>
                    <input id="gift_quantity" name="quantity" type="number" required min="1" max="99" value="{{ old('quantity', 1) }}" class="field mt-1">
                </div>

                <label class="flex min-h-[44px] items-center gap-2 text-caption text-ink-muted sm:col-span-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked>
                    Visible par les invités
                </label>

                <div class="sm:col-span-2">
                    <button type="submit" class="btn btn-primary">Ajouter le cadeau</button>
                </div>
            </form>
        </section>

        <section aria-labelledby="gifts-title" class="mt-8">
            <h2 id="gifts-title" class="font-display text-heading text-ink">Liste de cadeaux</h2>

            @if ($gifts->isEmpty())
                <p class="card mt-4 p-4 text-body text-ink-muted">Aucun cadeau pour l'instant.</p>
            @else
                <ul role="list" class="mt-4 space-y-4">
                    @foreach ($gifts as $gift)
                        @php
                            $paid = $gift->paidTotal();
                            $total = $gift->totalPrice();
                            $available = $gift->availableAmount();
                        @endphp

                        <li class="card p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-label text-ink">{{ $gift->name }}</p>
                                    <p class="text-caption text-ink-muted">
                                        {{ \App\Support\Money::format($gift->price) }} × {{ $gift->quantity }}
                                        @if (! $gift->is_active)
                                            · <span class="text-warning">Masqué</span>
                                        @endif
                                    </p>
                                </div>
                                <x-progress
                                    class="min-w-[200px] flex-1"
                                    :value="$paid"
                                    :max="$total"
                                    label="Financé"
                                    :value-label="\App\Support\Money::format($paid).' / '.\App\Support\Money::format($total)"
                                />
                            </div>

                            <p class="mt-2 text-caption text-ink-muted">
                                Disponible : {{ \App\Support\Money::format($available) }}
                            </p>

                            <details class="mt-3">
                                <summary class="cursor-pointer text-caption font-semibold text-accent">Modifier ce cadeau</summary>

                                <form method="POST" action="{{ route('gifts.update', [$event, $gift]) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                                    @csrf
                                    @method('PUT')

                                    <div class="sm:col-span-2">
                                        <label for="name_{{ $gift->id }}" class="label">Nom</label>
                                        <input id="name_{{ $gift->id }}" name="name" type="text" required maxlength="160" value="{{ $gift->name }}" class="field mt-1">
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="description_{{ $gift->id }}" class="label">Description</label>
                                        <input id="description_{{ $gift->id }}" name="description" type="text" maxlength="500" value="{{ $gift->description }}" class="field mt-1">
                                    </div>

                                    <div>
                                        <label for="price_{{ $gift->id }}" class="label">Prix unitaire (FCFA)</label>
                                        <input id="price_{{ $gift->id }}" name="price" type="number" required min="500" max="10000000" step="500" inputmode="numeric" value="{{ $gift->price }}" class="field mt-1">
                                    </div>

                                    <div>
                                        <label for="quantity_{{ $gift->id }}" class="label">Quantité</label>
                                        <input id="quantity_{{ $gift->id }}" name="quantity" type="number" required min="1" max="99" value="{{ $gift->quantity }}" class="field mt-1">
                                    </div>

                                    <label class="flex min-h-[44px] items-center gap-2 text-caption text-ink-muted sm:col-span-2">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($gift->is_active)>
                                        Visible par les invités
                                    </label>

                                    <div class="sm:col-span-2">
                                        <button type="submit" class="btn btn-secondary">Enregistrer le cadeau</button>
                                    </div>
                                </form>
                            </details>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section id="journal" aria-labelledby="journal-title" class="mt-10">
            <h2 id="journal-title" class="font-display text-heading text-ink">Journal des paiements</h2>
            <p class="mt-1 text-caption text-ink-muted">
                Les remboursements et encaissements hors ligne sont enregistrés manuellement. MoneyFusion ne signe pas ses notifications : le statut est toujours revérifié côté serveur.
            </p>

            @if ($contributions->isEmpty())
                <p class="card mt-4 p-4 text-body text-ink-muted">Aucune contribution pour l'instant.</p>
            @else
                <div class="card mt-4 overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-caption">
                        <thead class="border-b border-line text-eyebrow uppercase tracking-wide text-ink-muted">
                            <tr>
                                <th scope="col" class="p-3">Date</th>
                                <th scope="col" class="p-3">Destination</th>
                                <th scope="col" class="p-3">Foyer</th>
                                <th scope="col" class="p-3">Montant</th>
                                <th scope="col" class="p-3">Statut</th>
                                <th scope="col" class="p-3">Référence</th>
                                <th scope="col" class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contributions as $contribution)
                                <tr class="border-b border-line align-top last:border-0">
                                    <td class="p-3 whitespace-nowrap">
                                        {{ $contribution->created_at->translatedFormat('j M Y') }}<br>
                                        <span class="text-ink-muted">{{ $contribution->created_at->format('H\hi') }}</span>
                                    </td>
                                    <td class="p-3">
                                        <span class="status-pill">{{ $contribution->kind->label() }}</span>
                                        <p class="mt-1 text-ink-muted">{{ $contribution->label() }}</p>
                                    </td>
                                    <td class="p-3">
                                        {{ $contribution->contributor_name }}
                                        @if ($contribution->anonymous)
                                            <span class="text-ink-muted">(anonyme)</span>
                                        @endif
                                        <p class="text-ink-muted">{{ $contribution->phone }}</p>
                                    </td>
                                    <td class="p-3 whitespace-nowrap tabular">{{ \App\Support\Money::format($contribution->amount) }}</td>
                                    <td class="p-3">
                                        <span class="status-pill status-pill-{{ $contribution->status->tone() }}">{{ $contribution->status->label() }}</span>
                                        @if ($contribution->refunded_at)
                                            <p class="mt-1 text-ink-muted">{{ $contribution->refund_note }}</p>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <span class="font-mono text-[0.7rem]">{{ $contribution->reference }}</span>
                                        @if ($contribution->provider_token)
                                            <p class="mt-1 truncate font-mono text-[0.7rem] text-ink-muted" title="{{ $contribution->provider_token }}">{{ $contribution->provider_token }}</p>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <form method="POST" action="{{ route('contributions.reconcile', [$event, $contribution]) }}" class="space-y-2">
                                            @csrf

                                            @if ($contribution->status === \App\Enums\ContributionStatus::Pending)
                                                <input name="note" type="text" maxlength="255" placeholder="Note (facultative)" class="field">
                                                <div class="flex flex-wrap gap-2">
                                                    <button type="submit" name="action" value="paid" class="btn btn-primary">Encaisser</button>
                                                    <button type="submit" name="action" value="failed" class="btn btn-secondary">Échouer</button>
                                                </div>
                                            @elseif ($contribution->status === \App\Enums\ContributionStatus::Paid)
                                                <input name="note" type="text" maxlength="255" required placeholder="Référence du remboursement" class="field">
                                                <button type="submit" name="action" value="refunded" class="btn btn-secondary">Rembourser</button>
                                            @elseif (in_array($contribution->status, [\App\Enums\ContributionStatus::Failed, \App\Enums\ContributionStatus::Expired], true))
                                                <input name="note" type="text" maxlength="255" placeholder="Note (facultative)" class="field">
                                                <button type="submit" name="action" value="paid" class="btn btn-secondary">Encaisser hors ligne</button>
                                            @else
                                                <p class="text-ink-muted">Aucune action</p>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
