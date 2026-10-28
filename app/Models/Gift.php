<?php

namespace App\Models;

use App\Enums\ContributionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'is_active',
    ];

    protected $attributes = [
        'quantity' => 1,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function totalPrice(): int
    {
        return $this->price * $this->quantity;
    }

    public function paidTotal(): int
    {
        return (int) $this->contributions()
            ->where('status', ContributionStatus::Paid)
            ->sum('amount');
    }

    public function pendingTotal(): int
    {
        return (int) $this->contributions()
            ->where('status', ContributionStatus::Pending)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->sum('amount');
    }

    public function availableAmount(): int
    {
        return max(0, $this->totalPrice() - $this->paidTotal() - $this->pendingTotal());
    }

    public function availableUnits(): int
    {
        return intdiv($this->availableAmount(), max(1, $this->price));
    }

    public function progressPercent(): float
    {
        $total = $this->totalPrice();

        if ($total <= 0) {
            return 0.0;
        }

        return round(min($this->paidTotal(), $total) / $total * 100, 1);
    }

    public function isFunded(): bool
    {
        return $this->paidTotal() >= $this->totalPrice();
    }
}
