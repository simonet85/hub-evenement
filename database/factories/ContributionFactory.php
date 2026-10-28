<?php

namespace Database\Factories;

use App\Enums\ContributionKind;
use App\Enums\ContributionStatus;
use App\Models\Contribution;
use App\Models\Event;
use App\Models\Gift;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contribution>
 */
class ContributionFactory extends Factory
{
    protected $model = Contribution::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'guest_id' => null,
            'gift_id' => null,
            'kind' => ContributionKind::Cagnotte,
            'amount' => fake()->numberBetween(5, 100) * 1000,
            'quantity' => null,
            'contributor_name' => fake()->name(),
            'phone' => fake()->numerify('+225 07 ## ## ## ##'),
            'message' => fake()->optional()->sentence(),
            'anonymous' => false,
            'status' => ContributionStatus::Pending,
            'provider' => 'moneyfusion',
            'provider_token' => 'sim_'.fake()->unique()->regexify('[A-Za-z0-9]{24}'),
            'provider_url' => null,
            'expires_at' => now()->addHours(2),
        ];
    }

    public function forGuest(Guest $guest): static
    {
        return $this->state(fn () => [
            'event_id' => $guest->event_id,
            'guest_id' => $guest->id,
            'contributor_name' => $guest->name,
        ]);
    }

    public function forGift(Gift $gift, int $quantity = 1): static
    {
        return $this->state(fn () => [
            'event_id' => $gift->event_id,
            'gift_id' => $gift->id,
            'kind' => ContributionKind::Gift,
            'amount' => $gift->price * $quantity,
            'quantity' => $quantity,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => ContributionStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => ContributionStatus::Failed,
            'failed_at' => now(),
        ]);
    }

    public function refunded(?string $note = 'Remboursement de test'): static
    {
        return $this->state(fn () => [
            'status' => ContributionStatus::Refunded,
            'paid_at' => now()->subDay(),
            'refunded_at' => now(),
            'refund_note' => $note,
        ]);
    }
}
