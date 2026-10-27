<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 month', '+6 months');

        return [
            'user_id' => User::factory(),
            'name' => ucfirst(fake()->words(3, true)),
            'type' => fake()->randomElement(EventType::cases()),
            'starts_at' => $startsAt,
            'venue_name' => 'Salle '.fake()->city(),
            'venue_address' => fake()->address(),
            'rsvp_deadline' => fake()->dateTimeBetween('now', $startsAt),
        ];
    }
}
