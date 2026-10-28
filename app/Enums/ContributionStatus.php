<?php

namespace App\Enums;

enum ContributionStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Paid => 'Payé',
            self::Failed => 'Échoué',
            self::Expired => 'Expiré',
            self::Refunded => 'Remboursé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Expired => 'neutral',
            self::Refunded => 'neutral',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function canBeMarkedPaid(): bool
    {
        return in_array($this, [self::Pending, self::Failed, self::Expired], true);
    }

    public function canBeRefunded(): bool
    {
        return $this === self::Paid;
    }
}
