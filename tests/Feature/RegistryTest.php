<?php

use App\Enums\ContributionKind;
use App\Enums\ContributionStatus;
use App\Models\Contribution;
use App\Models\Event;
use App\Models\Gift;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    config(['services.moneyfusion.driver' => 'fake']);
});

function registryEvent(User $owner, array $overrides = []): Event
{
    return Event::factory()->create(array_merge(['user_id' => $owner->id], $overrides));
}

function registryGuest(Event $event, array $overrides = []): Guest
{
    return Guest::factory()->create(array_merge([
        'event_id' => $event->id,
        'phone' => '+225 07 00 00 00 00',
    ], $overrides));
}

function registryGift(Event $event, array $overrides = []): Gift
{
    return Gift::factory()->create(array_merge([
        'event_id' => $event->id,
        'name' => 'Service de table',
        'price' => 25000,
        'quantity' => 1,
    ], $overrides));
}

function registryContribution(Event $event, array $overrides = []): Contribution
{
    return Contribution::factory()->create(array_merge([
        'event_id' => $event->id,
        'contributor_name' => 'Awa Koné',
    ], $overrides));
}

function registryHttpDriver(): void
{
    config([
        'services.moneyfusion.driver' => 'http',
        'moneyfusion.api_url' => 'https://api.moneyfusion.test/pay',
        'moneyfusion.app_key' => 'app/key',
        'moneyfusion.check_payment_url' => 'https://pay.moneyfusion.test/paiementNotif',
    ]);
}

it('ajoute un cadeau au registre', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);

    $this->actingAs($owner)
        ->post(route('gifts.store', $event), [
            'name' => 'Machine à coudre',
            'description' => 'Pour la maison des jeunes',
            'price' => 75000,
            'quantity' => 2,
        ])
        ->assertRedirect(route('registry.index', $event));

    $gift = $event->gifts()->first();

    expect($gift->name)->toBe('Machine à coudre')
        ->and($gift->price)->toBe(75000)
        ->and($gift->quantity)->toBe(2)
        ->and($gift->is_active)->toBeTrue();
});

it('modifie et masque un cadeau', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $gift = registryGift($event);

    $this->actingAs($owner)
        ->put(route('gifts.update', [$event, $gift]), [
            'name' => 'Service en porcelaine',
            'description' => '',
            'price' => 30000,
            'quantity' => 3,
            'is_active' => '0',
        ])
        ->assertRedirect(route('registry.index', $event));

    $gift->refresh();

    expect($gift->name)->toBe('Service en porcelaine')
        ->and($gift->price)->toBe(30000)
        ->and($gift->quantity)->toBe(3)
        ->and($gift->is_active)->toBeFalse();
});

it('refuse un cadeau sans prix valide', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);

    $this->actingAs($owner)
        ->post(route('gifts.store', $event), [
            'name' => 'Cadeau',
            'price' => 100,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('price');
});

it('reserve la gestion des cadeaux aux membres de l’événement', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $event = registryEvent($owner);
    $gift = registryGift($event);

    $this->actingAs($outsider)->get(route('registry.index', $event))->assertForbidden();
    $this->actingAs($outsider)->post(route('gifts.store', $event), [
        'name' => 'Cadeau',
        'price' => 5000,
        'quantity' => 1,
    ])->assertForbidden();
    $this->actingAs($outsider)->put(route('gifts.update', [$event, $gift]), [
        'name' => 'Cadeau',
        'price' => 5000,
        'quantity' => 1,
    ])->assertForbidden();
});

it('laisse un co-organisateur gérer les cadeaux', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $coOrganizer = makeCoOrganizer($event);

    $this->actingAs($coOrganizer)
        ->post(route('gifts.store', $event), [
            'name' => 'Lot de draps',
            'price' => 15000,
            'quantity' => 1,
        ])
        ->assertRedirect(route('registry.index', $event));

    expect($event->gifts()->count())->toBe(1);
});

it('configure la cagnotte', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);

    $this->actingAs($owner)
        ->put(route('cagnotte.update', $event), [
            'enabled' => '1',
            'goal' => 500000,
            'message' => 'Votre présence est notre plus beau cadeau.',
        ])
        ->assertRedirect(route('registry.index', $event));

    $event->refresh();

    expect($event->cagnotte_enabled)->toBeTrue()
        ->and($event->cagnotte_goal)->toBe(500000)
        ->and($event->cagnotte_message)->toBe('Votre présence est notre plus beau cadeau.');
});

