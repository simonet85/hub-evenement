<?php

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Services\EventMetrics;
use App\Services\Exports\EventExporter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

afterEach(function () {
    Carbon::setTestNow();
});

function catererShowUrl(Event $event, Carbon $expires): string
{
    return URL::temporarySignedRoute('caterer.show', $expires, ['event' => $event]);
}

function catererExportUrl(Event $event, string $format, Carbon $expires): string
{
    return URL::temporarySignedRoute('caterer.export', $expires, [
        'event' => $event,
        'format' => $format,
    ]);
}

beforeEach(function () {
    Carbon::setTestNow('2026-10-26 12:00:00');

    $this->owner = User::factory()->create();
    $this->event = Event::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'Mariage Awa & Yao',
        'starts_at' => '2026-12-12 17:00:00',
        'venue_name' => 'Espace Latrille',
    ]);

    $this->attending = Guest::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'Famille Présente',
        'email' => 'prive@example.com',
        'phone' => '+225 01 02 03 04 05',
        'allowed_seats' => 4,
        'rsvp_status' => RsvpStatus::Attending,
        'rsvp_seats' => 3,
        'attendee_names' => "Awa\nKoffi",
        'dietary_notes' => 'Sans gluten',
        'rsvp_message' => 'Message privé',
        'rsvp_responded_at' => now()->subDay(),
    ]);

    $this->declined = Guest::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'Foyer Absent',
        'email' => 'absent@example.com',
        'rsvp_status' => RsvpStatus::Declined,
        'rsvp_responded_at' => now()->subDay(),
    ]);

    $this->pending = Guest::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'Foyer En Attente',
    ]);

    $this->expires = now()->addDays(7);
    $this->url = catererShowUrl($this->event, $this->expires);
});

it('ouvre le portail traiteur signé sans compte, sans coordonnées', function () {
    $this->get($this->url)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertSee('Mariage Awa &amp; Yao', false)
        ->assertSee('Couverts confirmés')
        ->assertSee('Famille Présente')
        ->assertSee('Sans gluten')
        ->assertSee('Awa, Koffi')
        ->assertSee($this->expires->translatedFormat('j F Y \à H\hi'))
        ->assertDontSee('prive@example.com')
        ->assertDontSee('+225 01 02 03 04 05')
        ->assertDontSee('Message privé')
        ->assertDontSee('Foyer Absent')
        ->assertDontSee('Foyer En Attente');
});

it('refuse un portail traiteur non signé, altéré ou expiré', function () {
    $this->get(route('caterer.show', $this->event))
        ->assertForbidden();

    $tampered = preg_replace('/signature=[^&]+/', 'signature=invalide', $this->url);

    $this->get($tampered)
        ->assertForbidden();

    $this->get(catererShowUrl($this->event, now()->subMinute()))
        ->assertForbidden();
});

it('exporte les couverts en CSV signé sans coordonnées', function () {
    $response = $this->get(catererExportUrl($this->event, 'csv', $this->expires))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $content = $response->getContent();

    expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue()
        ->and($content)->toContain('Foyer;Couverts;Prénoms;Régime / allergies')
        ->and($content)->toContain('Famille Présente;3;Awa, Koffi;Sans gluten')
        ->and($content)->not->toContain('prive@example.com')
        ->and($content)->not->toContain('+225 01 02 03 04 05')
        ->and($content)->not->toContain('Message privé')
        ->and($content)->not->toContain('Foyer Absent')
        ->and($content)->not->toContain('Foyer En Attente');
});

it('exporte les couverts en XLSX signé sans coordonnées', function () {
    $response = $this->get(catererExportUrl($this->event, 'xlsx', $this->expires))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $content = $response->getContent();
    $sheets = readXlsx($content);
    $cells = collect($sheets)->flatten(2)->filter(fn ($value) => is_string($value))->implode(' | ');

    expect(str_starts_with($content, 'PK'))->toBeTrue()
        ->and(array_keys($sheets))->toBe(['Couverts', 'Synthèse'])
        ->and($sheets['Couverts'][0])->toBe(['Foyer', 'Couverts', 'Prénoms', 'Régime / allergies'])
        ->and($sheets['Couverts'][1])->toBe(['Famille Présente', 3, 'Awa, Koffi', 'Sans gluten'])
        ->and($cells)->not->toContain('prive@example.com')
        ->and($cells)->not->toContain('+225 01 02 03 04 05')
        ->and($cells)->not->toContain('Message privé')
        ->and($cells)->not->toContain('Foyer Absent');
});

it('exporte le dossier traiteur en PDF signé', function () {
    $response = $this->get(catererExportUrl($this->event, 'pdf', $this->expires))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect(str_starts_with($response->getContent(), '%PDF'))->toBeTrue()
        ->and($response->headers->get('content-disposition'))
        ->toContain('attachment')
        ->toContain('.pdf');
});

it('refuse les exports traiteur sans signature ou expirés', function (string $format) {
    $this->get(route('caterer.export', [$this->event, $format]))
        ->assertForbidden();

    $this->get(catererExportUrl($this->event, $format, now()->subMinute()))
        ->assertForbidden();
})->with(['csv', 'xlsx', 'pdf']);

it('prépare un dossier PDF limité aux foyers présents et sans coordonnées', function () {
    $exporter = new EventExporter($this->event);

    $html = view('exports.caterer-pdf', [
        'event' => $this->event,
        'households' => $exporter->catererHouseholds(),
        'summary' => (new EventMetrics($this->event))->summary(),
        'summaryRows' => $exporter->summaryRows(),
        'generatedAt' => now(),
    ])->render();

    expect($html)->toContain('Famille Présente')
        ->toContain('Sans gluten')
        ->toContain('Awa, Koffi')
        ->not->toContain('prive@example.com')
        ->not->toContain('+225 01 02 03 04 05')
        ->not->toContain('Message privé')
        ->not->toContain('Foyer Absent')
        ->not->toContain('Foyer En Attente');
});

it('renvoie 404 pour un format d’export inconnu', function () {
    $this->get('/traiteur/'.$this->event->id.'/exports/docx')
        ->assertNotFound();
});

it('signe les téléchargements du portail avec l’expiration de la page', function () {
    $html = html_entity_decode($this->get($this->url)->getContent());

    preg_match_all(
        '/href="([^"]*\/traiteur\/'.$this->event->id.'\/exports\/(?:csv|xlsx|pdf)\?[^"]*)"/',
        $html,
        $matches
    );

    expect($matches[1])->toHaveCount(3);

    foreach ($matches[1] as $exportUrl) {
        parse_str((string) parse_url($exportUrl, PHP_URL_QUERY), $query);

        expect((int) $query['expires'])->toBe($this->expires->getTimestamp());

        $this->get($exportUrl)->assertOk();
    }
});
