<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'starts_at',
        'venue_name',
        'venue_address',
        'dress_code',
        'latitude',
        'longitude',
        'rsvp_deadline',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'starts_at' => 'datetime',
            'rsvp_deadline' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(EventRole::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('roles', fn (Builder $query) => $query->where('user_id', $user->id));
        });
    }

    public function hasMember(User $user): bool
    {
        return $this->user_id === $user->id
            || $this->roles()->where('user_id', $user->id)->exists();
    }

    public function programSteps(): HasMany
    {
        return $this->hasMany(ProgramStep::class)->orderBy('position')->orderBy('id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function guestbookEntries(): HasMany
    {
        return $this->hasMany(GuestbookEntry::class);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function googleMapsUrl(): string
    {
        if ($this->hasCoordinates()) {
            return 'https://www.google.com/maps/dir/?api=1&destination='.$this->latitude.','.$this->longitude;
        }

        $query = trim($this->venue_name.' '.$this->venue_address);

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    }

    public function wazeUrl(): ?string
    {
        if (! $this->hasCoordinates()) {
            return null;
        }

        return 'https://waze.com/ul?ll='.$this->latitude.'%2C'.$this->longitude.'&navigate=yes';
    }
}