it('refuse un objectif de cagnotte trop bas', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);

    $this->actingAs($owner)
        ->put(route('cagnotte.update', $event), [
            'enabled' => '1',
            'goal' => 500,
        ])
        ->assertSessionHasErrors('goal');
});

it('affiche le journal et les totaux', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $gift = registryGift($event);

    registryContribution($event, ['amount' => 25000, 'status' => ContributionStatus::Paid, 'paid_at' => now(), 'gift_id' => $gift->id, 'kind' => ContributionKind::Gift]);
    registryContribution($event, ['amount' => 10000, 'status' => ContributionStatus::Pending]);
    registryContribution($event, ['amount' => 5000, 'status' => ContributionStatus::Refunded, 'paid_at' => now()->subDay(), 'refunded_at' => now()]);

    $response = $this->actingAs($owner)->get(route('registry.index', $event));

    $response->assertOk()
        ->assertSee('Journal des paiements')
        ->assertSee('25 000 FCFA')
        ->assertSee('10 000 FCFA')
        ->assertSee('5 000 FCFA')
        ->assertSee('Financé')
        ->assertSee('Encaisser');
});

it('renvoie 404 pour un cadeau d’un autre événement', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $otherEvent = registryEvent($owner);
    $gift = registryGift($otherEvent);

    $this->actingAs($owner)
        ->put(route('gifts.update', [$event, $gift]), [
            'name' => 'Triche',
            'price' => 5000,
            'quantity' => 1,
        ])
        ->assertNotFound();
});

it('propose les cadeaux depuis l’invitation quand une cagnotte est ouverte', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner, ['cagnotte_enabled' => true]);
    $guest = registryGuest($event);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertSee(route('invitations.registry', $guest->invitation_token));
});

it('cache le lien cadeaux sans cagnotte ni cadeau actif', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    registryGift($event, ['is_active' => false]);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertDontSee(route('invitations.registry', $guest->invitation_token));
});

it('affiche la cagnotte et les cadeaux actifs côté invité', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner, ['cagnotte_enabled' => true, 'cagnotte_goal' => 200000]);
    $guest = registryGuest($event);
    registryGift($event, ['name' => 'Cuisinière']);
    registryGift($event, ['name' => 'Cadeau secret', 'is_active' => false]);

    $this->get(route('invitations.registry', $guest->invitation_token))
        ->assertOk()
        ->assertSee('Cagnotte')
        ->assertSee('Cuisinière')
        ->assertSee('200 000 FCFA')
        ->assertDontSee('Cadeau secret');
});

it('propose seulement une participation libre quand aucun exemplaire ne reste', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    $gift = registryGift($event, ['price' => 10000, 'quantity' => 1]);

    registryContribution($event, [
        'amount' => 6000,
        'status' => ContributionStatus::Paid,
        'paid_at' => now(),
        'gift_id' => $gift->id,
        'kind' => ContributionKind::Gift,
    ]);

    $this->get(route('invitations.registry', $guest->invitation_token))
        ->assertOk()
        ->assertSee('Reste à financer : 4 000 FCFA')
        ->assertSee('Participer librement')
        ->assertSee('name="mode" value="contribute" checked', false)
        ->assertDontSee('name="mode" value="reserve"', false);
});

it('affiche les contributions payées et respecte l’anonymat', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);

    registryContribution($event, ['contributor_name' => 'Fatou Traoré', 'amount' => 15000, 'status' => ContributionStatus::Paid, 'paid_at' => now()]);
    registryContribution($event, ['contributor_name' => 'Moussa Anonyme', 'amount' => 20000, 'status' => ContributionStatus::Paid, 'paid_at' => now(), 'anonymous' => true]);
    registryContribution($event, ['contributor_name' => 'En attente', 'amount' => 9000, 'status' => ContributionStatus::Pending]);

    $this->get(route('invitations.registry', $guest->invitation_token))
        ->assertOk()
        ->assertSee('Fatou Traoré')
        ->assertSee('Invité anonyme')
        ->assertDontSee('Moussa Anonyme')
        ->assertDontSee('9 000 FCFA');
});

it('renvoie 404 pour un jeton invité inconnu', function () {
    $this->get(route('invitations.registry', 'jeton-inconnu'))->assertNotFound();
});

