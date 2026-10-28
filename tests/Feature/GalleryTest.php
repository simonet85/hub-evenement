<?php

use App\Enums\PhotoStatus;
use App\Jobs\GeneratePhotoDerivatives;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Storage::fake('local');
});

function galleryEvent(User $owner): Event
{
    return Event::factory()->create(['user_id' => $owner->id]);
}

function galleryGuest(Event $event): Guest
{
    return Guest::factory()->create(['event_id' => $event->id]);
}

function galleryPhoto(Event $event, array $overrides = []): Photo
{
    return Photo::factory()->create(array_merge(['event_id' => $event->id], $overrides));
}

function storeDerivatives(Photo $photo): void
{
    $bytes = UploadedFile::fake()->image('derivative.jpg', 60, 40)->getContent();

    foreach (array_filter([$photo->path, $photo->thumb_path, $photo->web_path]) as $path) {
        Storage::disk('local')->put($path, $bytes);
    }
}

it('enregistre un envoi de photos en attente sur le disque privé', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = galleryEvent($owner);

    $this->actingAs($owner)
        ->post(route('gallery.store', $event), [
            'photos' => [
                UploadedFile::fake()->image('ceremonie.jpg', 2000, 1500),
                UploadedFile::fake()->image('reception.png', 1200, 800),
            ],
        ])
        ->assertRedirect(route('gallery.index', $event))
        ->assertSessionHas('status', '2 photos ajoutées. Elles seront visibles après validation.');

    $photos = Photo::query()->orderBy('id')->get();

    expect($photos)->toHaveCount(2)
        ->and($photos[0]->event_id)->toBe($event->id)
        ->and($photos[0]->uploaded_by)->toBe($owner->id)
        ->and($photos[0]->original_name)->toBe('ceremonie.jpg')
        ->and($photos[0]->status)->toBe(PhotoStatus::Pending)
        ->and($photos[0]->mime)->toBe('image/jpeg')
        ->and($photos[0]->width)->toBe(2000)
        ->and($photos[0]->height)->toBe(1500)
        ->and($photos[0]->path)->toStartWith('events/'.$event->id.'/photos/')
        ->and($photos[0]->thumb_path)->toBeNull()
        ->and($photos[1]->mime)->toBe('image/png');

    Storage::disk('local')->assertExists($photos[0]->path);
    Storage::disk('local')->assertExists($photos[1]->path);

    Queue::assertPushed(GeneratePhotoDerivatives::class, 2);
});

