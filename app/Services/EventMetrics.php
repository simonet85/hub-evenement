<?php

namespace App\Services;

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EventMetrics
{
    public function __construct(private Event $event) {}

    /**
     * @return Collection<int, Guest>
     */
    public function guests(): Collection
    {
        if ($this->event->relationLoaded('guests')) {
            return $this->event->guests->sortBy('name')->values();
        }

        $guests = $this->event->guests()
            ->with('checkedInBy')
            ->orderBy('name')
            ->get();

        $this->event->setRelation('guests', $guests);

        return $guests;
    }

    /**
     * @return Collection<int, Guest>
     */
    public function attending(): Collection
    {
        return $this->guests()
            ->filter(fn (Guest $guest) => $guest->rsvp_status === RsvpStatus::Attending)
            ->values();
    }

    /**
     * @return Collection<int, Guest>
     */
    public function dietary(): Collection
    {
        return $this->attending()
            ->filter(fn (Guest $guest) => $guest->hasDietaryNotes())
            ->values();
    }

    /**
     * @return array{households: int, seats: int, responded: int, attending: int, declined: int, pending: int, coming: int, diet_households: int, late_responses: int}
     */
    public function summary(): array
    {
        $guests = $this->guests();
        $attending = $guests->filter(fn (Guest $guest) => $guest->rsvp_status === RsvpStatus::Attending);
        $responded = $guests->filter(fn (Guest $guest) => $guest->hasResponded())->count();

        return [
            'households' => $guests->count(),
            'seats' => (int) $guests->sum('allowed_seats'),
            'responded' => $responded,
            'attending' => $attending->count(),
            'declined' => $guests->filter(fn (Guest $guest) => $guest->rsvp_status === RsvpStatus::Declined)->count(),
            'pending' => $guests->count() - $responded,
            'coming' => (int) $attending->sum(fn (Guest $guest) => $guest->confirmedSeats()),
            'diet_households' => $attending->filter(fn (Guest $guest) => $guest->hasDietaryNotes())->count(),
            'late_responses' => $guests->filter(fn (Guest $guest) => $guest->hasResponded() && $guest->rsvp_is_late)->count(),
        ];
    }

    /**
     * @return array{days: list<array{date: Carbon, label: string, title: string, count: int, cumulative: int, percent: int}>, max: int, total: int}
     */
    public function evolution(int $days = 14): array
    {
        $days = max(1, $days);
        $start = today()->subDays($days - 1)->startOfDay();
        $counts = $this->guests()
            ->filter(fn (Guest $guest) => $guest->hasResponded())
            ->countBy(fn (Guest $guest) => $guest->rsvp_responded_at->toDateString());

        $total = 0;
        $cumulative = (int) $this->guests()
            ->filter(fn (Guest $guest) => $guest->hasResponded() && $guest->rsvp_responded_at->lt($start))
            ->count();

        $window = [];
        $maximum = 0;

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset);
            $count = (int) ($counts[$date->toDateString()] ?? 0);
            $cumulative += $count;
            $total += $count;
            $maximum = max($maximum, $count);

            $window[] = [
                'date' => $date,
                'label' => $date->format('d/m'),
                'title' => $date->translatedFormat('l j F Y'),
                'count' => $count,
                'cumulative' => $cumulative,
                'percent' => 0,
            ];
        }

        foreach ($window as $index => $day) {
            $window[$index]['percent'] = $maximum === 0 ? 0 : (int) round($day['count'] / $maximum * 100);
        }

        return ['days' => $window, 'max' => $maximum, 'total' => $total];
    }
}