it('calcule le montant d’une réservation côté serveur', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    $gift = registryGift($event, ['price' => 25000, 'quantity' => 3]);

    $this->post(route('contributions.gift', [$guest->invitation_token, $gift]), [
        'mode' => 'reserve',
        'quantity' => 2,
        'amount' => 1,
        'phone' => '+225 07 11 22 33 44',
    ])->assertRedirect();

    $contribution = Contribution::query()->sole();

    expect($contribution->amount)->toBe(50000)
        ->and($contribution->quantity)->toBe(2)
        ->and($contribution->kind)->toBe(ContributionKind::Gift)
        ->and($contribution->status)->toBe(ContributionStatus::Pending)
        ->and($contribution->provider_token)->toStartWith('sim_')
        ->and($contribution->provider_url)->toContain('/paiement-simule/');
});

it('enregistre une participation libre à un cadeau', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    $gift = registryGift($event);

    $this->post(route('contributions.gift', [$guest->invitation_token, $gift]), [
        'mode' => 'contribute',
        'amount' => 7000,
        'phone' => '+225 07 11 22 33 44',
        'message' => 'Avec plaisir',
    ])->assertRedirect();

    $contribution = Contribution::query()->sole();

    expect($contribution->amount)->toBe(7000)
        ->and($contribution->quantity)->toBeNull()
        ->and($contribution->message)->toBe('Avec plaisir');
});

it('refuse une participation au-delà du disponible', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    $gift = registryGift($event, ['price' => 25000]);

    registryContribution($event, ['gift_id' => $gift->id, 'kind' => ContributionKind::Gift, 'amount' => 20000, 'status' => ContributionStatus::Paid, 'paid_at' => now()]);

    $this->post(route('contributions.gift', [$guest->invitation_token, $gift]), [
        'mode' => 'contribute',
        'amount' => 6000,
        'phone' => '+225 07 11 22 33 44',
    ])->assertSessionHasErrors('amount');

    expect(Contribution::query()->count())->toBe(1);
});

it('retient les paiements en attente dans le disponible', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    $gift = registryGift($event, ['price' => 25000, 'quantity' => 2]);

    registryContribution($event, ['gift_id' => $gift->id, 'kind' => ContributionKind::Gift, 'amount' => 25000, 'status' => ContributionStatus::Pending, 'expires_at' => now()->addHour()]);

    $this->post(route('contributions.gift', [$guest->invitation_token, $gift]), [
        'mode' => 'reserve',
        'quantity' => 2,
        'phone' => '+225 07 11 22 33 44',
    ])->assertSessionHasErrors('quantity');
});

it('refuse une participation sous le minimum', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    $gift = registryGift($event);

    $this->post(route('contributions.gift', [$guest->invitation_token, $gift]), [
        'mode' => 'contribute',
        'amount' => 400,
        'phone' => '+225 07 11 22 33 44',
    ])->assertSessionHasErrors('amount');
});

it('enregistre une contribution à la cagnotte', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner, ['cagnotte_enabled' => true]);
    $guest = registryGuest($event);

    $this->post(route('contributions.cagnotte', $guest->invitation_token), [
        'custom_amount' => 15000,
        'phone' => '+225 05 44 33 22 11',
        'message' => 'Pour le plus beau des jours',
        'anonymous' => '1',
    ])->assertRedirect();

    $contribution = Contribution::query()->sole();

    expect($contribution->amount)->toBe(15000)
        ->and($contribution->isCagnotte())->toBeTrue()
        ->and($contribution->anonymous)->toBeTrue()
        ->and($contribution->phone)->toBe('+225 05 44 33 22 11')
        ->and($contribution->status)->toBe(ContributionStatus::Pending);
});

it('refuse une cagnotte fermée', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner, ['cagnotte_enabled' => false]);
    $guest = registryGuest($event);

    $this->post(route('contributions.cagnotte', $guest->invitation_token), [
        'amount' => 10000,
        'phone' => '+225 07 11 22 33 44',
    ])->assertNotFound();

    expect(Contribution::query()->count())->toBe(0);
});

it('refuse une contribution à un cadeau masqué', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    $gift = registryGift($event, ['is_active' => false]);

    $this->post(route('contributions.gift', [$guest->invitation_token, $gift]), [
        'mode' => 'contribute',
        'amount' => 5000,
        'phone' => '+225 07 11 22 33 44',
    ])->assertNotFound();
});

