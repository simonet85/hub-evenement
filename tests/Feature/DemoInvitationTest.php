<?php

use App\Enums\RsvpStatus;
use App\Models\Guest;
use App\Models\User;

it('renvoie 404 quand aucune démonstration n’est configurée', function () {
    config(['hub.demo_invitation_token' => null]);

    $this->get(route('demo.invitation'))->assertNotFound();
});

it('renvoie 404 quand le jeton ne pointe sur aucun foyer', function () {
    config(['hub.demo_invitation_token' => str_repeat('b', 48)]);

    $this->get(route('demo.invitation'))->assertNotFound();
});

it('réinitialise le foyer de démonstration et redirige vers son invitation', function () {
    $token = str_repeat('DeMo', 12);
    config(['hub.demo_invitation_token' => $token]);

    $guest = Guest::factory()->create([
        'invitation_token' => $token,
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 2,
        'attendee_names' => 'Awa, Koffi',
        'dietary_notes' => 'Sans porc',
        'rsvp_message' => 'Avec plaisir',
        'rsvp_responded_at' => now(),
        'rsvp_is_late' => true,
        'checked_in_at' => now(),
        'checked_in_by' => User::factory()->create()->id,
    ]);

    $this->get(route('demo.invitation'))
        ->assertRedirect(route('invitations.show', $token));

    $guest->refresh();

    expect($guest->rsvp_status)->toBe(RsvpStatus::Pending)
        ->and($guest->rsvp_seats)->toBeNull()
        ->and($guest->attendee_names)->toBeNull()
        ->and($guest->dietary_notes)->toBeNull()
        ->and($guest->rsvp_message)->toBeNull()
        ->and($guest->rsvp_responded_at)->toBeNull()
        ->and($guest->rsvp_is_late)->toBeFalse()
        ->and($guest->checked_in_at)->toBeNull()
        ->and($guest->checked_in_by)->toBeNull();
});

it('marque l’invitation de démonstration sans toucher aux invitations réelles', function () {
    $token = str_repeat('DeMo', 12);
    config(['hub.demo_invitation_token' => $token]);

    $demo = Guest::factory()->create(['invitation_token' => $token]);
    $real = Guest::factory()->create();

    $this->get(route('invitations.show', $demo->invitation_token))
        ->assertOk()
        ->assertSee('Invitation de démonstration')
        ->assertSee('Cette invitation est une démonstration publique')
        ->assertSee('<span class="eyebrow">Démonstration</span>', false);

    $this->get(route('invitations.show', $real->invitation_token))
        ->assertOk()
        ->assertDontSee('Invitation de démonstration')
        ->assertSee('Ce lien est personnel et confidentiel')
        ->assertSee('<span class="eyebrow">Invitation privée</span>', false);
});
