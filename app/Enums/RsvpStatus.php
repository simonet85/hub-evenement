<?php

namespace App\Enums;

enum RsvpStatus: string
{
    case Pending = 'pending';
    case Attending = 'attending';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Attending => 'Présent',
            self::Declined => 'Absent',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Attending => 'success',
            self::Declined => 'danger',
        };
    }
}
