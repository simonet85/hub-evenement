<?php

use App\Enums\RsvpStatus;
use App\Mail\RsvpConfirmation;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('génère un jeton unique à la création d’un foyer', function () {
    $first = Guest::factory()->create();
    $second = Guest::factory()->create();

    expect($first->invitation_token)->toHaveLength(48)
        ->and($second->invitation_token)->toHaveLength(48)
        ->and($first->invitation_token)->not->toBe($second->invitation_token);
});

it('affiche l’invitation publique sans authentification', function () {
    $guest = Guest::factory()->create(['name' => 'Famille Koné']);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertSee('Invitation pour')
        ->assertSee('Famille Koné')
        ->assertSee($guest->event->name)
        ->assertSee($guest->event->venue_name)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('<meta name="referrer" content="no-referrer">', false);
});

it('renvoie 404 pour un jeton inconnu', function () {
    $this->get('/i/jeton-inconnu')->assertNotFound();
});

it('replie la modification de réponse une fois la réponse enregistrée', function () {
    $guest = Guest::factory()->create([
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
        'rsvp_responded_at' => now(),
    ]);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertSee('<details', false)
        ->assertSee('Modifier votre réponse')
        ->assertSee('Enregistrer ma réponse')
        ->assertDontSee('Répondre à l’invitation');
});

it('régénère le lien et révoque l’ancien', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $oldToken = $guest->invitation_token;

    $this->actingAs($owner)
        ->post(route('guests.invitation.regenerate', [$event, $guest]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $guest->refresh();

    expect($guest->invitation_token)->not->toBe($oldToken);

    $this->get(route('invitations.show', $oldToken))->assertNotFound();
    $this->get(route('invitations.show', $guest->invitation_token))->assertOk();
});

it('permet à un co-organisateur de regénérer un lien', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $coOrganizer = makeCoOrganizer($event);
    $oldToken = $guest->invitation_token;

    $this->actingAs($coOrganizer)
        ->post(route('guests.invitation.regenerate', [$event, $guest]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($guest->fresh()->invitation_token)->not->toBe($oldToken);
});

it('refuse la régénération à un tiers et pour un foyer d’un autre événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $otherEvent = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('guests.invitation.regenerate', [$event, $guest]))
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('guests.invitation.regenerate', [$otherEvent, $guest]))
        ->assertNotFound();
});

it('enregistre une présence et met le reçu en file', function () {
    Mail::fake();

    $guest = Guest::factory()->create([
        'email' => 'foyer@example.com',
        'allowed_seats' => 4,
    ]);
    $guest->event->update(['rsvp_deadline' => now()->addWeek()->toDateString()]);

    $this->post(route('invitations.rsvp', $guest->invitation_token), [
        'rsvp_status' => 'attending',
        'rsvp_seats' => 3,
        'attendee_names' => "Awa\nYao\n",
        'dietary_notes' => 'Sans porc',
        'rsvp_message' => 'Avec joie !',
    ])
        ->assertRedirect(route('invitations.show', $guest->invitation_token))
        ->assertSessionHasNoErrors();

    $guest->refresh();

    expect($guest->rsvp_status)->toBe(RsvpStatus::Attending)
        ->and($guest->rsvp_seats)->toBe(3)
        ->and($guest->attendee_names)->toBe("Awa\nYao")
        ->and($guest->dietary_notes)->toBe('Sans porc')
        ->and($guest->rsvp_message)->toBe('Avec joie !')
        ->and($guest->rsvp_responded_at)->not->toBeNull()
        ->and($guest->rsvp_is_late)->toBeFalse();

    Mail::assertQueued(RsvpConfirmation::class, fn (RsvpConfirmation $mail) => $mail->guest->is($guest));
});

