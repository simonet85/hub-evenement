<?php

namespace Database\Factories;

use App\Enums\PhotoStatus;
use App\Models\Event;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'uploaded_by' => null,
            'original_name' => 'photo-'.Str::lower(Str::random(6)).'.jpg',
            'path' => 'events/photos/'.Str::uuid().'.jpg',
            'thumb_path' => null,
            'web_path' => null,
            'mime' => 'image/jpeg',
            'size' => fake()->numberBetween(100_000, 2_000_000),
            'width' => 1600,
            'height' => 1200,
            'caption' => null,
            'status' => PhotoStatus::Pending,
            'moderated_by' => null,
            'moderated_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => PhotoStatus::Approved,
            'moderated_at' => now(),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => [
            'status' => PhotoStatus::Hidden,
            'moderated_at' => now(),
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'thumb_path' => preg_replace('/\.(jpg|jpeg|png)$/', '-thumb.$1', $attributes['path']),
            'web_path' => preg_replace('/\.(jpg|jpeg|png)$/', '-web.$1', $attributes['path']),
        ]);
    }
}
