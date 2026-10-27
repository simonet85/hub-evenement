<?php

namespace App\Services;

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    public const CHECKED_IN = 'checked_in';

    public const ALREADY_CHECKED_IN = 'already_checked_in';

    public const REQUIRES_CONFIRMATION = 'requires_confirmation';

    public function findByToken(string $token): ?Guest
    {
        return Guest::query()
            ->with(['event', 'checkedInBy'])
            ->where('entry_token', $token)
            ->first();
    }

    public function checkIn(Guest $guest, User $user, bool $confirmed = false): string
    {
        return DB::transaction(function () use ($guest, $user, $confirmed) {
            $locked = Guest::query()->lockForUpdate()->findOrFail($guest->id);

            if ($locked->hasCheckedIn()) {
                return self::ALREADY_CHECKED_IN;
            }

            if (! $confirmed && $locked->rsvp_status !== RsvpStatus::Attending) {
                return self::REQUIRES_CONFIRMATION;
            }

            $locked->forceFill([
                'checked_in_at' => now(),
                'checked_in_by' => $user->id,
            ])->save();

            return self::CHECKED_IN;
        });
    }

    public function undo(Guest $guest): void
    {
        $guest->forceFill([
            'checked_in_at' => null,
            'checked_in_by' => null,
        ])->save();

        $guest->unsetRelation('checkedInBy');
    }

    /**
     * @return array{households: int, checked_in_households: int, seats: int, checked_in_seats: int}
     */
    public function counters(Event $event): array
    {
        $guests = $event->guests()->get();
        $checkedIn = $guests->filter(fn (Guest $guest) => $guest->hasCheckedIn());

        return [
            'households' => $guests->count(),
            'checked_in_households' => $checkedIn->count(),
            'seats' => (int) $guests->sum('allowed_seats'),
            'checked_in_seats' => (int) $checkedIn->sum(fn (Guest $guest) => $this->expectedSeats($guest)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Guest $guest): array
    {
        return [
            'id' => $guest->id,
            'name' => $guest->name,
            'rsvp_status' => $guest->rsvp_status->value,
            'rsvp_label' => $guest->rsvp_status->label(),
            'seats' => $this->expectedSeats($guest),
            'allowed_seats' => $guest->allowed_seats,
            'attendee_names' => $guest->attendee_names,
            'checked_in' => $guest->hasCheckedIn(),
            'checked_in_at' => $guest->checked_in_at?->toIso8601String(),
            'checked_in_at_label' => $guest->checked_in_at?->translatedFormat('j F Y \à H\hi'),
            'checked_in_by' => $guest->checkedInBy?->name,
            'undo_url' => route('check-in.undo', [$guest->event, $guest]),
            'entry_url' => $guest->entryUrl(),
        ];
    }

    private function expectedSeats(Guest $guest): int
    {
        if ($guest->rsvp_status === RsvpStatus::Attending) {
            return (int) ($guest->rsvp_seats ?? $guest->allowed_seats);
        }

        return (int) $guest->allowed_seats;
    }
}
