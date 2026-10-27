<?php

use App\Enums\GuestbookStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestbookEntry;
use App\Models\User;

function guestbookGuest(Event $event, array $overrides = []): Guest
{
    return Guest::factory()->create(array_merge(['event_id' => $event->id], $overrides));
}

function guestbookEntry(Guest $guest, array $overrides = []): GuestbookEntry
{
    return GuestbookEntry::factory()->create(array_merge([
        'event_id' => $guest->event_id,
        'guest_id' => $guest->id,
        'status' => GuestbookStatus::Pending,
    ], $overrides));
}

it('enregistre un message en attente depuis l’invitation', function () {
    $event = Event::factory()->create();
    $guest = guestbookGuest($event, ['name' => 'Famille Koné']);

    $this->post(route('guestbook.store', $guest->invitation_token), [
        'author_name' => '  Awa Koné  ',
        'message' => "  Félicitations !\nQue du bonheur.  ",
    ])
        ->assertRedirect(route('invitations.show', $guest->invitation_token).'#livre-dor')
        ->assertSessionHas('status', 'Merci ! Votre message sera publié après validation par les organisateurs.');

    $this->assertDatabaseHas('guestbook_entries', [
        'event_id' => $event->id,
        'guest_id' => $guest->id,
        'author_name' => 'Awa Koné',
        'message' => "Félicitations !\nQue du bonheur.",
        'status' => 'pending',
        'moderated_by' => null,
        'moderated_at' => null,
    ]);
});

it('refuse un nom vide ou un message trop long', function () {
    $guest = guestbookGuest(Event::factory()->create());

    $this->post(route('guestbook.store', $guest->invitation_token), [
        'author_name' => '',
        'message' => '',
    ])->assertSessionHasErrors(['author_name', 'message']);

    $this->post(route('guestbook.store', $guest->invitation_token), [
        'author_name' => str_repeat('a', 81),
        'message' => str_repeat('b', 1001),
    ])->assertSessionHasErrors(['author_name', 'message']);

    $this->assertDatabaseCount('guestbook_entries', 0);
});

it('ignore silencieusement un dépôt piégé par le honeypot', function () {
    $guest = guestbookGuest(Event::factory()->create());

    $this->post(route('guestbook.store', $guest->invitation_token), [
        'author_name' => 'Robot',
        'message' => 'Achetez maintenant',
        'website_url' => 'https://spam.example',
    ])
        ->assertRedirect(route('invitations.show', $guest->invitation_token).'#livre-dor')
        ->assertSessionHas('status');

    $this->assertDatabaseCount('guestbook_entries', 0);
});

it('limite les dépôts à cinq par minute', function () {
    $guest = guestbookGuest(Event::factory()->create());

    for ($index = 0; $index < 5; $index++) {
        $this->post(route('guestbook.store', $guest->invitation_token), [
            'author_name' => 'Awa',
            'message' => 'Message '.$index,
        ])->assertRedirect();
    }

    $this->post(route('guestbook.store', $guest->invitation_token), [
        'author_name' => 'Awa',
        'message' => 'Trop de messages',
    ])->assertStatus(429);

    $this->assertDatabaseCount('guestbook_entries', 5);
});

it('affiche les messages publiés et cache les autres états', function () {
    $event = Event::factory()->create();
    $guest = guestbookGuest($event, ['name' => 'Famille Koné']);

    guestbookEntry($guest, [
        'status' => GuestbookStatus::Approved,
        'author_name' => 'Awa',
        'message' => 'Premier message publié',
        'created_at' => now()->subHour(),
    ]);
    guestbookEntry($guest, [
        'status' => GuestbookStatus::Approved,
        'author_name' => 'Yao',
        'message' => 'Second message publié',
        'created_at' => now(),
    ]);
    guestbookEntry($guest, ['message' => 'Message en attente']);
    guestbookEntry($guest, ['status' => GuestbookStatus::Hidden, 'message' => 'Message masqué']);
    guestbookEntry($guest, ['status' => GuestbookStatus::Rejected, 'message' => 'Message rejeté']);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertSee('Signer le livre d\'or', false)
        ->assertSeeInOrder(['Second message publié', 'Premier message publié'])
        ->assertDontSee('Message en attente')
        ->assertDontSee('Message masqué')
        ->assertDontSee('Message rejeté')
        ->assertSee('value="Famille Koné"', false);
});

