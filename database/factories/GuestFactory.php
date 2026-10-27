<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->lastName().' '.fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('+225 07 ## ## ## ##'),
            'allowed_seats' => fake()->numberBetween(1, 4),
        ];
    }
}
