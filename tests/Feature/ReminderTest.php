<?php

use App\Jobs\SendReminderEmail;
use App\Mail\ReminderEmail;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

function pendingGuest(Event $event, array $overrides = []): Guest
{
    return Guest::factory()->create(array_merge(['event_id' => $event->id], $overrides));
}

it('met en file une relance pour les foyers sélectionnés', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = pendingGuest($event);

    $this->actingAs($owner)
        ->post(route('reminders.store', $event), ['guest_ids' => [$guest->id]])
        ->assertRedirect(route('reminders.index', $event))
        ->assertSessionHas('status', '1 relance mise en file.');

    $this->assertDatabaseHas('reminders', [
        'event_id' => $event->id,
        'guest_id' => $guest->id,
        'user_id' => $owner->id,
        'sent_at' => null,
    ]);

    Queue::assertPushed(SendReminderEmail::class, fn (SendReminderEmail $job) => $job->delay === null);
});

it('met chaque envoi d’un lot en file', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guests = collect([pendingGuest($event), pendingGuest($event), pendingGuest($event)]);

    $this->actingAs($owner)
        ->post(route('reminders.store', $event), ['guest_ids' => $guests->pluck('id')->all()])
        ->assertSessionHas('status', '3 relances mises en file.');

    Queue::assertPushed(SendReminderEmail::class, 3);
    $this->assertDatabaseCount('reminders', 3);
});

it('relance tous les foyers éligibles', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $first = pendingGuest($event);
    $second = pendingGuest($event);
    pendingGuest($event, ['rsvp_status' => 'declined', 'rsvp_responded_at' => now()]);

    $this->actingAs($owner)
        ->post(route('reminders.store', $event), ['all' => '1'])
        ->assertSessionHas('status', '2 relances mises en file. 1 foyer ignoré.');

    expect(Reminder::pluck('guest_id')->all())->toEqualCanonicalizing([$first->id, $second->id]);
});

it('envoie l’e-mail et marque la relance comme envoyée', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = pendingGuest($event);

    $this->actingAs($owner)
        ->post(route('reminders.store', $event), ['guest_ids' => [$guest->id]]);

    Mail::assertSent(ReminderEmail::class, function (ReminderEmail $mail) use ($guest) {
        return $mail->hasTo($guest->email)
            && str_contains($mail->render(), $guest->invitationUrl())
            && str_contains($mail->render(), 'Ne plus recevoir de relances')
            && str_contains($mail->render(), 'signature=');
    });

    expect(Reminder::first()->sent_at)->not->toBeNull();
});

it('ignore les foyers ayant déjà répondu', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = pendingGuest($event, ['rsvp_status' => 'attending', 'rsvp_seats' => 2, 'rsvp_responded_at' => now()]);

    $this->actingAs($owner)
        ->post(route('reminders.store', $event), ['guest_ids' => [$guest->id]])
        ->assertSessionHasErrors('guest_ids');

    $this->assertDatabaseCount('reminders', 0);
    Queue::assertNothingPushed();
});

it('ignore les foyers sans e-mail ou désinscrits', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $withoutEmail = pendingGuest($event, ['email' => null]);
    $optedOut = pendingGuest($event);
    $optedOut->forceFill(['reminders_opted_out_at' => now()])->save();

    $this->actingAs($owner)
        ->post(route('reminders.store', $event), ['guest_ids' => [$withoutEmail->id, $optedOut->id]])
        ->assertSessionHasErrors('guest_ids');

    $this->assertDatabaseCount('reminders', 0);
});

it('refuse une seconde relance moins de 24 heures après', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = pendingGuest($event);
    $guest->reminders()->create(['event_id' => $event->id, 'user_id' => $owner->id])
        ->forceFill(['created_at' => now()->subHours(2)])->save();

    $this->actingAs($owner)
        ->post(route('reminders.store', $event), ['guest_ids' => [$guest->id]])
        ->assertSessionHasErrors('guest_ids');

    Queue::assertNothingPushed();
});

it('autorise une seconde relance après 24 heures', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = pendingGuest($event);
    $guest->reminders()->create(['event_id' => $event->id, 'user_id' => $owner->id])
        ->forceFill(['created_at' => now()->subHours(25)])->save();

    $this->actingAs($owner)
        ->post(route('reminders.store', $event), ['guest_ids' => [$guest->id]])
        ->assertSessionHas('status');

    $this->assertDatabaseCount('reminders', 2);
});

it('plafonne à deux relances par foyer', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = pendingGuest($event);

    foreach ([48, 24] as $hours) {
        $guest->reminders()->create(['event_id' => $event->id, 'user_id' => $owner->id])
            ->forceFill(['created_at' => now()->subHours($hours)])->save();
    }

    $this->actingAs($owner)
        ->post(route('reminders.store', $event), ['guest_ids' => [$guest->id]])
        ->assertSessionHasErrors('guest_ids');

    $this->assertDatabaseCount('reminders', 2);
});

