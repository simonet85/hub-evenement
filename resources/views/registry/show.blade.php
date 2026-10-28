@extends('layouts.invitation')

@section('title', 'Cadeaux et cagnotte — '.$event->name.' — Hub Événement')

@section('content')
    <div class="{{ $event->type->accentClass() }}">
        <article class="card card-invitation overflow-hidden">
            <div class="h-1 bg-accent" aria-hidden="true"></div>

            <div class="p-6 sm:p-10">
                <p class="eyebrow">{{ $event->type->label() }}</p>
                <h1 class="mt-2 font-display text-heading text-ink sm:text-display-lg">
                    Cadeaux et cagnotte
                </h1>
                <p class="mt-2 text-body text-ink-muted">
                    {{ $event->name }} · {{ $event->starts_at->translatedFormat('j F Y') }}
                </p>

                @if ($errors->any())
                    <x-alert tone="danger" class="mt-5">{{ $errors->first() }}</x-alert>
                @endif

                @if (config('services.moneyfusion.driver') === 'fake')
                    <x-alert tone="warning" class="mt-5">
                        Mode simulation local : les paiements ne sont pas réellement débités.
                    </x-alert>
                @endif

                @if ($event->cagnotte_enabled)
                    <section id="cagnotte" class="mt-8 border-t border-line pt-6" aria-labelledby="cagnotte-title">
                        <h2 id="cagnotte-title" class="font-display text-heading text-ink">Cagnotte</h2>

                        @if ($event->cagnotte_message)
                            <p class="mt-2 text-body text-ink-muted">{{ $event->cagnotte_message }}</p>
                        @endif

                        <div class="mt-4 rounded-sm border border-line bg-surface-300 p-4">
                            <x-progress
                                :value="$collected"
                                :max="$event->cagnotte_goal ?? max($collected, 1)"
                                label="Collecté"
                                :value-label="\App\Support\Money::format($collected).($event->cagnotte_goal ? ' / '.\App\Support\Money::format($event->cagnotte_goal) : '')"
                            />
                        </div>

                        <form method="POST" action="{{ route('contributions.cagnotte', $guest->invitation_token) }}" class="mt-5 space-y-4">
                            @csrf

                            <fieldset>
                                <legend class="label">Montant</legend>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    @foreach ([5000, 10000, 25000, 50000] as $suggested)
                                        <label class="flex min-h-[44px] cursor-pointer items-center gap-2 rounded-sm border border-line-strong bg-surface-300 px-3 py-2 text-label">
                                            <input
                                                type="radio"
                                                name="amount"
                                                value="{{ $suggested }}"
                                                @checked((int) old('amount', 10000) === $suggested)
                                            >
                                            <span class="tabular">{{ \App\Support\Money::format($suggested) }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <label for="custom_amount" class="label mt-3">Montant libre</label>
                                <input
                                    id="custom_amount"
                                    name="custom_amount"
                                    type="number"
                                    min="500"
                                    max="2000000"
                                    step="100"
                                    inputmode="numeric"
                                    value="{{ old('custom_amount') }}"
                                    placeholder="Ex. 15 000"
                                    class="field mt-1"
                                >
                                <p class="help mt-1">Laissez vide pour utiliser un montant suggéré. Minimum {{ \App\Support\Money::format(500) }}.</p>
                            </fieldset>

                            <div>
                                <label for="cagnotte_phone" class="label">Téléphone mobile money</label>
                                <input
                                    id="cagnotte_phone"
                                    name="phone"
                                    type="tel"
                                    required
                                    maxlength="30"
                                    value="{{ old('phone', $guest->phone) }}"
                                    placeholder="+225 07 00 00 00 00"
                                    class="field mt-1"
                                >
                            </div>

                            <div>
                                <label for="cagnotte_message" class="label">Message <span class="font-normal text-ink-muted">(facultatif)</span></label>
                                <textarea
                                    id="cagnotte_message"
                                    name="message"
                                    rows="2"
                                    maxlength="300"
                                    class="field mt-1"
                                    placeholder="Un mot pour les heureux mariés…"
                                >{{ old('message') }}</textarea>
                            </div>

                            <label class="flex min-h-[44px] items-center gap-2 text-caption text-ink-muted">
                                <input type="checkbox" name="anonymous" value="1" @checked(old('anonymous'))>
                                Rester anonyme dans la liste des contributions
                            </label>

                            <button type="submit" class="btn btn-primary w-full">Contribuer avec MoneyFusion</button>
                        </form>
                    </section>
                @endif

                @if ($gifts->isNotEmpty())
                    <section class="mt-8 border-t border-line pt-6" aria-labelledby="cadeaux-title">
                        <h2 id="cadeaux-title" class="font-display text-heading text-ink">Liste de cadeaux</h2>
                        <p class="mt-1 text-caption text-ink-muted">
                            Réservez un cadeau ou participez librement à son prix. La progression ne compte que les paiements confirmés.
                        </p>

                        <ul role="list" class="mt-4 space-y-4">
                            @foreach ($gifts as $gift)
                                @php
                                    $paid = $gift->paidTotal();
                                    $total = $gift->totalPrice();
                                    $available = $gift->availableAmount();
                                    $units = $gift->availableUnits();
                                @endphp

                                <li id="cadeau-{{ $gift->id }}" class="rounded-sm border border-line bg-surface-300 p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <h3 class="text-label text-ink">{{ $gift->name }}</h3>
                                            @if ($gift->description)
                                                <p class="mt-1 text-caption text-ink-muted">{{ $gift->description }}</p>
                                            @endif
                                            <p class="mt-1 text-caption text-ink-muted">
                                                {{ \App\Support\Money::format($gift->price) }} × {{ $gift->quantity }} exemplaire{{ $gift->quantity > 1 ? 's' : '' }}
                                            </p>
                                        </div>
                                        @if ($paid >= $total)
                                            <span class="status-pill status-pill-success">Complet</span>
                                        @endif
                                    </div>

                                    <x-progress
                                        class="mt-3"
                                        :value="$paid"
                                        :max="$total"
                                        label="Financé"
                                        :value-label="\App\Support\Money::format($paid).' / '.\App\Support\Money::format($total)"
                                    />

                                    @if ($available > 0)
                                        <form method="POST" action="{{ route('contributions.gift', [$guest->invitation_token, $gift]) }}" class="mt-4 space-y-3 border-t border-line pt-4">
                                            @csrf

                                            <fieldset>
                                                <legend class="label">Votre participation</legend>
                                                <div class="mt-2 flex flex-wrap gap-4 text-caption">
                                                    @if ($units > 0)
                                                        <label class="flex items-center gap-2">
                                                            <input type="radio" name="mode" value="reserve" @checked(old('mode', 'reserve') === 'reserve')>
                                                            Réserver
                                                        </label>
                                                    @endif
                                                    <label class="flex items-center gap-2">
                                                        <input type="radio" name="mode" value="contribute" @checked(old('mode', $units > 0 ? 'reserve' : 'contribute') === 'contribute')>
                                                        Participer librement
                                                    </label>
                                                </div>

                                                @if ($units > 0)
                                                    <label for="quantity_{{ $gift->id }}" class="label mt-3">Exemplaires à réserver</label>
                                                    <select id="quantity_{{ $gift->id }}" name="quantity" class="field mt-1">
                                                        @for ($n = 1; $n <= $units; $n++)
                                                            <option value="{{ $n }}" @selected((int) old('quantity', 1) === $n)>{{ $n }}</option>
                                                        @endfor
                                                    </select>
                                                @endif

                                                <label for="amount_{{ $gift->id }}" class="label mt-3">Montant libre</label>
                                                <input
                                                    id="amount_{{ $gift->id }}"
                                                    name="amount"
                                                    type="number"
                                                    min="500"
                                                    max="{{ $available }}"
                                                    step="100"
                                                    inputmode="numeric"
                                                    value="{{ old('amount', min($gift->price, $available)) }}"
                                                    class="field mt-1"
                                                >
                                                <p class="help mt-1">
                                                    Reste à financer : {{ \App\Support\Money::format($available) }}.
                                                    Le montant libre n'est utilisé qu'en mode « Participer librement ».
                                                </p>
                                            </fieldset>

                                            <div>
                                                <label for="phone_{{ $gift->id }}" class="label">Téléphone mobile money</label>
                                                <input
                                                    id="phone_{{ $gift->id }}"
                                                    name="phone"
                                                    type="tel"
                                                    required
                                                    maxlength="30"
                                                    value="{{ old('phone', $guest->phone) }}"
                                                    placeholder="+225 07 00 00 00 00"
                                                    class="field mt-1"
                                                >
                                            </div>

                                            <div>
                                                <label for="message_{{ $gift->id }}" class="label">Message <span class="font-normal text-ink-muted">(facultatif)</span></label>
                                                <input
                                                    id="message_{{ $gift->id }}"
                                                    name="message"
                                                    type="text"
                                                    maxlength="300"
                                                    value="{{ old('message') }}"
                                                    class="field mt-1"
                                                >
                                            </div>

                                            <label class="flex min-h-[44px] items-center gap-2 text-caption text-ink-muted">
                                                <input type="checkbox" name="anonymous" value="1" @checked(old('anonymous'))>
                                                Rester anonyme dans la liste des contributions
                                            </label>

                                            <button type="submit" class="btn btn-secondary w-full">Payer avec MoneyFusion</button>
                                        </form>
                                    @else
                                        <p class="mt-3 text-caption text-ink-muted">Ce cadeau est entièrement financé. Merci !</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if (! $event->cagnotte_enabled && $gifts->isEmpty())
                    <p class="mt-8 rounded-sm border border-line bg-surface-300 p-4 text-body text-ink-muted">
                        Aucun cadeau ni cagnotte n'est proposé pour cet événement.
                    </p>
                @endif

                @if ($recent->isNotEmpty())
                    <section class="mt-8 border-t border-line pt-6" aria-labelledby="contributions-title">
                        <h2 id="contributions-title" class="font-display text-heading text-ink">Dernières contributions</h2>
                        <ul role="list" class="mt-3 space-y-3">
                            @foreach ($recent as $contribution)
                                <li class="flex flex-wrap items-baseline justify-between gap-2 border-b border-line pb-2 text-caption">
                                    <div class="min-w-0">
                                        <p class="text-ink">{{ $contribution->displayName() }}</p>
                                        <p class="text-ink-muted">
                                            {{ $contribution->isCagnotte() ? 'Cagnotte' : $contribution->label() }}
                                            @if ($contribution->message)
                                                — {{ $contribution->message }}
                                            @endif
                                        </p>
                                    </div>
                                    <p class="tabular text-ink">{{ \App\Support\Money::format($contribution->amount) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <div class="mt-8 flex flex-wrap gap-3 border-t border-line pt-6">
                    <a href="{{ route('invitations.show', $guest->invitation_token) }}" class="btn btn-secondary">
                        Retour à l'invitation
                    </a>
                    <a href="{{ route('invitations.programme', $guest->invitation_token) }}" class="btn btn-secondary">
                        Programme et carte
                    </a>
                </div>
            </div>
        </article>

        <p class="mt-6 text-center text-caption text-ink-muted">
            Paiements sécurisés par MoneyFusion. Ce lien est personnel et confidentiel.
        </p>
    </div>
@endsection