it('exige un téléphone valide', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    $gift = registryGift($event);

    $this->post(route('contributions.gift', [$guest->invitation_token, $gift]), [
        'mode' => 'contribute',
        'amount' => 5000,
        'phone' => 'abc',
    ])->assertSessionHasErrors('phone');
});

it('marque la contribution en échec si le fournisseur refuse', function () {
    registryHttpDriver();
    Http::fake(['https://api.moneyfusion.test/pay' => Http::response('boom', 500)]);

    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    $gift = registryGift($event, ['price' => 5000]);

    $this->post(route('contributions.gift', [$guest->invitation_token, $gift]), [
        'mode' => 'reserve',
        'quantity' => 1,
        'phone' => '+225 07 11 22 33 44',
    ])->assertRedirect(route('invitations.registry', $guest->invitation_token).'#cadeau-'.$gift->id)
        ->assertSessionHasErrors('payment');

    $contribution = Contribution::query()->sole();

    expect($contribution->status)->toBe(ContributionStatus::Failed)
        ->and($contribution->provider_token)->toBeNull()
        ->and($contribution->failed_at)->not->toBeNull();
});

it('redirige vers la page MoneyFusion en mode réel', function () {
    registryHttpDriver();
    Http::fake(['https://api.moneyfusion.test/pay' => Http::response([
        'statut' => true,
        'token' => 'tok_real_123',
        'url' => 'https://pay.moneyfusion.test/checkout/tok_real_123',
    ])]);

    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $guest = registryGuest($event);
    $gift = registryGift($event, ['price' => 5000]);

    $this->post(route('contributions.gift', [$guest->invitation_token, $gift]), [
        'mode' => 'reserve',
        'quantity' => 1,
        'phone' => '+225 07 11 22 33 44',
    ])->assertRedirect('https://pay.moneyfusion.test/checkout/tok_real_123');

    $contribution = Contribution::query()->sole();

    expect($contribution->provider_token)->toBe('tok_real_123')
        ->and($contribution->provider_url)->toBe('https://pay.moneyfusion.test/checkout/tok_real_123');
});

it('refuse un webhook sans signature', function () {
    $event = registryEvent(User::factory()->create());
    $contribution = registryContribution($event, ['provider_token' => 'tok_unsigned']);

    $this->post(route('payments.webhook', $contribution))->assertForbidden();
});

it('confirme un paiement vérifié par le webhook', function () {
    registryHttpDriver();
    $event = registryEvent(User::factory()->create());
    $contribution = registryContribution($event, ['provider_token' => 'tok_paid', 'amount' => 12000]);

    Http::fake(['https://pay.moneyfusion.test/paiementNotif/tok_paid' => Http::response([
        'statut' => true,
        'data' => ['statut' => 'paid', 'Montant' => 12000, 'tokenPay' => 'tok_paid'],
    ])]);

    $url = URL::temporarySignedRoute('payments.webhook', now()->addDay(), ['contribution' => $contribution]);

    $this->post($url)->assertOk()->assertJson(['status' => 'paid']);

    $contribution->refresh();

    expect($contribution->status)->toBe(ContributionStatus::Paid)
        ->and($contribution->paid_at)->not->toBeNull();
});

it('ignore un webhook rejoué', function () {
    registryHttpDriver();
    $event = registryEvent(User::factory()->create());
    $contribution = registryContribution($event, ['provider_token' => 'tok_replay', 'amount' => 12000]);

    Http::fake(['https://pay.moneyfusion.test/paiementNotif/tok_replay' => Http::response([
        'statut' => true,
        'data' => ['statut' => 'paid', 'Montant' => 12000],
    ])]);

    $url = URL::temporarySignedRoute('payments.webhook', now()->addDay(), ['contribution' => $contribution]);

    $this->post($url)->assertOk();

    $paidAt = $contribution->fresh()->paid_at;

    $this->post($url)->assertOk()->assertJson(['status' => 'already_processed']);

    expect($contribution->fresh()->paid_at->equalTo($paidAt))->toBeTrue();
});

