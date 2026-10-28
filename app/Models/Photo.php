<?php

namespace App\Models;

use App\Enums\PhotoStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'uploaded_by',
        'original_name',
        'path',
        'thumb_path',
        'web_path',
        'mime',
        'size',
        'width',
        'height',
        'caption',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => PhotoStatus::class,
            'moderated_at' => 'datetime',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', PhotoStatus::Approved);
    }

    public function isApproved(): bool
    {
        return $this->status === PhotoStatus::Approved;
    }

    public function derivativePath(string $variant): ?string
    {
        return match ($variant) {
            'thumb' => $this->thumb_path,
            'web' => $this->web_path,
            'original' => $this->path,
            default => null,
        };
    }

    public function hasDerivatives(): bool
    {
        return $this->thumb_path !== null && $this->web_path !== null;
    }
}