it('affiche un état vide du livre d’or', function () {
    $guest = guestbookGuest(Event::factory()->create());

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertSee('Aucun message publié pour l\'instant.', false)
        ->assertSee('Signer le livre d\'or', false);
});

it('publie, masque, rejette puis republie un message', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = guestbookGuest($event);
    $entry = guestbookEntry($guest, ['message' => 'Tous nos vœux de bonheur.']);

    $this->actingAs($owner)
        ->post(route('guestbook.approve', [$event, $entry]))
        ->assertRedirect(route('guestbook.index', $event))
        ->assertSessionHas('status', 'Message publié.');

    $entry->refresh();
    expect($entry->status)->toBe(GuestbookStatus::Approved)
        ->and($entry->moderated_by)->toBe($owner->id)
        ->and($entry->moderated_at)->not->toBeNull();

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertSee('Tous nos vœux de bonheur.');

    $this->actingAs($owner)
        ->post(route('guestbook.hide', [$event, $entry]))
        ->assertSessionHas('status', 'Message masqué.');

    expect($entry->fresh()->status)->toBe(GuestbookStatus::Hidden);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertDontSee('Tous nos vœux de bonheur.');

    $this->actingAs($owner)
        ->post(route('guestbook.reject', [$event, $entry]))
        ->assertSessionHas('status', 'Message rejeté.');

    expect($entry->fresh()->status)->toBe(GuestbookStatus::Rejected);

    $this->actingAs($owner)->post(route('guestbook.approve', [$event, $entry]));

    expect($entry->fresh()->status)->toBe(GuestbookStatus::Approved);
});

it('renvoie 404 pour un message d’un autre événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $entry = guestbookEntry(guestbookGuest(Event::factory()->create(['user_id' => $owner->id])));

    $this->actingAs($owner)->post(route('guestbook.approve', [$event, $entry]))->assertNotFound();
    $this->actingAs($owner)->post(route('guestbook.hide', [$event, $entry]))->assertNotFound();
    $this->actingAs($owner)->post(route('guestbook.reject', [$event, $entry]))->assertNotFound();

    expect($entry->fresh()->status)->toBe(GuestbookStatus::Pending);
});

it('réserve la modération aux comptes de l’événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $entry = guestbookEntry(guestbookGuest($event));
    $stranger = User::factory()->create();

    $this->get(route('guestbook.index', $event))->assertRedirect(route('login'));

    $this->actingAs($stranger)->get(route('guestbook.index', $event))->assertForbidden();
    $this->actingAs($stranger)
        ->post(route('guestbook.approve', [$event, $entry]))
        ->assertForbidden();

    $coOrganizer = makeCoOrganizer($event);
    $this->actingAs($coOrganizer)->get(route('guestbook.index', $event))->assertOk();
    $this->actingAs($coOrganizer)
        ->post(route('guestbook.approve', [$event, $entry]))
        ->assertRedirect();

    expect($entry->fresh()->status)->toBe(GuestbookStatus::Approved);
});

it('affiche la file de modération', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = guestbookGuest($event, ['name' => 'Famille Bamba']);
    guestbookEntry($guest, ['author_name' => 'Awa Bamba', 'message' => 'Tous nos vœux de bonheur.']);

    $this->actingAs($owner)
        ->get(route('guestbook.index', $event))
        ->assertOk()
        ->assertSee('En attente de validation')
        ->assertSee('Awa Bamba')
        ->assertSee('Famille Bamba')
        ->assertSee('Tous nos vœux de bonheur.')
        ->assertSee('Publier')
        ->assertSee('Rejeter')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('affiche un état vide de modération', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('guestbook.index', $event))
        ->assertOk()
        ->assertSee('Aucun message pour l\'instant', false)
        ->assertDontSee('En attente de validation');
});

it('échappe le nom et le message des invités', function () {
    $guest = guestbookGuest(Event::factory()->create());
    guestbookEntry($guest, [
        'status' => GuestbookStatus::Approved,
        'author_name' => '<script>alert(1)</script>',
        'message' => '<script>alert("xss")</script>',
    ]);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertDontSee('<script>alert', false)
        ->assertSee('&lt;script&gt;alert', false);
});

it('affiche les messages en attente sur l’événement et le tableau de bord', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = guestbookGuest($event);
    guestbookEntry($guest);
    guestbookEntry($guest);
    guestbookEntry($guest, ['status' => GuestbookStatus::Approved]);

    $this->actingAs($owner)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Livre d\'or (2)', false);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('2 messages à modérer');
});
