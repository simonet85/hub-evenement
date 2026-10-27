<?php

namespace App\Models;

use App\Enums\GuestbookStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestbookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'guest_id',
        'author_name',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => GuestbookStatus::class,
            'moderated_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', GuestbookStatus::Approved);
    }
}
