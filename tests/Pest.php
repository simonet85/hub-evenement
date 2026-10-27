<?php

use App\Models\Event;
use App\Models\EventRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
