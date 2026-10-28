<?php

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Services\EventMetrics;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

function pilotageEvent(User $owner, array $overrides = []): Event
{
    return Event::factory()->create(array_merge(['user_id' => $owner->id], $overrides));
}

function pilotageGuest(Event $event, array $overrides = []): Guest
{
    return Guest::factory()->create(array_merge(['event_id' => $event->id], $overrides));
}

it('calcule les indicateurs de pilotage', function () {
    $owner = User::factory()->create();
    $event = pilotageEvent($owner);

    pilotageGuest($event, [
        'name' => 'Famille Koné',
        'allowed_seats' => 4,
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
        'attendee_names' => "Awa\nKoffi",
        'dietary_notes' => 'Sans porc',
        'rsvp_responded_at' => now()->subDays(3),
    ]);
    pilotageGuest($event, [
        'allowed_seats' => 3,
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 3,
        'rsvp_responded_at' => now(),
    ]);
    pilotageGuest($event, [
        'allowed_seats' => 2,
        'rsvp_status' => RsvpStatus::Declined,
        'rsvp_responded_at' => now()->subDay(),
        'rsvp_is_late' => true,
    ]);
    pilotageGuest($event, [
        'allowed_seats' => 2,
    ]);

    expect((new EventMetrics($event))->summary())->toBe([
        'households' => 4,
        'seats' => 11,
        'responded' => 3,
        'attending' => 2,
        'declined' => 1,
        'pending' => 1,
        'coming' => 5,
        'diet_households' => 1,
        'late_responses' => 1,
    ]);
});

it('utilise les places autorisées quand le foyer présent n’a pas précisé de couverts', function () {
    $owner = User::factory()->create();
    $event = pilotageEvent($owner);

    $guest = pilotageGuest($event, [
        'allowed_seats' => 3,
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => null,
        'rsvp_responded_at' => now(),
    ]);

    expect($guest->confirmedSeats())->toBe(3)
        ->and((new EventMetrics($event))->summary()['coming'])->toBe(3);
});

it('calcule l’évolution sur 14 jours avec cumul des réponses antérieures', function () {
    Carbon::setTestNow('2026-10-26 12:00:00');

    $owner = User::factory()->create();
    $event = pilotageEvent($owner);

    pilotageGuest($event, [
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 1,
        'rsvp_responded_at' => now()->subDays(20),
    ]);
    pilotageGuest($event, [
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 1,
        'rsvp_responded_at' => now()->subDays(2),
    ]);
    pilotageGuest($event, [
        'rsvp_status' => RsvpStatus::Declined,
        'rsvp_responded_at' => now(),
    ]);
    pilotageGuest($event);

    $evolution = (new EventMetrics($event))->evolution(14);

    expect($evolution['days'])->toHaveCount(14)
        ->and($evolution['total'])->toBe(2)
        ->and($evolution['max'])->toBe(1)
        ->and($evolution['days'][0]['label'])->toBe('13/10')
        ->and($evolution['days'][0]['date']->toDateString())->toBe('2026-10-13')
        ->and($evolution['days'][0]['count'])->toBe(0)
        ->and($evolution['days'][0]['cumulative'])->toBe(1)
        ->and($evolution['days'][11]['label'])->toBe('24/10')
        ->and($evolution['days'][11]['count'])->toBe(1)
        ->and($evolution['days'][11]['percent'])->toBe(100)
        ->and($evolution['days'][13]['label'])->toBe('26/10')
        ->and($evolution['days'][13]['count'])->toBe(1)
        ->and($evolution['days'][13]['cumulative'])->toBe(3);
});

