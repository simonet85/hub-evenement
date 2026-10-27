<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\ProgramStep;
use App\Models\User;

function programStepData(array $overrides = []): array
{
    return array_merge([
        'start_time' => '14:00',
        'end_time' => '15:30',
        'title' => 'Cérémonie',
        'location' => 'Espace Latrille',
        'description' => 'Bénédiction et échanges.',
    ], $overrides);
}

it('ajoute une étape au programme', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('program-steps.store', $event), programStepData())
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHas('status');

    $step = $event->programSteps()->firstOrFail();

    expect($step->title)->toBe('Cérémonie')
        ->and($step->start_time)->toBe('14:00')
        ->and($step->position)->toBe(1);
});

it('ajoute les étapes les unes à la suite des autres', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('program-steps.store', $event), programStepData(['start_time' => '10:00', 'title' => 'Accueil']));

    $this->actingAs($owner)
        ->post(route('program-steps.store', $event), programStepData(['start_time' => '12:00', 'title' => 'Déjeuner']));

    expect($event->programSteps()->pluck('title')->all())
        ->toBe(['Accueil', 'Déjeuner'])
        ->and($event->programSteps()->pluck('position')->all())
        ->toBe([1, 2]);
});

it('modifie une étape', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $step = $event->programSteps()->create(programStepData() + ['position' => 1]);

    $this->actingAs($owner)
        ->put(route('program-steps.update', [$event, $step]), programStepData([
            'title' => 'Cérémonie civile',
            'start_time' => '15:00',
        ]))
        ->assertRedirect(route('events.show', $event));

    expect($step->fresh()->title)->toBe('Cérémonie civile')
        ->and($step->fresh()->start_time)->toBe('15:00');
});

it('réordonne les étapes', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $first = $event->programSteps()->create(programStepData(['title' => 'Accueil']) + ['position' => 1]);
    $second = $event->programSteps()->create(programStepData(['title' => 'Dîner']) + ['position' => 2]);
    $third = $event->programSteps()->create(programStepData(['title' => 'Bal']) + ['position' => 3]);

    $this->actingAs($owner)
        ->post(route('program-steps.move', [$event, $third]), ['direction' => 'up'])
        ->assertRedirect(route('events.show', $event));

    expect($event->programSteps()->pluck('id')->all())
        ->toBe([$first->id, $third->id, $second->id])
        ->and($event->programSteps()->pluck('position')->all())
        ->toBe([0, 1, 2]);

    $this->actingAs($owner)
        ->post(route('program-steps.move', [$event, $first]), ['direction' => 'up']);

    expect($event->programSteps()->pluck('id')->all())->toBe([$first->id, $third->id, $second->id]);
});

it('supprime une étape', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $step = $event->programSteps()->create(programStepData() + ['position' => 1]);

    $this->actingAs($owner)
        ->delete(route('program-steps.destroy', [$event, $step]))
        ->assertRedirect(route('events.show', $event));

    expect(ProgramStep::count())->toBe(0);
});

it('autorise un co-organisateur à gérer le programme', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $coOrganizer = makeCoOrganizer($event);

    $this->actingAs($coOrganizer)
        ->post(route('program-steps.store', $event), programStepData())
        ->assertRedirect(route('events.show', $event));

    expect($event->programSteps()->count())->toBe(1);
});

it('refuse le programme à un tiers', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $step = $event->programSteps()->create(programStepData() + ['position' => 1]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('program-steps.store', $event), programStepData())
        ->assertForbidden();

    $this->actingAs($stranger)
        ->put(route('program-steps.update', [$event, $step]), programStepData(['title' => 'Détournement']))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->delete(route('program-steps.destroy', [$event, $step]))
        ->assertForbidden();

    expect($step->fresh()->title)->toBe('Cérémonie');
});

it('renvoie 404 pour une étape d’un autre événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $otherEvent = Event::factory()->create(['user_id' => $owner->id]);
    $foreignStep = $otherEvent->programSteps()->create(programStepData() + ['position' => 1]);

    $this->actingAs($owner)
        ->put(route('program-steps.update', [$event, $foreignStep]), programStepData(['title' => 'Détournement']))
        ->assertNotFound();

    $this->actingAs($owner)
        ->delete(route('program-steps.destroy', [$event, $foreignStep]))
        ->assertNotFound();

    $this->actingAs($owner)
        ->post(route('program-steps.move', [$event, $foreignStep]), ['direction' => 'up'])
        ->assertNotFound();

    expect($foreignStep->fresh()->title)->toBe('Cérémonie');
});

