<?php

namespace App\Enums;

enum PhotoStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Approved => 'Publiée',
            self::Hidden => 'Masquée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Hidden => 'warning',
        };
    }
}
