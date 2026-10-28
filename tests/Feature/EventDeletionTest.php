<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

it('supprime un événement avec ses foyers et ses fichiers', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id, 'name' => 'Mariage Awa & Yao']);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $otherEvent = Event::factory()->create(['user_id' => $owner->id]);

    Storage::disk('local')->put('events/'.$event->id.'/photos/portrait.jpg', 'photo');

    Log::spy();

    $this->actingAs($owner)
        ->delete(route('events.destroy', $event), ['confirmation' => 'Mariage Awa & Yao'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Événement supprimé.');

    expect(Event::find($event->id))->toBeNull()
        ->and(Guest::find($guest->id))->toBeNull()
        ->and(Event::find($otherEvent->id))->not->toBeNull()
        ->and(Storage::disk('local')->exists('events/'.$event->id.'/photos/portrait.jpg'))->toBeFalse();

    Log::shouldHaveReceived('info')->once()->withArgs(
        fn (string $message, array $context) => $message === 'event.deleted'
            && $context['event_id'] === $event->id
            && $context['user_id'] === $owner->id,
    );
});

it('exige le nom exact de l’événement pour confirmer', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id, 'name' => 'Mariage Awa & Yao']);

    $this->actingAs($owner)
        ->delete(route('events.destroy', $event), ['confirmation' => 'Mariage Awa'])
        ->assertSessionHasErrors('confirmation');

    $this->actingAs($owner)
        ->delete(route('events.destroy', $event), [])
        ->assertSessionHasErrors('confirmation');

    expect(Event::find($event->id))->not->toBeNull();

    $this->actingAs($owner)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Supprimer cet événement')
        ->assertSee('name="confirmation"', false);
});

it('interdit la suppression à un co-organisateur', function () {
    $event = Event::factory()->create();
    $coOrganizer = makeCoOrganizer($event);

    $this->actingAs($coOrganizer)
        ->delete(route('events.destroy', $event), ['confirmation' => $event->name])
        ->assertForbidden();

    expect(Event::find($event->id))->not->toBeNull();
});

it('interdit la suppression à un organisateur étranger', function () {
    $event = Event::factory()->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->delete(route('events.destroy', $event), ['confirmation' => $event->name])
        ->assertForbidden();

    expect(Event::find($event->id))->not->toBeNull();
});

it('renvoie un visiteur non connecté vers la connexion', function () {
    $event = Event::factory()->create();

    $this->delete(route('events.destroy', $event), ['confirmation' => $event->name])
        ->assertRedirect(route('login'));

    expect(Event::find($event->id))->not->toBeNull();
});