it('marque un webhook vérifié en échec', function () {
    registryHttpDriver();
    $event = registryEvent(User::factory()->create());
    $contribution = registryContribution($event, ['provider_token' => 'tok_failed']);

    Http::fake(['https://pay.moneyfusion.test/paiementNotif/tok_failed' => Http::response([
        'statut' => true,
        'data' => ['statut' => 'no paid', 'Montant' => $contribution->amount],
    ])]);

    $url = URL::temporarySignedRoute('payments.webhook', now()->addDay(), ['contribution' => $contribution]);

    $this->post($url)->assertOk()->assertJson(['status' => 'failed']);

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Failed);
});

it('ne crédite pas un montant différent', function () {
    registryHttpDriver();
    $event = registryEvent(User::factory()->create());
    $contribution = registryContribution($event, ['provider_token' => 'tok_mismatch', 'amount' => 12000]);

    Http::fake(['https://pay.moneyfusion.test/paiementNotif/tok_mismatch' => Http::response([
        'statut' => true,
        'data' => ['statut' => 'paid', 'Montant' => 99000],
    ])]);

    $url = URL::temporarySignedRoute('payments.webhook', now()->addDay(), ['contribution' => $contribution]);

    $this->post($url)->assertOk()->assertJson(['status' => 'pending']);

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Pending);
});

it('renvoie 503 quand la vérification est indisponible', function () {
    registryHttpDriver();
    $event = registryEvent(User::factory()->create());
    $contribution = registryContribution($event, ['provider_token' => 'tok_down']);

    Http::fake(['https://pay.moneyfusion.test/paiementNotif/tok_down' => Http::response('down', 500)]);

    $url = URL::temporarySignedRoute('payments.webhook', now()->addDay(), ['contribution' => $contribution]);

    $this->post($url)->assertStatus(503);

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Pending);
});

it('affiche la page de retour signée', function () {
    $event = registryEvent(User::factory()->create());
    $guest = registryGuest($event);
    $contribution = registryContribution($event, [
        'guest_id' => $guest->id,
        'amount' => 12000,
        'status' => ContributionStatus::Paid,
        'paid_at' => now(),
    ]);

    $url = URL::temporarySignedRoute('payments.return', now()->addHours(24), ['contribution' => $contribution]);

    $this->get($url)
        ->assertOk()
        ->assertSee('votre participation est confirmée')
        ->assertSee('12 000 FCFA')
        ->assertSee($contribution->reference);
});

it('exige une signature valide pour le retour', function () {
    $event = registryEvent(User::factory()->create());
    $contribution = registryContribution($event);

    $this->get(route('payments.return', $contribution))->assertForbidden();
    $this->get(URL::temporarySignedRoute('payments.return', now()->subMinute(), ['contribution' => $contribution]))
        ->assertForbidden();
});

it('paie une contribution depuis le simulateur local', function () {
    $event = registryEvent(User::factory()->create());
    $contribution = registryContribution($event, ['provider_token' => 'sim_token']);

    $this->get(route('payments.simulator', 'sim_token'))
        ->assertOk()
        ->assertSee('Simuler un paiement réussi');

    $this->post(route('payments.simulator.complete', 'sim_token'), ['action' => 'paid'])
        ->assertRedirect();

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Paid);
});

it('gère un échec depuis le simulateur', function () {
    $event = registryEvent(User::factory()->create());
    $contribution = registryContribution($event, ['provider_token' => 'sim_fail']);

    $this->post(route('payments.simulator.complete', 'sim_fail'), ['action' => 'failed'])
        ->assertRedirect();

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Failed);
});

it('ferme le simulateur en mode réel', function () {
    registryHttpDriver();
    $event = registryEvent(User::factory()->create());
    registryContribution($event, ['provider_token' => 'sim_closed']);

    $this->get(route('payments.simulator', 'sim_closed'))->assertNotFound();
});

it('encaisse manuellement un paiement en attente', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $contribution = registryContribution($event);

    $this->actingAs($owner)
        ->post(route('contributions.reconcile', [$event, $contribution]), [
            'action' => 'paid',
            'note' => 'Reçu en espèces',
        ])
        ->assertRedirect(route('registry.index', $event).'#journal');

    $contribution->refresh();

    expect($contribution->status)->toBe(ContributionStatus::Paid)
        ->and($contribution->reconciled_by)->toBe($owner->id)
        ->and($contribution->reconciliation_note)->toBe('Reçu en espèces')
        ->and($contribution->paid_at)->not->toBeNull();
});