it('ignore un foyer d’un autre événement', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $foreign = pendingGuest(Event::factory()->create(['user_id' => $owner->id]));

    $this->actingAs($owner)
        ->post(route('reminders.store', $event), ['guest_ids' => [$foreign->id]])
        ->assertSessionHasErrors('guest_ids');

    $this->assertDatabaseCount('reminders', 0);
});

it('réserve les relances aux comptes de l’événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = pendingGuest($event);
    $stranger = User::factory()->create();

    $this->get(route('reminders.index', $event))->assertRedirect(route('login'));

    $this->actingAs($stranger)->get(route('reminders.index', $event))->assertForbidden();
    $this->actingAs($stranger)
        ->post(route('reminders.store', $event), ['guest_ids' => [$guest->id]])
        ->assertForbidden();

    $coOrganizer = makeCoOrganizer($event);
    $this->actingAs($coOrganizer)->get(route('reminders.index', $event))->assertOk();
});

it('affiche les foyers sans réponse et leurs relances', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = pendingGuest($event, ['name' => 'Famille Koné']);
    $guest->reminders()->create(['event_id' => $event->id, 'user_id' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('reminders.index', $event))
        ->assertOk()
        ->assertSee('Relancer les foyers')
        ->assertSee('Famille Koné')
        ->assertSee('1 / 2 relances utilisées')
        ->assertSee('Ouvrir WhatsApp pré-rempli')
        ->assertSee('Relancer tous les foyers éligibles')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('masque la prochaine relance d’un foyer désinscrit', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $guest = pendingGuest($event);
    $guest->reminders()->create(['event_id' => $event->id, 'user_id' => $owner->id]);
    $guest->forceFill(['reminders_opted_out_at' => now()])->save();

    $this->actingAs($owner)
        ->get(route('reminders.index', $event))
        ->assertOk()
        ->assertSee('Désinscrit')
        ->assertSee('Ce foyer a refusé les relances')
        ->assertDontSee('Prochaine relance possible');
});

it('affiche un état vide quand tous les foyers ont répondu', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    pendingGuest($event, ['rsvp_status' => 'declined', 'rsvp_responded_at' => now()]);

    $this->actingAs($owner)
        ->get(route('reminders.index', $event))
        ->assertOk()
        ->assertSee('Tous les foyers ont répondu')
        ->assertDontSee('Relancer tous les foyers éligibles');
});

it('désinscrit un foyer depuis un lien signé', function () {
    $guest = pendingGuest(Event::factory()->create());

    $unsigned = route('reminders.unsubscribe.show', $guest);
    $this->get($unsigned)->assertForbidden();

    $showUrl = URL::signedRoute('reminders.unsubscribe.show', ['guest' => $guest]);
    $this->get($showUrl)
        ->assertOk()
        ->assertSee($guest->name)
        ->assertSee('Ne plus recevoir de relances');

    $this->post(URL::signedRoute('reminders.unsubscribe', ['guest' => $guest]))
        ->assertRedirect();

    expect($guest->fresh()->hasOptedOutOfReminders())->toBeTrue();
    expect($guest->fresh()->invitationUrl())->not->toBeEmpty();
    $this->get(route('invitations.show', $guest->invitation_token))->assertOk();
});

it('reste idempotent sur une désinscription répétée', function () {
    $guest = pendingGuest(Event::factory()->create());
    $guest->forceFill(['reminders_opted_out_at' => now()->subDay()])->save();

    $this->post(URL::signedRoute('reminders.unsubscribe', ['guest' => $guest]))->assertRedirect();

    expect($guest->fresh()->reminders_opted_out_at->toDateString())->toBe(now()->subDay()->toDateString());
});

it('construit un lien WhatsApp normalisé et pré-rempli', function () {
    $event = Event::factory()->create(['name' => 'Mariage Awa']);
    $guest = pendingGuest($event, ['name' => 'Famille Koné', 'phone' => '07 07 07 07 07']);

    $url = $guest->whatsappUrl();

    expect($guest->normalizedPhone())->toBe('2250707070707')
        ->and($url)->toStartWith('https://wa.me/2250707070707?text=')
        ->and($url)->toContain(rawurlencode($guest->invitationUrl()));

    $international = pendingGuest($event, ['phone' => '+225 01 02 03 04 05']);
    expect($international->normalizedPhone())->toBe('2250102030405');

    $withoutPhone = pendingGuest($event, ['phone' => null]);
    expect($withoutPhone->whatsappUrl())->toBeNull();
});

it('affiche le lien de relance depuis l’événement', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Relancer les foyers')
        ->assertSee(route('reminders.index', $event), false);
});