it('affiche le pilotage au propriétaire et au co-organisateur', function () {
    $owner = User::factory()->create();
    $event = pilotageEvent($owner);
    $coOrganizer = makeCoOrganizer($event);

    pilotageGuest($event, [
        'name' => 'Famille Koné',
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
        'dietary_notes' => 'Sans porc',
        'rsvp_responded_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('pilotage.show', $event))
        ->assertOk()
        ->assertSee('Couverts confirmés')
        ->assertSee('Évolution des réponses')
        ->assertSee('Régimes et allergies')
        ->assertSee('Sans porc')
        ->assertSee('Lien traiteur')
        ->assertSee('Invités en CSV');

    $this->actingAs($coOrganizer)
        ->get(route('pilotage.show', $event))
        ->assertOk();
});

it('refuse le pilotage à un tiers', function () {
    $event = pilotageEvent(User::factory()->create());
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('pilotage.show', $event))
        ->assertForbidden();
});

it('redirige un visiteur vers la connexion pour le pilotage', function () {
    $event = pilotageEvent(User::factory()->create());

    $this->get(route('pilotage.show', $event))
        ->assertRedirect(route('login'));
});

it('relie le pilotage depuis la page de l’événement', function () {
    $owner = User::factory()->create();
    $event = pilotageEvent($owner);

    $this->actingAs($owner)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Pilotage et exports');
});

it('génère un lien traiteur pour chaque durée autorisée', function (int $days) {
    Carbon::setTestNow('2026-10-26 10:00:00');

    $owner = User::factory()->create();
    $event = pilotageEvent($owner);

    $response = $this->actingAs($owner)
        ->post(route('pilotage.caterer-link', $event), ['days' => $days])
        ->assertRedirect(route('pilotage.show', $event))
        ->assertSessionHas('caterer_link');

    $url = session('caterer_link');
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    expect($url)->toContain('/traiteur/'.$event->id)
        ->and($url)->toContain('signature=')
        ->and((int) $query['expires'])->toBe(now()->addDays($days)->getTimestamp());

    $this->get($url)->assertOk()->assertSee($event->name);
})->with([1, 7, 30]);

it('refuse une durée de lien traiteur non autorisée', function () {
    $owner = User::factory()->create();
    $event = pilotageEvent($owner);

    $this->actingAs($owner)
        ->postJson(route('pilotage.caterer-link', $event), ['days' => 3])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('days');
});

it('réserve la génération du lien traiteur aux membres', function () {
    $event = pilotageEvent(User::factory()->create());
    $stranger = User::factory()->create();
    $coOrganizer = makeCoOrganizer($event);

    $this->actingAs($stranger)
        ->post(route('pilotage.caterer-link', $event), ['days' => 7])
        ->assertForbidden();

    $this->actingAs($coOrganizer)
        ->post(route('pilotage.caterer-link', $event), ['days' => 7])
        ->assertRedirect(route('pilotage.show', $event));
});

it('exporte les invités en CSV avec BOM, accents et formules neutralisées', function () {
    $owner = User::factory()->create();
    $event = pilotageEvent($owner, ['name' => 'Mariage Awa & Yao']);

    pilotageGuest($event, [
        'name' => 'Famille Koné',
        'email' => 'awa.kone@example.com',
        'phone' => '+225 07 00 00 00 00',
        'allowed_seats' => 4,
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
        'attendee_names' => "Awa\nKoffi",
        'dietary_notes' => "Sans porc; pas d'arachide",
        'rsvp_message' => 'Merci ; à bientôt',
        'rsvp_responded_at' => now()->subDay(),
    ]);
    pilotageGuest($event, [
        'name' => '=1+1',
        'email' => null,
        'phone' => null,
    ]);

    $response = $this->actingAs($owner)
        ->get(route('exports.guests.csv', $event))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->getContent();

    expect($response->headers->get('content-disposition'))
        ->toContain('attachment')
        ->toContain('-invites-')
        ->toContain('.csv')
        ->and(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue()
        ->and(str_contains($content, "\r\n"))->toBeTrue()
        ->and($content)->toContain('Foyer;E-mail;Téléphone;Places autorisées;Réponse;Places confirmées;Prénoms;Régime / allergies;Message;Répondu le;Réponse hors délai;Pointé le;Pointé par')
        ->and($content)->toContain("Famille Koné;awa.kone@example.com;'+225 07 00 00 00 00;4;Présent;2;Awa, Koffi;\"Sans porc; pas d'arachide\";\"Merci ; à bientôt\";")
        ->and($content)->toContain("'=1+1;");
});

it('exporte les invités en XLSX avec les feuilles Invités et Synthèse', function () {
    $owner = User::factory()->create();
    $event = pilotageEvent($owner);

    pilotageGuest($event, [
        'name' => 'Famille Koné',
        'allowed_seats' => 4,
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
        'dietary_notes' => 'Sans porc',
        'rsvp_responded_at' => now(),
    ]);
    pilotageGuest($event, ['name' => '=1+1']);

    $response = $this->actingAs($owner)
        ->get(route('exports.guests.xlsx', $event))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $content = $response->getContent();
    $sheets = readXlsx($content);
    $guestRows = collect($sheets['Invités']);
    $kone = $guestRows->firstWhere(fn (array $row) => $row[0] === 'Famille Koné');

    expect(str_starts_with($content, 'PK'))->toBeTrue()
        ->and(array_keys($sheets))->toBe(['Invités', 'Synthèse'])
        ->and($sheets['Invités'][0][0])->toBe('Foyer')
        ->and($sheets['Invités'][0][12])->toBe('Pointé par')
        ->and($kone[5])->toBe(2)
        ->and($guestRows->contains(fn (array $row) => $row[0] === '=1+1'))->toBeTrue()
        ->and($sheets['Synthèse'][0])->toBe(['Indicateur', 'Valeur'])
        ->and($sheets['Synthèse'])->toContain(['Couverts confirmés', 2])
        ->and($sheets['Synthèse'])->toContain(['Foyers', 2]);
});

it('produit un dossier traiteur PDF', function () {
    $owner = User::factory()->create();
    $event = pilotageEvent($owner);

    pilotageGuest($event, [
        'name' => 'Famille Koné',
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
        'dietary_notes' => 'Sans porc',
        'rsvp_responded_at' => now(),
    ]);

    $response = $this->actingAs($owner)
        ->get(route('exports.caterer.pdf', $event))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect(str_starts_with($response->getContent(), '%PDF'))->toBeTrue()
        ->and($response->headers->get('content-disposition'))
        ->toContain('attachment')
        ->toContain('-couverts-traiteur-')
        ->toContain('.pdf');
});

it('réserve les exports invités aux membres', function () {
    $event = pilotageEvent(User::factory()->create());
    $stranger = User::factory()->create();
    $coOrganizer = makeCoOrganizer($event);

    foreach (['exports.guests.csv', 'exports.guests.xlsx', 'exports.caterer.pdf'] as $route) {
        $this->actingAs($stranger)
            ->get(route($route, $event))
            ->assertForbidden();
    }

    $this->actingAs($coOrganizer)
        ->get(route('exports.guests.csv', $event))
        ->assertOk();
});
