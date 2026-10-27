<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;

it('ajoute un foyer à un événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('guests.store', $event), [
            'name' => 'Famille Koné',
            'email' => 'kone@example.com',
            'phone' => '+225 07 00 00 00',
            'allowed_seats' => 3,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('guests', [
        'event_id' => $event->id,
        'name' => 'Famille Koné',
        'allowed_seats' => 3,
    ]);
});

it('valide le nom et le nombre de places du foyer', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('guests.store', $event), [
            'name' => '',
            'email' => 'pas-un-email',
            'allowed_seats' => 0,
        ])
        ->assertSessionHasErrors(['name', 'email', 'allowed_seats']);

    $this->actingAs($owner)
        ->post(route('guests.store', $event), [
            'name' => 'Famille Yao',
            'allowed_seats' => 21,
        ])
        ->assertSessionHasErrors('allowed_seats');

    expect(Guest::count())->toBe(0);
});

it('supprime un foyer saisi par erreur', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);

    $this->actingAs($owner)
        ->delete(route('guests.destroy', [$event, $guest]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('guests', ['id' => $guest->id]);
});

it('permet à un co-organisateur de gérer les foyers', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $coOrganizer = makeCoOrganizer($event);

    $this->actingAs($coOrganizer)
        ->post(route('guests.store', $event), [
            'name' => 'Famille Bamba',
            'allowed_seats' => 2,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $guest = Guest::where('event_id', $event->id)->firstOrFail();

    $this->actingAs($coOrganizer)
        ->delete(route('guests.destroy', [$event, $guest]))
        ->assertRedirect();

    $this->assertDatabaseMissing('guests', ['id' => $guest->id]);
});

it('refuse la gestion des foyers à un tiers', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('guests.store', $event), ['name' => 'Intrus', 'allowed_seats' => 1])
        ->assertForbidden();

    $this->actingAs($stranger)
        ->delete(route('guests.destroy', [$event, $guest]))
        ->assertForbidden();

    expect($guest->fresh())->not->toBeNull();
});

it('renvoie 404 quand le foyer appartient à un autre événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $otherEvent = Event::factory()->create(['user_id' => $owner->id]);
    $foreignGuest = Guest::factory()->create(['event_id' => $otherEvent->id]);

    $this->actingAs($owner)
        ->delete(route('guests.destroy', [$event, $foreignGuest]))
        ->assertNotFound();

    expect($foreignGuest->fresh())->not->toBeNull();
});
