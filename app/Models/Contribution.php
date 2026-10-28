<?php

namespace App\Models;

use App\Enums\ContributionKind;
use App\Enums\ContributionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Contribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'guest_id',
        'gift_id',
        'kind',
        'amount',
        'quantity',
        'contributor_name',
        'phone',
        'message',
        'anonymous',
        'status',
        'provider',
        'provider_token',
        'provider_url',
        'expires_at',
        'paid_at',
        'failed_at',
        'refunded_at',
        'refund_note',
        'refunded_by',
        'reconciled_by',
        'reconciled_at',
        'reconciliation_note',
        'payload',
    ];

    protected $attributes = [
        'anonymous' => false,
        'status' => 'pending',
        'provider' => 'moneyfusion',
    ];

    protected static function booted(): void
    {
        static::creating(function (Contribution $contribution) {
            $contribution->reference ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'kind' => ContributionKind::class,
            'status' => ContributionStatus::class,
            'amount' => 'integer',
            'quantity' => 'integer',
            'anonymous' => 'boolean',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }

    public function refunder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', ContributionStatus::Paid);
    }

    public function scopePendingActive(Builder $query): Builder
    {
        return $query->where('status', ContributionStatus::Pending)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeRefunded(Builder $query): Builder
    {
        return $query->where('status', ContributionStatus::Refunded);
    }

    public function isCagnotte(): bool
    {
        return $this->kind === ContributionKind::Cagnotte;
    }

    public function label(): string
    {
        if ($this->isCagnotte()) {
            return 'Cagnotte';
        }

        return $this->gift?->name ?? 'Cadeau supprimé';
    }

    public function displayName(): string
    {
        return $this->anonymous ? 'Invité anonyme' : $this->contributor_name;
    }
}