it('rembourse un paiement avec une note obligatoire', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $contribution = registryContribution($event, ['status' => ContributionStatus::Paid, 'paid_at' => now()]);

    $this->actingAs($owner)
        ->post(route('contributions.reconcile', [$event, $contribution]), ['action' => 'refunded'])
        ->assertSessionHasErrors('note');

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Paid);

    $this->actingAs($owner)
        ->post(route('contributions.reconcile', [$event, $contribution]), [
            'action' => 'refunded',
            'note' => 'Remboursement Wave 1234',
        ])
        ->assertRedirect(route('registry.index', $event).'#journal');

    $contribution->refresh();

    expect($contribution->status)->toBe(ContributionStatus::Refunded)
        ->and($contribution->refund_note)->toBe('Remboursement Wave 1234')
        ->and($contribution->refunded_by)->toBe($owner->id);
});

it('refuse un rapprochement impossible', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $contribution = registryContribution($event, ['status' => ContributionStatus::Refunded, 'refunded_at' => now()]);

    $this->actingAs($owner)
        ->post(route('contributions.reconcile', [$event, $contribution]), ['action' => 'paid'])
        ->assertSessionHasErrors('action');

    expect($contribution->fresh()->status)->toBe(ContributionStatus::Refunded);
});

it('refuse le rapprochement à un tiers', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $event = registryEvent($owner);
    $contribution = registryContribution($event);

    $this->actingAs($outsider)
        ->post(route('contributions.reconcile', [$event, $contribution]), ['action' => 'paid'])
        ->assertForbidden();
});

it('renvoie 404 pour une contribution d’un autre événement', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $otherEvent = registryEvent($owner);
    $contribution = registryContribution($otherEvent);

    $this->actingAs($owner)
        ->post(route('contributions.reconcile', [$event, $contribution]), ['action' => 'paid'])
        ->assertNotFound();
});

it('expire les contributions en attente dépassées', function () {
    $event = registryEvent(User::factory()->create());

    $stale = registryContribution($event, ['expires_at' => now()->subMinute()]);
    $fresh = registryContribution($event, ['expires_at' => now()->addHour()]);
    $paid = registryContribution($event, ['expires_at' => now()->subHour(), 'status' => ContributionStatus::Paid, 'paid_at' => now()]);
    $refunded = registryContribution($event, ['expires_at' => now()->subHour(), 'status' => ContributionStatus::Refunded, 'refunded_at' => now()]);

    $this->artisan('contributions:expire')->assertSuccessful();

    expect($stale->fresh()->status)->toBe(ContributionStatus::Expired)
        ->and($fresh->fresh()->status)->toBe(ContributionStatus::Pending)
        ->and($paid->fresh()->status)->toBe(ContributionStatus::Paid)
        ->and($refunded->fresh()->status)->toBe(ContributionStatus::Refunded);
});

it('calcule la progression et le disponible sans les remboursés', function () {
    $event = registryEvent(User::factory()->create());
    $gift = registryGift($event, ['price' => 20000, 'quantity' => 2]);

    registryContribution($event, ['gift_id' => $gift->id, 'kind' => ContributionKind::Gift, 'amount' => 20000, 'status' => ContributionStatus::Paid, 'paid_at' => now()]);
    registryContribution($event, ['gift_id' => $gift->id, 'kind' => ContributionKind::Gift, 'amount' => 5000, 'status' => ContributionStatus::Refunded, 'paid_at' => now()->subDay(), 'refunded_at' => now()]);
    registryContribution($event, ['gift_id' => $gift->id, 'kind' => ContributionKind::Gift, 'amount' => 3000, 'status' => ContributionStatus::Pending, 'expires_at' => now()->addHour()]);

    expect($gift->paidTotal())->toBe(20000)
        ->and($gift->pendingTotal())->toBe(3000)
        ->and($gift->availableAmount())->toBe(17000)
        ->and($gift->progressPercent())->toBe(50.0)
        ->and($gift->isFunded())->toBeFalse();
});

it('échappe les textes libres', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    $gift = registryGift($event, ['name' => '<script>alert(1)</script>']);

    $this->actingAs($owner)
        ->get(route('registry.index', $event))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('signale les paiements en attente sur le tableau de bord', function () {
    $owner = User::factory()->create();
    $event = registryEvent($owner);
    registryContribution($event);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('1 paiement en attente');
});
