<?php

namespace App\Enums;

enum GuestbookStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Hidden = 'hidden';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Approved => 'Publié',
            self::Hidden => 'Masqué',
            self::Rejected => 'Rejeté',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Hidden => 'warning',
            self::Rejected => 'danger',
        };
    }
}
