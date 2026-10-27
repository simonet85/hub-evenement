<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;

it('importe un CSV point-virgule avec en-tête et signale les lignes invalides', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $csv = "nom;e-mail;téléphone;places\n"
        ."Famille Koné;kone@example.com;+225 07 00 00 00;3\n"
        ."Famille Bamba;bamba@example.com;+225 01 00 00 00;2\n"
        ."Famille Yao;;;0\n";

    $this->actingAs($owner)
        ->post(route('guests.import', $event), [
            'file' => UploadedFile::fake()->createWithContent('foyers.csv', $csv),
        ])
        ->assertRedirect()
        ->assertSessionHas('status')
        ->assertSessionHas('import_errors', fn ($errors) => is_array($errors) && count($errors) === 1);

    $this->assertDatabaseHas('guests', [
        'event_id' => $event->id,
        'name' => 'Famille Koné',
        'email' => 'kone@example.com',
        'allowed_seats' => 3,
    ]);

    $this->assertDatabaseHas('guests', [
        'event_id' => $event->id,
        'name' => 'Famille Bamba',
        'allowed_seats' => 2,
    ]);

    expect($event->guests()->count())->toBe(2)
        ->and($event->guests()->whereNotNull('invitation_token')->count())->toBe(2)
        ->and($event->guests()->whereNotNull('entry_token')->count())->toBe(2);
});

it('importe un CSV virgule sans en-tête avec un BOM', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $csv = "\xEF\xBB\xBFFamille Awa,awa@example.com,,4\nFamille Bamba,,,\n";

    $this->actingAs($owner)
        ->post(route('guests.import', $event), [
            'file' => UploadedFile::fake()->createWithContent('liste.csv', $csv),
        ])
        ->assertRedirect()
        ->assertSessionHas('status')
        ->assertSessionHas('import_errors', fn ($errors) => $errors === []);

    $this->assertDatabaseHas('guests', [
        'event_id' => $event->id,
        'name' => 'Famille Awa',
        'allowed_seats' => 4,
    ]);

    $this->assertDatabaseHas('guests', [
        'event_id' => $event->id,
        'name' => 'Famille Bamba',
        'allowed_seats' => 1,
    ]);
});

it('refuse un fichier qui n’est pas un CSV', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('guests.import', $event), [
            'file' => UploadedFile::fake()->create('liste.pdf', 10, 'application/pdf'),
        ])
        ->assertSessionHasErrors('file');

    expect($event->guests()->count())->toBe(0);
});

it('refuse l’import à un tiers', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id]);
    $stranger = User::factory()->create();

    $csv = "nom;e-mail;téléphone;places\nIntrus;;;1\n";

    $this->actingAs($stranger)
        ->post(route('guests.import', $event), [
            'file' => UploadedFile::fake()->createWithContent('foyers.csv', $csv),
        ])
        ->assertForbidden();

    expect($event->guests()->count())->toBe(0);
});
