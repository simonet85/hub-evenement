<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Enums\GuestbookStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoInvitationSeeder extends Seeder
{
    public function run(): void
    {
        $token = config('hub.demo_invitation_token');

        if (! is_string($token) || $token === '') {
            $this->command?->warn('DEMO_INVITATION_TOKEN absent : invitation de démonstration non créée.');

            return;
        }

        $owner = User::query()->firstOrCreate(
            ['email' => 'demo@hub-evenement.test'],
            ['name' => 'Démo Hub Événement', 'password' => Str::password(32)],
        );

        $event = Event::query()->firstOrNew([
            'user_id' => $owner->id,
            'name' => 'Awa & Koffi',
        ]);

        $event->fill([
            'type' => EventType::Mariage,
            'starts_at' => Carbon::parse('2026-12-12 16:00'),
            'venue_name' => 'Maison Gbaka',
            'venue_address' => 'Cocody, Abidjan',
            'dress_code' => 'Tenue de cérémonie, tons chauds',
            'rsvp_deadline' => Carbon::parse('2026-11-28'),
            'cagnotte_enabled' => true,
            'cagnotte_goal' => 500_000,
            'cagnotte_message' => 'Votre présence est notre plus beau cadeau. Pour ceux qui le souhaitent, cette cagnotte accompagne notre lune de miel.',
        ]);
        $event->user_id = $owner->id;
        $event->save();

        $guest = Guest::query()->firstOrNew(['invitation_token' => $token]);
        $guest->fill(['name' => 'Famille Kouassi', 'allowed_seats' => 2]);
        $guest->event_id = $event->id;
        $guest->entry_token ??= Guest::generateEntryToken();
        $guest->forceFill([
            'rsvp_status' => 'pending',
            'rsvp_seats' => null,
            'attendee_names' => null,
            'dietary_notes' => null,
            'rsvp_message' => null,
            'rsvp_responded_at' => null,
            'rsvp_is_late' => false,
            'checked_in_at' => null,
            'checked_in_by' => null,
        ]);
        $guest->save();

        if ($event->programSteps()->doesntExist()) {
            $steps = [
                ['start_time' => '10:00', 'title' => 'Cérémonie traditionnelle', 'location' => 'Maison Gbaka', 'description' => 'Bénédiction des familles et présentation des mariés.'],
                ['start_time' => '12:30', 'title' => 'Déjeuner de famille', 'location' => 'Maison Gbaka', 'description' => 'Repas partagé dans la cour.'],
                ['start_time' => '16:00', 'title' => 'Réception', 'location' => 'Maison Gbaka', 'description' => 'Entrée des mariés, discours et soirée dansante.'],
            ];

            foreach ($steps as $position => $step) {
                $event->programSteps()->create($step + ['position' => $position + 1]);
            }
        }

        if ($event->guestbookEntries()->doesntExist()) {
            $aunt = new Guest(['name' => 'Tante Aïcha', 'allowed_seats' => 1]);
            $aunt->event_id = $event->id;
            $aunt->save();

            $entry = $event->guestbookEntries()->make([
                'guest_id' => $aunt->id,
                'author_name' => $aunt->name,
                'message' => 'Que Dieu bénisse votre union et votre foyer. Nous serons là pour danser !',
                'status' => GuestbookStatus::Approved,
            ]);
            $entry->moderated_by = $owner->id;
            $entry->moderated_at = now();
            $entry->save();
        }
    }
}
