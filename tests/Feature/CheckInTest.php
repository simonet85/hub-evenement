<?php

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;

it('génère un jeton d’entrée unique et distinct du jeton d’invitation', function () {
    $first = Guest::factory()->create();
    $second = Guest::factory()->create();

    expect($first->entry_token)->toHaveLength(48)
        ->and($first->entry_token)->not->toBe($first->invitation_token)
        ->and($second->entry_token)->not->toBe($first->entry_token);
});

it('affiche le QR code dans l’invitation du foyer', function () {
    $guest = Guest::factory()->create([
        'name' => 'Famille Koné',
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
        'rsvp_responded_at' => now(),
    ]);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertSee("Présentez ce code à l'entrée", false)
        ->assertSee('data:image/svg+xml;base64,', false)
        ->assertSee('Télécharger le QR')
        ->assertSee("alt=\"QR code d'entrée pour Famille Koné\"", false);
});

it('ne montre pas le QR avant la confirmation du foyer', function () {
    $guest = Guest::factory()->create(['name' => 'Famille Bamba']);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertDontSee("Présentez ce code à l'entrée", false)
        ->assertDontSee('data:image/svg+xml;base64,', false)
        ->assertSee("Votre QR d'entrée apparaîtra dès que vous aurez confirmé votre présence.", false)
        ->assertSee('Répondre à l’invitation');
});

it('télécharge le QR SVG du foyer pour un organisateur ou un co-organisateur', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $coOrganizer = makeCoOrganizer($event);

    $response = $this->actingAs($coOrganizer)
        ->get(route('check-in.qr', [$event, $guest]))
        ->assertOk()
        ->assertHeader('content-type', 'image/svg+xml; charset=UTF-8');

    expect($response->getContent())->toContain('<svg')
        ->and($response->headers->get('content-disposition'))->toContain('attachment');
});

it('refuse le QR d’un foyer d’un autre événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $otherEvent = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);

    $this->actingAs($owner)
        ->get(route('check-in.qr', [$otherEvent, $guest]))
        ->assertNotFound();
});

it('ouvre la page de scan au propriétaire et au co-organisateur', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $coOrganizer = makeCoOrganizer($event);

    $this->actingAs($owner)
        ->get(route('check-in.scan', $event))
        ->assertOk()
        ->assertSee('Scanner un QR')
        ->assertSee("Saisir le code ou l'URL", false);

    $this->actingAs($coOrganizer)
        ->get(route('check-in.scan', $event))
        ->assertOk();
});

it('refuse la page de scan à un tiers', function () {
    $event = Event::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('check-in.scan', $event))
        ->assertForbidden();
});

it('redirige un visiteur non connecté vers la connexion', function () {
    $guest = Guest::factory()->create();

    $this->get(route('check-in.show', $guest->entry_token))
        ->assertRedirect(route('login'));

    $this->get(route('check-in.scan', $guest->event))
        ->assertRedirect(route('login'));
});

it('revient au pointage après la connexion', function () {
    $owner = User::factory()->create(['password' => 'motdepasse123']);
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $url = route('check-in.show', $guest->entry_token);

    $this->get($url)->assertRedirect(route('login'));

    $this->post(route('login'), [
        'email' => $owner->email,
        'password' => 'motdepasse123',
    ])->assertRedirect($url);

    $this->get($url)->assertOk();
});

it('pointe un foyer présent directement et met à jour les compteurs', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create([
        'event_id' => $event->id,
        'name' => 'Famille Koné',
        'allowed_seats' => 4,
    ]);
    $guest->forceFill([
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 3,
        'rsvp_responded_at' => now(),
    ])->save();

    $this->actingAs($owner)
        ->postJson(route('check-in.record', $event), ['code' => $guest->entryUrl()])
        ->assertOk()
        ->assertJsonPath('status', 'checked_in')
        ->assertJsonPath('guest.name', 'Famille Koné')
        ->assertJsonPath('guest.checked_in_by', $owner->name)
        ->assertJsonPath('counters.checked_in_households', 1)
        ->assertJsonPath('counters.households', 1)
        ->assertJsonPath('counters.checked_in_seats', 3)
        ->assertJsonPath('counters.seats', 4);

    $guest->refresh();

    expect($guest->checked_in_at)->not->toBeNull()
        ->and($guest->checked_in_by)->toBe($owner->id);
});

it('signale un scan répété sans remplacer l’horodatage', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $guest->forceFill([
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
        'rsvp_responded_at' => now(),
        'checked_in_at' => now()->subMinutes(30),
        'checked_in_by' => $owner->id,
    ])->save();
    $firstCheckIn = $guest->checked_in_at;

    $this->actingAs($owner)
        ->postJson(route('check-in.record', $event), ['code' => $guest->entry_token])
        ->assertOk()
        ->assertJsonPath('status', 'already_checked_in');

    expect($guest->fresh()->checked_in_at->equalTo($firstCheckIn))->toBeTrue();
});

it('demande une confirmation pour un foyer sans réponse ou décliné', function (RsvpStatus $status) {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create([
        'event_id' => $event->id,
        'allowed_seats' => 2,
    ]);
    $guest->forceFill([
        'rsvp_status' => $status,
        'rsvp_responded_at' => $status === RsvpStatus::Pending ? null : now(),
    ])->save();

    $this->actingAs($owner)
        ->postJson(route('check-in.record', $event), ['code' => $guest->entry_token])
        ->assertOk()
        ->assertJsonPath('status', 'requires_confirmation');

    expect($guest->fresh()->checked_in_at)->toBeNull();

    $this->actingAs($owner)
        ->postJson(route('check-in.record', $event), [
            'code' => $guest->entry_token,
            'confirm' => true,
        ])
        ->assertOk()
        ->assertJsonPath('status', 'checked_in')
        ->assertJsonPath('guest.rsvp_label', $status->label());

    expect($guest->fresh()->checked_in_at)->not->toBeNull();
})->with([
    'sans réponse' => [RsvpStatus::Pending],
    'déclaré absent' => [RsvpStatus::Declined],
]);