it('enregistre une absence sans places ni prénoms', function () {
    Mail::fake();

    $guest = Guest::factory()->create(['email' => null, 'allowed_seats' => 4]);

    $this->post(route('invitations.rsvp', $guest->invitation_token), [
        'rsvp_status' => 'declined',
        'rsvp_seats' => 2,
        'attendee_names' => 'Awa',
        'dietary_notes' => 'Végétarien',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $guest->refresh();

    expect($guest->rsvp_status)->toBe(RsvpStatus::Declined)
        ->and($guest->rsvp_seats)->toBeNull()
        ->and($guest->attendee_names)->toBeNull()
        ->and($guest->dietary_notes)->toBeNull();

    Mail::assertNothingQueued();
});

it('refuse plus de places que le foyer n’en possède', function () {
    $guest = Guest::factory()->create(['allowed_seats' => 2]);

    $this->post(route('invitations.rsvp', $guest->invitation_token), [
        'rsvp_status' => 'attending',
        'rsvp_seats' => 3,
    ])->assertSessionHasErrors('rsvp_seats');

    expect($guest->fresh()->rsvp_responded_at)->toBeNull();
});

it('refuse plus de prénoms que de places demandées', function () {
    $guest = Guest::factory()->create(['allowed_seats' => 4]);

    $this->post(route('invitations.rsvp', $guest->invitation_token), [
        'rsvp_status' => 'attending',
        'rsvp_seats' => 2,
        'attendee_names' => "Awa\nYao\nKoffi",
    ])->assertSessionHasErrors('attendee_names');

    expect($guest->fresh()->rsvp_responded_at)->toBeNull();
});

it('modifie une réponse avant la date limite', function () {
    $guest = Guest::factory()->create(['email' => null, 'allowed_seats' => 4]);
    $guest->event->update(['rsvp_deadline' => now()->addDays(5)->toDateString()]);

    $this->post(route('invitations.rsvp', $guest->invitation_token), [
        'rsvp_status' => 'attending',
        'rsvp_seats' => 2,
    ])->assertRedirect();

    $this->post(route('invitations.rsvp', $guest->invitation_token), [
        'rsvp_status' => 'attending',
        'rsvp_seats' => 4,
        'attendee_names' => "Awa\nYao\nKoffi\nAya",
    ])->assertRedirect()->assertSessionHasNoErrors();

    $guest->refresh();

    expect($guest->rsvp_seats)->toBe(4)
        ->and($guest->attendee_names)->toBe("Awa\nYao\nKoffi\nAya");
});

it('accepte une première réponse après la date limite et la signale', function () {
    $guest = Guest::factory()->create(['email' => null, 'allowed_seats' => 2]);
    $guest->event->update(['rsvp_deadline' => now()->subDay()->toDateString()]);

    $this->post(route('invitations.rsvp', $guest->invitation_token), [
        'rsvp_status' => 'attending',
        'rsvp_seats' => 2,
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', fn (string $status) => str_contains($status, 'après la date limite'));

    expect($guest->fresh()->rsvp_is_late)->toBeTrue();
});

it('verrouille une réponse après la date limite', function () {
    $guest = Guest::factory()->create(['email' => null, 'allowed_seats' => 2]);
    $guest->event->update(['rsvp_deadline' => now()->subDay()->toDateString()]);
    $guest->forceFill([
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 1,
        'rsvp_responded_at' => now()->subDays(3),
    ])->save();

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertDontSee('Envoyer ma réponse');

    $this->post(route('invitations.rsvp', $guest->invitation_token), [
        'rsvp_status' => 'attending',
        'rsvp_seats' => 2,
    ])->assertForbidden();

    expect($guest->fresh()->rsvp_seats)->toBe(1);
});

it('reste modifiable sans date limite', function () {
    $guest = Guest::factory()->create(['email' => null, 'allowed_seats' => 3]);
    $guest->event->update(['rsvp_deadline' => null]);

    $this->post(route('invitations.rsvp', $guest->invitation_token), [
        'rsvp_status' => 'attending',
        'rsvp_seats' => 1,
    ])->assertRedirect();

    $this->post(route('invitations.rsvp', $guest->invitation_token), [
        'rsvp_status' => 'declined',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($guest->fresh()->rsvp_status)->toBe(RsvpStatus::Declined);
});

it('affiche les réponses et les couverts à l’organisateur', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = Guest::factory()->create([
        'event_id' => $event->id,
        'name' => 'Famille Awa',
        'allowed_seats' => 4,
    ]);
    $guest->forceFill([
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 3,
        'rsvp_responded_at' => now(),
    ])->save();

    $this->actingAs($owner)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Couverts')
        ->assertSee('Famille Awa')
        ->assertSee('Présent')
        ->assertSee('3 pers.');
});
