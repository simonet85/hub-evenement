<?php

use App\Models\Event;
use App\Models\EventRole;
use App\Models\User;

it('ajoute un co-organisateur par son e-mail', function () {
    $owner = User::factory()->create();
    $friend = User::factory()->create(['email' => 'ami@example.com']);
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('co-organizers.store', $event), ['co_email' => 'Ami@Example.com'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('event_roles', [
        'event_id' => $event->id,
        'user_id' => $friend->id,
        'role' => EventRole::ROLE_CO_ORGANIZER,
    ]);
});

it('refuse une adresse sans compte', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('co-organizers.store', $event), ['co_email' => 'inconnu@example.com'])
        ->assertSessionHasErrors('co_email');

    $this->assertDatabaseCount('event_roles', 0);
});

it('refuse le propriétaire comme co-organisateur', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('co-organizers.store', $event), ['co_email' => $owner->email])
        ->assertSessionHasErrors('co_email');

    $this->assertDatabaseCount('event_roles', 0);
});

it('refuse un doublon de co-organisateur', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $friend = makeCoOrganizer($event);

    $this->actingAs($owner)
        ->post(route('co-organizers.store', $event), ['co_email' => $friend->email])
        ->assertSessionHasErrors('co_email');

    $this->assertDatabaseCount('event_roles', 1);
});

it('réserve la gestion des co-organisateurs au propriétaire', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $coOrganizer = makeCoOrganizer($event);
    $other = User::factory()->create();

    $this->actingAs($coOrganizer)
        ->post(route('co-organizers.store', $event), ['co_email' => $other->email])
        ->assertForbidden();

    $this->actingAs($coOrganizer)
        ->delete(route('co-organizers.destroy', [$event, $coOrganizer]))
        ->assertForbidden();

    $this->assertDatabaseCount('event_roles', 1);
});

it('retire un co-organisateur', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $friend = makeCoOrganizer($event);

    $this->actingAs($owner)
        ->delete(route('co-organizers.destroy', [$event, $friend]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseCount('event_roles', 0);
});
