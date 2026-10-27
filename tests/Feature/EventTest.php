<?php

use App\Models\Event;
use App\Models\EventRole;
use App\Models\User;

it("affiche uniquement l'événement de l'organisateur et ceux partagés", function () {
    $user = User::factory()->create();
    $mine = Event::factory()->create(['user_id' => $user->id, 'name' => 'Mariage Awa']);
    $foreign = Event::factory()->create(['name' => 'Baptême Inconnu']);
    $shared = Event::factory()->create(['name' => 'Anniversaire Partagé']);

    $shared->roles()->create([
        'user_id' => $user->id,
        'role' => EventRole::ROLE_CO_ORGANIZER,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Mariage Awa')
        ->assertSee('Anniversaire Partagé')
        ->assertDontSee('Baptême Inconnu');

    expect($mine->user_id)->toBe($user->id)
        ->and($foreign->user_id)->not->toBe($user->id);
});

it("affiche l'état vide quand l'organisateur n'a aucun événement", function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Aucun événement pour')
        ->assertSee('Créer mon premier événement');
});

it('crée un événement et ouvre sa page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/events', eventData());

    $event = Event::firstOrFail();

    $response->assertRedirect(route('events.show', $event));

    expect($event->user_id)->toBe($user->id)
        ->and($event->name)->toBe('Mariage Awa & Yao')
        ->and($event->type->value)->toBe('mariage');

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Mariage Awa')
        ->assertSee('Espace Latrille');
});

it('refuse un événement incomplet', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/events', eventData([
            'name' => '',
            'type' => 'inconnu',
            'starts_at' => '',
            'venue_name' => '',
        ]))
        ->assertSessionHasErrors(['name', 'type', 'starts_at', 'venue_name']);

    expect(Event::count())->toBe(0);
});

it("refuse une date limite de réponse après l'événement", function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/events', eventData(['rsvp_deadline' => '2027-01-10']))
        ->assertSessionHasErrors('rsvp_deadline');

    expect(Event::count())->toBe(0);
});

it('met à jour un événement pour son propriétaire', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create([
        'user_id' => $owner->id,
        'name' => 'Ancien nom',
    ]);

    $this->actingAs($owner)
        ->put(route('events.update', $event), eventData(['name' => 'Nouveau nom']))
        ->assertRedirect(route('events.show', $event));

    expect($event->fresh()->name)->toBe('Nouveau nom');
});

it("réserve la modification de l'événement au propriétaire", function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $coOrganizer = makeCoOrganizer($event);

    $this->actingAs($coOrganizer)
        ->get(route('events.edit', $event))
        ->assertForbidden();

    $this->actingAs($coOrganizer)
        ->put(route('events.update', $event), eventData(['name' => 'Détournement']))
        ->assertForbidden();

    expect($event->fresh()->name)->not->toBe('Détournement');
});

it("renvoie 403 à un tiers sur un événement auquel il n'appartient pas", function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('events.show', $event))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->get(route('events.edit', $event))
        ->assertForbidden();
});

it('permet à un co-organisateur de consulter l’événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $coOrganizer = makeCoOrganizer($event);

    $this->actingAs($coOrganizer)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Co-organisateur');
});
