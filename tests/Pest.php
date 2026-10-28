<?php

use App\Models\Event;
use App\Models\EventRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

/**
 * @return array<string, mixed>
 */
function eventData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Mariage Awa & Yao',
        'type' => 'mariage',
        'starts_at' => '2026-12-12T17:00',
        'venue_name' => 'Espace Latrille',
        'venue_address' => 'Cocody, Abidjan',
        'rsvp_deadline' => '2026-11-20',
    ], $overrides);
}

function makeCoOrganizer(Event $event): User
{
    $user = User::factory()->create();

    $event->roles()->create([
        'user_id' => $user->id,
        'role' => EventRole::ROLE_CO_ORGANIZER,
    ]);

    return $user;
}

/**
 * @return array<string, list<list<mixed>>>
 */
function readXlsx(string $content): array
{
    $path = tempnam(sys_get_temp_dir(), 'hub-test-xlsx-');
    file_put_contents($path, $content);

    $reader = new Reader;
    $sheets = [];

    try {
        $reader->open($path);

        foreach ($reader->getSheetIterator() as $sheet) {
            $rows = [];

            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(fn ($cell) => $cell->getValue(), $row->getCells());
            }

            $sheets[$sheet->getName()] = $rows;
        }
    } finally {
        $reader->close();
        @unlink($path);
    }

    return $sheets;
}
