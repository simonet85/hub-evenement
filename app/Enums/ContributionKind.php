<?php

namespace App\Enums;

enum ContributionKind: string
{
    case Gift = 'gift';
    case Cagnotte = 'cagnotte';

    public function label(): string
    {
        return match ($this) {
            self::Gift => 'Cadeau',
            self::Cagnotte => 'Cagnotte',
        };
    }
}
