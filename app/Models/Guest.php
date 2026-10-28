<?php

namespace App\Models;

use App\Enums\RsvpStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Guest extends Model
{
    use HasFactory;

    public const MAX_REMINDERS = 2;

    public const REMINDER_INTERVAL_HOURS = 24;

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
            'reminders_opted_out_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
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

    public function confirmedSeats(): int
    {
        if ($this->rsvp_status !== RsvpStatus::Attending) {
            return 0;
        }

        return (int) ($this->rsvp_seats ?? $this->allowed_seats);
    }

    public function hasDietaryNotes(): bool
    {
        return filled($this->dietary_notes);
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

    public function hasOptedOutOfReminders(): bool
    {
        return $this->reminders_opted_out_at !== null;
    }

    public function reminderCount(?int $ignoreReminderId = null): int
    {
        return $this->reminderCollection($ignoreReminderId)->count();
    }

    public function lastReminderAt(?int $ignoreReminderId = null): ?Carbon
    {
        $last = $this->reminderCollection($ignoreReminderId)->max('created_at');

        return $last === null ? null : Carbon::parse($last);
    }

    public function nextReminderAt(?int $ignoreReminderId = null): ?Carbon
    {
        if ($this->reminderCount($ignoreReminderId) >= self::MAX_REMINDERS) {
            return null;
        }

        return $this->lastReminderAt($ignoreReminderId)?->copy()->addHours(self::REMINDER_INTERVAL_HOURS);
    }

    public function canReceiveReminder(?int $ignoreReminderId = null): bool
    {
        if ($this->hasResponded() || $this->hasOptedOutOfReminders() || blank($this->email)) {
            return false;
        }

        if ($this->reminderCount($ignoreReminderId) >= self::MAX_REMINDERS) {
            return false;
        }

        $next = $this->nextReminderAt($ignoreReminderId);

        return $next === null || $next->isPast();
    }

    private function reminderCollection(?int $ignoreReminderId = null)
    {
        if ($ignoreReminderId === null && $this->relationLoaded('reminders')) {
            return $this->reminders;
        }

        $query = $this->reminders();

        if ($ignoreReminderId !== null) {
            $query->whereKeyNot($ignoreReminderId);
        }

        return $query->get(['id', 'created_at']);
    }

    public function normalizedPhone(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if (blank($digits)) {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) <= 10) {
            $digits = '225'.$digits;
        }

        return $digits;
    }

    public function whatsappUrl(): ?string
    {
        $phone = $this->normalizedPhone();

        if ($phone === null) {
            return null;
        }

        $date = $this->event->starts_at->translatedFormat('l j F Y').' à '.$this->event->starts_at->format('H\hi');
        $message = "Bonjour {$this->name}, petit rappel pour {$this->event->name} le {$date} à {$this->event->venue_name}.";

        if ($this->event->rsvp_deadline) {
            $message .= ' Merci de répondre avant le '.$this->event->rsvp_deadline->translatedFormat('j F Y').'.';
        }

        $message .= ' Confirmez votre présence : '.$this->invitationUrl();

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