it('valide les champs d’une étape', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('program-steps.store', $event), [
            'start_time' => '25:00',
            'end_time' => '09:00',
            'title' => '',
        ])
        ->assertSessionHasErrors(['start_time', 'end_time', 'title']);

    expect(ProgramStep::count())->toBe(0);
});

it('affiche la page programme avec la frise, le code vestimentaire et la carte', function () {
    $event = Event::factory()->create([
        'dress_code' => 'Tenue de cérémonie, couleurs claires',
        'latitude' => 5.3599,
        'longitude' => -4.0083,
    ]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);

    $event->programSteps()->create(programStepData(['start_time' => '10:00', 'title' => 'Cérémonie']) + ['position' => 1]);
    $event->programSteps()->create(programStepData(['start_time' => '19:00', 'title' => 'Réception']) + ['position' => 2]);

    $this->get(route('invitations.programme', $guest->invitation_token))
        ->assertOk()
        ->assertSee('Programme')
        ->assertSeeInOrder(['Cérémonie', 'Réception'])
        ->assertSee('Tenue de cérémonie, couleurs claires')
        ->assertSee('data-program-map', false)
        ->assertSee('data-lat="5.3599"', false)
        ->assertSee('data-lng="-4.0083"', false)
        ->assertSee('Itinéraire Google Maps')
        ->assertSee('destination=5.3599,-4.0083', false)
        ->assertSee('Ouvrir dans Waze')
        ->assertSee('ll=5.3599%2C-4.0083', false)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('<meta name="referrer" content="no-referrer">', false);
});

it('affiche l’adresse et Google Maps sans coordonnées', function () {
    $event = Event::factory()->create([
        'venue_name' => 'Espace Latrille',
        'venue_address' => 'Cocody, Abidjan',
        'latitude' => null,
        'longitude' => null,
    ]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);

    $this->get(route('invitations.programme', $guest->invitation_token))
        ->assertOk()
        ->assertSee('Espace Latrille')
        ->assertSee('Cocody, Abidjan')
        ->assertDontSee('data-program-map', false)
        ->assertSee('google.com/maps/search', false)
        ->assertDontSee('Ouvrir dans Waze');
});

it('renvoie 404 pour un jeton inconnu sur le programme', function () {
    $this->get('/i/jeton-inconnu/programme')->assertNotFound();
});

it('affiche le code vestimentaire et le lien programme dans l’invitation', function () {
    $event = Event::factory()->create(['dress_code' => 'Tenue de soirée']);
    $guest = Guest::factory()->create(['event_id' => $event->id]);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertSee('Code vestimentaire : Tenue de soirée')
        ->assertSee(route('invitations.programme', $guest->invitation_token), false);
});

it('enregistre le code vestimentaire et les coordonnées', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->put(route('events.update', $event), eventData([
            'dress_code' => 'Costume clair',
            'latitude' => '5.34567',
            'longitude' => '-4.01234',
        ]))
        ->assertRedirect(route('events.show', $event));

    $event->refresh();

    expect($event->dress_code)->toBe('Costume clair')
        ->and($event->latitude)->toBe(5.34567)
        ->and($event->longitude)->toBe(-4.01234);
});

it('refuse des coordonnées hors bornes ou incomplètes', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->put(route('events.update', $event), eventData([
            'latitude' => 100,
            'longitude' => -4.0083,
        ]))
        ->assertSessionHasErrors('latitude');

    $this->actingAs($owner)
        ->put(route('events.update', $event), eventData([
            'latitude' => 5.3599,
            'longitude' => null,
        ]))
        ->assertSessionHasErrors('longitude');

    expect($event->fresh()->hasCoordinates())->toBeFalse();
});

it('affiche l’aperçu du programme dans l’espace organisateur', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $event->programSteps()->create(programStepData() + ['position' => 1]);

    $this->actingAs($owner)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Programme')
        ->assertSee('Cérémonie')
        ->assertSee('Voir la page programme')
        ->assertSee(route('invitations.programme', $guest->invitation_token), false);
});
