<?php

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use App\Models\Event;
use App\Models\Gift;
use App\Models\Guest;
use App\Models\GuestbookEntry;
use App\Models\Photo;
use Illuminate\Support\Facades\Log;

function pastEvent(int $days): Event
{
    return Event::factory()->create([
        'starts_at' => now()->subDays($days),
        'rsvp_deadline' => now()->subDays($days + 10),
    ]);
}

it('simule la purge sans rien modifier', function () {
    $event = pastEvent(120);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $contribution = Contribution::factory()->forGuest($guest)->paid()->create([
        'phone' => '+225 07 00 00 00 00',
        'message' => 'Avec plaisir',
    ]);

    $this->artisan('guests:purge', ['--dry-run' => true])
        ->expectsOutputToContain('Simulation')
        ->assertSuccessful();

    expect(Guest::find($guest->id))->not->toBeNull()
        ->and($contribution->fresh()->contributor_name)->toBe($guest->name)
        ->and($contribution->fresh()->phone)->toBe('+225 07 00 00 00 00');
});

it('purge les foyers anciens et anonymise leurs contributions', function () {
    $event = pastEvent(120);
    $guest = Guest::factory()->create(['event_id' => $event->id, 'name' => 'Awa Koné']);
    $entry = GuestbookEntry::factory()->approved()->create([
        'event_id' => $event->id,
        'guest_id' => $guest->id,
    ]);
    $contribution = Contribution::factory()->forGuest($guest)->paid()->create([
        'phone' => '+225 07 00 00 00 00',
        'message' => 'Tous mes vœux',
    ]);
    $contribution->forceFill(['payload' => ['provider' => 'moneyfusion', 'customer' => 'Awa Koné']])->save();

    $gift = Gift::factory()->create(['event_id' => $event->id]);
    $photo = Photo::factory()->create(['event_id' => $event->id]);

    $recent = pastEvent(10);
    $recentGuest = Guest::factory()->create(['event_id' => $recent->id]);

    Log::spy();

    $this->artisan('guests:purge')
        ->expectsOutputToContain('Événement #'.$event->id)
        ->assertSuccessful();

    $purged = Contribution::findOrFail($contribution->id);

    expect(Event::find($event->id))->not->toBeNull()
        ->and(Guest::find($guest->id))->toBeNull()
        ->and(GuestbookEntry::find($entry->id))->toBeNull()
        ->and(Gift::find($gift->id))->not->toBeNull()
        ->and(Photo::find($photo->id))->not->toBeNull()
        ->and(Guest::find($recentGuest->id))->not->toBeNull()
        ->and($purged->guest_id)->toBeNull()
        ->and($purged->contributor_name)->toBe('Invité anonymisé')
        ->and($purged->phone)->toBe('')
        ->and($purged->message)->toBeNull()
        ->and($purged->payload)->toBeNull()
        ->and($purged->amount)->toBe($contribution->amount)
        ->and($purged->status)->toBe(ContributionStatus::Paid);

    Log::shouldHaveReceived('info')->once()->withArgs(
        fn (string $message, array $context) => $message === 'guest-data.purged'
            && $context === [
                'event_id' => $event->id,
                'guests' => 1,
                'guestbook' => 1,
                'contributions' => 1,
            ],
    );
});

it('reste idempotente sur une seconde exécution', function () {
    $event = pastEvent(120);
    $guest = Guest::factory()->create(['event_id' => $event->id]);
    $contribution = Contribution::factory()->forGuest($guest)->paid()->create();

    $this->artisan('guests:purge')->assertSuccessful();

    $this->artisan('guests:purge')
        ->expectsOutputToContain('0 foyer(s), 0 message(s), 0 contribution(s)')
        ->assertSuccessful();

    expect(Contribution::find($contribution->id)->contributor_name)->toBe('Invité anonymisé');
});

it('respecte le seuil de jours configuré', function () {
    config(['retention.guest_data_days' => 30]);

    $event = pastEvent(40);
    $guest = Guest::factory()->create(['event_id' => $event->id]);

    $this->artisan('guests:purge')->assertSuccessful();

    expect(Guest::find($guest->id))->toBeNull();
});

it('accepte un seuil personnalisé en jours', function () {
    $event = pastEvent(10);
    $guest = Guest::factory()->create(['event_id' => $event->id]);

    $this->artisan('guests:purge', ['--days' => 30])->assertSuccessful();

    expect(Guest::find($guest->id))->not->toBeNull();

    $this->artisan('guests:purge', ['--days' => 5])->assertSuccessful();

    expect(Guest::find($guest->id))->toBeNull();
});

it('refuse un nombre de jours invalide', function () {
    $this->artisan('guests:purge', ['--days' => 'abc'])
        ->expectsOutputToContain('Le nombre de jours doit être un entier positif ou nul.')
        ->assertFailed();
});