it('refuse les fichiers invalides et les lots hors limites', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = galleryEvent($owner);

    $this->actingAs($owner)
        ->post(route('gallery.store', $event), ['photos' => []])
        ->assertSessionHasErrors('photos');

    $this->actingAs($owner)
        ->post(route('gallery.store', $event), [
            'photos' => collect(range(1, 11))
                ->map(fn (int $index) => UploadedFile::fake()->image("photo-{$index}.jpg"))
                ->all(),
        ])
        ->assertSessionHasErrors('photos');

    $this->actingAs($owner)
        ->post(route('gallery.store', $event), [
            'photos' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
        ])
        ->assertRedirect(route('gallery.index', $event))
        ->assertSessionHasErrors('photos.0');

    $this->actingAs($owner)
        ->post(route('gallery.store', $event), [
            'photos' => [UploadedFile::fake()->image('animation.gif', 40, 40)],
        ])
        ->assertSessionHasErrors('photos.0');

    $this->actingAs($owner)
        ->post(route('gallery.store', $event), [
            'photos' => [UploadedFile::fake()->create('image.webp', 100, 'image/webp')],
        ])
        ->assertSessionHasErrors('photos.0');

    $this->actingAs($owner)
        ->post(route('gallery.store', $event), [
            'photos' => [UploadedFile::fake()->image('trop-lourde.jpg')->size(10_241)],
        ])
        ->assertSessionHasErrors('photos.0');

    expect(Photo::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('redirige une validation vers la galerie même après un aperçu', function () {
    $owner = User::factory()->create();
    $event = galleryEvent($owner);
    $photo = galleryPhoto($event);
    Storage::disk('local')->put($photo->path, UploadedFile::fake()->image('apercu.jpg', 60, 40)->getContent());

    $this->actingAs($owner)
        ->get(route('gallery.preview', [$event, $photo]))
        ->assertOk();

    $this->actingAs($owner)
        ->post(route('gallery.store', $event), [
            'photos' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
        ])
        ->assertRedirect(route('gallery.index', $event))
        ->assertSessionHasErrors('photos.0');
});

it('réserve la gestion de la galerie aux membres de l’événement', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $event = galleryEvent($owner);
    $stranger = User::factory()->create();
    $coOrganizer = makeCoOrganizer($event);

    $this->get(route('gallery.index', $event))->assertRedirect(route('login'));

    $this->actingAs($stranger)->get(route('gallery.index', $event))->assertForbidden();
    $this->actingAs($stranger)
        ->post(route('gallery.store', $event), [
            'photos' => [UploadedFile::fake()->image('intruse.jpg')],
        ])
        ->assertForbidden();

    $this->actingAs($coOrganizer)->get(route('gallery.index', $event))->assertOk();
    $this->actingAs($coOrganizer)
        ->post(route('gallery.store', $event), [
            'photos' => [UploadedFile::fake()->image('co-organisateur.jpg')],
        ])
        ->assertRedirect(route('gallery.index', $event));

    expect(Photo::sole()->uploaded_by)->toBe($coOrganizer->id);
});

it('génère une vignette et une version web bornées', function () {
    $event = Event::factory()->create();
    $path = 'events/'.$event->id.'/photos/grande.jpg';
    Storage::disk('local')->put($path, UploadedFile::fake()->image('grande.jpg', 2400, 1200)->getContent());

    $photo = galleryPhoto($event, ['path' => $path, 'mime' => 'image/jpeg']);

    (new GeneratePhotoDerivatives($photo))->handle();

    $photo->refresh();

    expect($photo->web_path)->toBe('events/'.$event->id.'/photos/grande-web.jpg')
        ->and($photo->thumb_path)->toBe('events/'.$event->id.'/photos/grande-thumb.jpg')
        ->and($photo->width)->toBe(1600)
        ->and($photo->height)->toBe(800);

    Storage::disk('local')->assertExists($photo->web_path);
    Storage::disk('local')->assertExists($photo->thumb_path);

    [$webWidth, $webHeight] = getimagesize(Storage::disk('local')->path($photo->web_path));
    [$thumbWidth, $thumbHeight] = getimagesize(Storage::disk('local')->path($photo->thumb_path));

    expect($webWidth)->toBe(1600)
        ->and($webHeight)->toBe(800)
        ->and($thumbWidth)->toBe(480)
        ->and($thumbHeight)->toBe(240);
});

it('borne le plus grand côté des dérivés en portrait', function () {
    $event = Event::factory()->create();
    $path = 'events/'.$event->id.'/photos/portrait.jpg';
    Storage::disk('local')->put($path, UploadedFile::fake()->image('portrait.jpg', 1200, 2400)->getContent());

    $photo = galleryPhoto($event, ['path' => $path, 'mime' => 'image/jpeg']);

    (new GeneratePhotoDerivatives($photo))->handle();

    $photo->refresh();

    expect($photo->width)->toBe(800)
        ->and($photo->height)->toBe(1600);

    [$webWidth, $webHeight] = getimagesize(Storage::disk('local')->path($photo->web_path));
    [$thumbWidth, $thumbHeight] = getimagesize(Storage::disk('local')->path($photo->thumb_path));

    expect($webWidth)->toBe(800)
        ->and($webHeight)->toBe(1600)
        ->and($thumbWidth)->toBe(240)
        ->and($thumbHeight)->toBe(480);
});

it('conserve le format PNG pour les dérivés', function () {
    $event = Event::factory()->create();
    $path = 'events/'.$event->id.'/photos/transparente.png';
    Storage::disk('local')->put($path, UploadedFile::fake()->image('transparente.png', 900, 600)->getContent());

    $photo = galleryPhoto($event, ['path' => $path, 'mime' => 'image/png']);

    (new GeneratePhotoDerivatives($photo))->handle();

    $photo->refresh();

    expect($photo->web_path)->toEndWith('-web.png')
        ->and($photo->thumb_path)->toEndWith('-thumb.png');

    Storage::disk('local')->assertExists($photo->web_path);
    Storage::disk('local')->assertExists($photo->thumb_path);

    expect(getimagesize(Storage::disk('local')->path($photo->web_path))[2])->toBe(IMAGETYPE_PNG);
});

it('échoue sans chemins partiels si l’original a disparu', function () {
    $photo = galleryPhoto(Event::factory()->create(), ['path' => 'events/photos/absente.jpg']);

    expect(fn () => (new GeneratePhotoDerivatives($photo))->handle())
        ->toThrow(RuntimeException::class);

    expect($photo->fresh()->thumb_path)->toBeNull()
        ->and($photo->fresh()->web_path)->toBeNull();
});

it('sert les dérivés publiés par URL signée uniquement', function () {
    $event = Event::factory()->create();
    $photo = Photo::factory()->approved()->processed()->create(['event_id' => $event->id]);
    storeDerivatives($photo);

    $thumb = URL::temporarySignedRoute('gallery.photo', now()->addMinutes(30), ['photo' => $photo, 'variant' => 'thumb']);
    $web = URL::temporarySignedRoute('gallery.photo', now()->addMinutes(30), ['photo' => $photo, 'variant' => 'web']);

    $this->get($thumb)->assertOk();
    $this->get($web)->assertOk();

    $this->get(route('gallery.photo', [$photo, 'thumb']))->assertForbidden();
    $this->get($thumb.'&tampered=1')->assertForbidden();

    $photo->forceFill(['status' => PhotoStatus::Hidden])->save();
    $this->get($thumb)->assertNotFound();

    $photo->forceFill(['status' => PhotoStatus::Pending])->save();
    $this->get($web)->assertNotFound();

    $photo->forceFill(['status' => PhotoStatus::Approved])->save();

    $original = URL::temporarySignedRoute('gallery.photo', now()->addMinutes(30), ['photo' => $photo, 'variant' => 'original']);
    $this->get($original)->assertNotFound();

    Storage::disk('local')->delete($photo->thumb_path);
    $this->get($thumb)->assertNotFound();
});

it('affiche un aperçu privé aux organisateurs avec repli sur l’original', function () {
    $owner = User::factory()->create();
    $event = galleryEvent($owner);
    $photo = galleryPhoto($event);
    Storage::disk('local')->put($photo->path, UploadedFile::fake()->image('apercu.jpg', 60, 40)->getContent());

    $this->get(route('gallery.preview', [$event, $photo]))->assertRedirect(route('login'));

    $this->actingAs($owner)
        ->get(route('gallery.preview', [$event, $photo]))
        ->assertOk();

    $this->actingAs(User::factory()->create())
        ->get(route('gallery.preview', [$event, $photo]))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('gallery.preview', [$event, $photo, 'inconnu']))
        ->assertNotFound();

    $otherEvent = galleryEvent($owner);
    $otherPhoto = galleryPhoto($otherEvent);

    $this->actingAs($owner)
        ->get(route('gallery.preview', [$event, $otherPhoto]))
        ->assertNotFound();
});