it('refuse un jeton inconnu sans écrire de pointage', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);

    $this->actingAs($owner)
        ->postJson(route('check-in.record', $event), ['code' => 'jeton-inconnu'])
        ->assertNotFound()
        ->assertJsonPath('status', 'invalid');

    expect($guest->fresh()->checked_in_at)->toBeNull();
});

it('refuse le jeton d’un foyer d’un autre événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $otherEvent = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $otherEvent->id]);

    $this->actingAs($owner)
        ->postJson(route('check-in.record', $event), ['code' => $guest->entryUrl()])
        ->assertNotFound()
        ->assertJsonPath('status', 'invalid');

    expect($guest->fresh()->checked_in_at)->toBeNull();
});

it('annule un pointage depuis le scan', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $guest->forceFill([
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
        'checked_in_at' => now(),
        'checked_in_by' => $owner->id,
    ])->save();
    $coOrganizer = makeCoOrganizer($event);

    $this->actingAs($coOrganizer)
        ->deleteJson(route('check-in.undo', [$event, $guest]))
        ->assertOk()
        ->assertJsonPath('status', 'undone')
        ->assertJsonPath('counters.checked_in_households', 0)
        ->assertJsonPath('counters.checked_in_seats', 0);

    $guest->refresh();

    expect($guest->checked_in_at)->toBeNull()
        ->and($guest->checked_in_by)->toBeNull();
});

it('affiche la page directe sans JavaScript et sans indexation', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create([
        'event_id' => $event->id,
        'name' => 'Famille Awa',
        'allowed_seats' => 3,
    ]);
    $guest->forceFill([
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
    ])->save();

    $this->actingAs($owner)
        ->get(route('check-in.show', $guest->entry_token))
        ->assertOk()
        ->assertSee("Pointage d'entrée", false)
        ->assertSee('Famille Awa')
        ->assertSee('Confirmer l’entrée', false)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('<meta name="referrer" content="no-referrer">', false);
});

it('pointe depuis la page directe même après la date limite', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create([
        'user_id' => $owner->id,
        'rsvp_deadline' => now()->subWeek()->toDateString(),
    ]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $guest->forceFill([
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 1,
    ])->save();

    $this->actingAs($owner)
        ->post(route('check-in.store', $guest->entry_token))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($guest->fresh()->checked_in_at)->not->toBeNull();
});

it('exige une confirmation sur la page directe pour un foyer non confirmé', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);

    $this->actingAs($owner)
        ->post(route('check-in.store', $guest->entry_token))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($guest->fresh()->checked_in_at)->toBeNull();

    $this->actingAs($owner)
        ->post(route('check-in.store', $guest->entry_token), ['confirm' => 1])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($guest->fresh()->checked_in_at)->not->toBeNull();
});

it('annule un pointage depuis la page directe', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $guest->forceFill([
        'checked_in_at' => now(),
        'checked_in_by' => $owner->id,
    ])->save();

    $this->actingAs($owner)
        ->post(route('check-in.undo.form', $guest->entry_token))
        ->assertRedirect()
        ->assertSessionHas('status', 'Pointage annulé pour '.$guest->name.'.');

    expect($guest->fresh()->checked_in_at)->toBeNull();
});

it('refuse la page directe et le pointage à un tiers', function () {
    $guest = Guest::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('check-in.show', $guest->entry_token))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->post(route('check-in.store', $guest->entry_token))
        ->assertForbidden();

    expect($guest->fresh()->checked_in_at)->toBeNull();
});

it('refuse le scan, le QR et l’annulation à un tiers', function () {
    $event = Event::factory()->create();
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->postJson(route('check-in.record', $event), ['code' => $guest->entry_token])
        ->assertForbidden();

    $this->actingAs($stranger)
        ->get(route('check-in.qr', [$event, $guest]))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->deleteJson(route('check-in.undo', [$event, $guest]))
        ->assertForbidden();

    expect($guest->fresh()->checked_in_at)->toBeNull();
});

it('accepte un scan après la date limite de réponse', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create([
        'user_id' => $owner->id,
        'rsvp_deadline' => now()->subWeek()->toDateString(),
    ]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $guest->forceFill([
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
    ])->save();

    $this->actingAs($owner)
        ->postJson(route('check-in.record', $event), ['code' => $guest->entry_token])
        ->assertOk()
        ->assertJsonPath('status', 'checked_in');

    expect($guest->fresh()->checked_in_at)->not->toBeNull();
});

it('affiche les compteurs de pointage à l’organisateur', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $checkedGuest = Guest::factory()->create([
        'event_id' => $event->id,
        'allowed_seats' => 4,
    ]);
    $checkedGuest->forceFill([
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 3,
        'checked_in_at' => now(),
        'checked_in_by' => $owner->id,
    ])->save();
    Guest::factory()->create(['event_id' => $event->id, 'allowed_seats' => 2]);

    $this->actingAs($owner)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Foyers pointés')
        ->assertSee('Couverts pointés')
        ->assertSee('1 / 2')
        ->assertSee('3 / 6')
        ->assertSee("Télécharger le QR d'entrée", false);
});

it('retourne 404 pour un jeton direct inconnu', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->get('/check-in/jeton-inconnu')
        ->assertNotFound();
});
