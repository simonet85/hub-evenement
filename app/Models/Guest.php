<?php

namespace App\Models;

use App\Enums\RsvpStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'allowed_seats',
    ];

    protected $attributes = [
        'rsvp_status' => 'pending',
        'rsvp_is_late' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (Guest $guest) {
            $guest->invitation_token ??= self::generateInvitationToken();
            $guest->entry_token ??= self::generateEntryToken();
        });
    }

    protected function casts(): array
    {
        return [
            'allowed_seats' => 'integer',
            'rsvp_status' => RsvpStatus::class,
            'rsvp_seats' => 'integer',
            'rsvp_is_late' => 'boolean',
            'rsvp_responded_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public static function generateInvitationToken(): string
    {
        return Str::random(48);
    }

    public static function generateEntryToken(): string
    {
        return Str::random(48);
    }

    public function hasResponded(): bool
    {
        return $this->rsvp_responded_at !== null;
    }

    public function isPastDeadline(): bool
    {
        $deadline = $this->event?->rsvp_deadline;

        return $deadline !== null && $deadline->lt(today());
    }

    public function canRespond(): bool
    {
        return ! $this->hasResponded() || ! $this->isPastDeadline();
    }

    public function invitationUrl(): string
    {
        return route('invitations.show', $this->invitation_token);
    }

    public function entryUrl(): string
    {
        return route('check-in.show', $this->entry_token);
    }

    public function hasCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }
}
