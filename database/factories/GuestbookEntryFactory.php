<?php

namespace Database\Factories;

use App\Enums\GuestbookStatus;
use App\Models\Guest;
use App\Models\GuestbookEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestbookEntry>
 */
class GuestbookEntryFactory extends Factory
{
    protected $model = GuestbookEntry::class;

    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'event_id' => fn (array $attributes) => Guest::query()->findOrFail($attributes['guest_id'])->event_id,
            'author_name' => fake()->name(),
            'message' => fake()->sentence(12),
            'status' => GuestbookStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => GuestbookStatus::Approved]);
    }
}
