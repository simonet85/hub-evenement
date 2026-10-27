<?php

namespace App\Enums;

enum EventType: string
{
    case Mariage = 'mariage';
    case Bapteme = 'bapteme';
    case Anniversaire = 'anniversaire';
    case Fete = 'fete';

    public function label(): string
    {
        return match ($this) {
            self::Mariage => 'Mariage',
            self::Bapteme => 'Baptême',
            self::Anniversaire => 'Anniversaire',
            self::Fete => 'Fête',
        };
    }

    public function accentClass(): string
    {
        return 'ev-'.$this->value;
    }
}