it('affiche uniquement les photos publiées et échappe les légendes', function () {
    $event = Event::factory()->create();
    $guest = galleryGuest($event);

    galleryPhoto($event, [
        'status' => PhotoStatus::Approved,
        'caption' => 'Premier regard',
        'created_at' => now()->subMinute(),
    ]);
    galleryPhoto($event, [
        'status' => PhotoStatus::Approved,
        'caption' => '<script>alert(1)</script>',
        'created_at' => now(),
    ]);
    galleryPhoto($event, ['caption' => 'Photo en attente']);
    galleryPhoto($event, ['status' => PhotoStatus::Hidden, 'caption' => 'Photo masquée']);

    $this->get(route('invitations.gallery', $guest->invitation_token))
        ->assertOk()
        ->assertSee('Premier regard')
        ->assertSee('<script>alert(1)</script>')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('Photo en attente')
        ->assertDontSee('Photo masquée')
        ->assertSeeInOrder(['<script>alert(1)</script>', 'Premier regard'])
        ->assertSee('noindex');
});

it('affiche l’état vide et refuse un jeton inconnu', function () {
    $guest = galleryGuest(Event::factory()->create());

    $this->get(route('invitations.gallery', $guest->invitation_token))
        ->assertOk()
        ->assertSee("Aucune photo publiée pour l'instant", false)
        ->assertSee("Retour à l'invitation", false);

    $this->get(route('invitations.gallery', 'jeton-inconnu'))->assertNotFound();
});

it('pagine la galerie invitée par douze', function () {
    $event = Event::factory()->create();
    $guest = galleryGuest($event);

    foreach (range(1, 13) as $index) {
        galleryPhoto($event, [
            'status' => PhotoStatus::Approved,
            'caption' => 'Photo '.$index,
            'created_at' => now()->subMinutes(14 - $index),
        ]);
    }

    $first = $this->get(route('invitations.gallery', $guest->invitation_token))->assertOk();
    expect($first->viewData('photos')->count())->toBe(12);

    $second = $this->get(route('invitations.gallery', $guest->invitation_token).'?galerie_page=2')->assertOk();
    expect($second->viewData('photos')->count())->toBe(1);
});

it('affiche le lien galerie sur l’invitation avec le nombre de photos publiées', function () {
    $event = Event::factory()->create();
    $guest = galleryGuest($event);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertDontSee('Galerie photo', false);

    galleryPhoto($event, ['status' => PhotoStatus::Approved]);
    galleryPhoto($event, ['status' => PhotoStatus::Approved]);
    galleryPhoto($event);

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertSee('Galerie photo (2)', false)
        ->assertSee(route('invitations.gallery', $guest->invitation_token), false);
});

