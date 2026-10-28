<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\Photo;

function accessibilityGuest(array $overrides = []): Guest
{
    return Guest::factory()->create($overrides);
}

it('expose les repères d’accessibilité dans les deux mises en page', function () {
    $guest = accessibilityGuest();

    $guestPage = $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertSee('<html lang="fr">', false)
        ->assertSee('href="#contenu"', false)
        ->assertSee('id="contenu"', false)
        ->assertSee('<meta name="theme-color" content="#fbf6ee">', false)
        ->getContent();

    $appPage = $this->get(route('login'))
        ->assertOk()
        ->assertSee('<html lang="fr">', false)
        ->assertSee('href="#contenu"', false)
        ->assertSee('id="contenu"', false)
        ->assertSee('<meta name="theme-color" content="#fbf6ee">', false)
        ->getContent();

    expect($guestPage)->toContain('Aller au contenu')
        ->and($appPage)->toContain('Aller au contenu');
});

it('marque les sections de l’invitation pour une révélation progressive', function () {
    $guest = accessibilityGuest();

    $html = $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, 'data-reveal'))->toBeGreaterThanOrEqual(4)
        ->and($html)->not->toContain('data-reveal-shown');
});

it('marque le programme et décrit la carte sans piéger le lecteur d’écran', function () {
    $event = Event::factory()->create([
        'dress_code' => 'Tenue de soirée',
        'latitude' => 5.359,
        'longitude' => -3.986,
    ]);
    $guest = accessibilityGuest(['event_id' => $event->id]);

    $html = $this->get(route('invitations.programme', $guest->invitation_token))
        ->assertOk()
        ->assertSee('role="region"', false)
        ->assertSee('aria-describedby="carte-aide"', false)
        ->assertSee('id="carte-aide"', false)
        ->assertDontSee('role="application"', false)
        ->assertSee('Itinéraire Google Maps')
        ->getContent();

    expect(substr_count($html, 'data-reveal'))->toBeGreaterThanOrEqual(3);
});

it('relie l’erreur de places au champ du RSVP', function () {
    $guest = accessibilityGuest(['allowed_seats' => 2]);

    $this->from(route('invitations.show', $guest->invitation_token))
        ->post(route('invitations.rsvp', $guest->invitation_token), [
            'rsvp_status' => 'attending',
            'rsvp_seats' => 9,
        ])
        ->assertRedirect(route('invitations.show', $guest->invitation_token))
        ->assertSessionHasErrors('rsvp_seats');

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertSee('aria-describedby="rsvp_seats-help rsvp_seats-error"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('id="rsvp_seats-error"', false)
        ->assertSee('role="alert"', false);
});

it('relie les erreurs du livre d’or à leurs champs', function () {
    $guest = accessibilityGuest();

    $this->from(route('invitations.show', $guest->invitation_token))
        ->post(route('guestbook.store', $guest->invitation_token), [
            'author_name' => '',
            'message' => '',
        ])
        ->assertSessionHasErrors(['author_name', 'message']);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertSee('id="author_name-error"', false)
        ->assertSee('id="guestbook_message-error"', false)
        ->assertSee('aria-describedby="guestbook_message-help guestbook_message-error"', false)
        ->assertSee('autocomplete="name"', false);
});

it('annonce la nouvelle fenêtre et charge les photos de la galerie en différé', function () {
    $guest = accessibilityGuest();
    Photo::factory()->approved()->create([
        'event_id' => $guest->event_id,
        'caption' => 'Le gâteau',
    ]);

    $this->get(route('invitations.gallery', $guest->invitation_token))
        ->assertOk()
        ->assertSee('loading="lazy"', false)
        ->assertSee('decoding="async"', false)
        ->assertSee('(nouvelle fenêtre)')
        ->assertSee('alt="Le gâteau"', false);
});

it('prépare la saisie mobile des téléphones du registre', function () {
    $guest = accessibilityGuest(['phone' => '+225 07 00 00 00 00']);
    $guest->event->forceFill([
        'cagnotte_enabled' => true,
        'cagnotte_goal' => 500000,
    ])->save();

    $this->get(route('invitations.registry', $guest->invitation_token))
        ->assertOk()
        ->assertSee('autocomplete="tel"', false)
        ->assertSee('inputmode="tel"', false)
        ->assertSee('inputmode="numeric"', false);
});

it('conserve un contenu visible sans JavaScript et respecte le mouvement réduit', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    $reveal = file_get_contents(resource_path('js/reveal.js'));

    expect($css)->toContain('html.reveal-enabled [data-reveal]:not([data-reveal-shown])')
        ->toContain('@media (prefers-reduced-motion: reduce)')
        ->toContain('@media print')

        ->and($reveal)->toContain('prefers-reduced-motion: reduce')
        ->toContain('IntersectionObserver')
        ->toContain('focusin')
        ->toContain('reveal-enabled');
});