it('publie, masque et republie une photo', function () {
    $owner = User::factory()->create();
    $event = galleryEvent($owner);
    $guest = galleryGuest($event);
    $photo = galleryPhoto($event, ['caption' => 'Le premier baiser']);

    $this->actingAs($owner)
        ->get(route('gallery.index', $event))
        ->assertOk()
        ->assertSee('En attente de validation')
        ->assertSee($photo->original_name);

    $this->actingAs($owner)
        ->post(route('gallery.approve', [$event, $photo]))
        ->assertRedirect(route('gallery.index', $event))
        ->assertSessionHas('status', 'Photo publiée.');

    $photo->refresh();
    expect($photo->status)->toBe(PhotoStatus::Approved)
        ->and($photo->moderated_by)->toBe($owner->id)
        ->and($photo->moderated_at)->not->toBeNull();

    $this->get(route('invitations.gallery', $guest->invitation_token))
        ->assertSee('Le premier baiser');

    $this->actingAs($owner)
        ->post(route('gallery.hide', [$event, $photo]))
        ->assertSessionHas('status', 'Photo masquée.');

    $this->get(route('invitations.gallery', $guest->invitation_token))
        ->assertDontSee('Le premier baiser');

    $this->actingAs($owner)->post(route('gallery.approve', [$event, $photo]));

    $this->get(route('invitations.gallery', $guest->invitation_token))
        ->assertSee('Le premier baiser');
});

it('enregistre une légende facultative', function () {
    $owner = User::factory()->create();
    $event = galleryEvent($owner);
    $photo = galleryPhoto($event);

    $this->actingAs($owner)
        ->put(route('gallery.caption', [$event, $photo]), ['caption' => '  Le premier regard  '])
        ->assertRedirect(route('gallery.index', $event).'#photo-'.$photo->id)
        ->assertSessionHas('status', 'Légende enregistrée.');

    expect($photo->fresh()->caption)->toBe('Le premier regard');

    $this->actingAs($owner)
        ->put(route('gallery.caption', [$event, $photo]), ['caption' => '']);

    expect($photo->fresh()->caption)->toBeNull();

    $this->actingAs($owner)
        ->put(route('gallery.caption', [$event, $photo]), ['caption' => str_repeat('a', 121)])
        ->assertRedirect(route('gallery.index', $event).'#photo-'.$photo->id)
        ->assertSessionHasErrors('caption');

    expect($photo->fresh()->caption)->toBeNull();
});

it('supprime la photo et ses fichiers au rejet', function () {
    $owner = User::factory()->create();
    $event = galleryEvent($owner);
    $photo = Photo::factory()->processed()->create(['event_id' => $event->id]);

    foreach ([$photo->path, $photo->thumb_path, $photo->web_path] as $path) {
        Storage::disk('local')->put($path, 'bytes');
    }

    $this->actingAs($owner)
        ->post(route('gallery.reject', [$event, $photo]))
        ->assertRedirect(route('gallery.index', $event))
        ->assertSessionHas('status', 'Photo supprimée.');

    $this->assertDatabaseMissing('photos', ['id' => $photo->id]);

    foreach ([$photo->path, $photo->thumb_path, $photo->web_path] as $path) {
        Storage::disk('local')->assertMissing($path);
    }
});

it('renvoie 404 pour une photo d’un autre événement', function () {
    $owner = User::factory()->create();
    $event = galleryEvent($owner);
    $otherEvent = galleryEvent($owner);
    $foreignPhoto = galleryPhoto($otherEvent, ['caption' => 'Ailleurs']);

    $this->actingAs($owner)
        ->post(route('gallery.approve', [$event, $foreignPhoto]))
        ->assertNotFound();
    $this->actingAs($owner)
        ->post(route('gallery.hide', [$event, $foreignPhoto]))
        ->assertNotFound();
    $this->actingAs($owner)
        ->post(route('gallery.reject', [$event, $foreignPhoto]))
        ->assertNotFound();
    $this->actingAs($owner)
        ->put(route('gallery.caption', [$event, $foreignPhoto]), ['caption' => 'Volée'])
        ->assertNotFound();

    $foreignPhoto->refresh();

    expect($foreignPhoto->status)->toBe(PhotoStatus::Pending)
        ->and($foreignPhoto->caption)->toBe('Ailleurs');
});

it('affiche les compteurs de photos à modérer', function () {
    $owner = User::factory()->create();
    $event = galleryEvent($owner);

    galleryPhoto($event);
    galleryPhoto($event);
    galleryPhoto($event, ['status' => PhotoStatus::Approved]);
    galleryPhoto($event, ['status' => PhotoStatus::Hidden]);

    $this->actingAs($owner)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Galerie photo (2)', false);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('2 photos à modérer');
});
