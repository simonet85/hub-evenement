<?php

namespace App\Services\MoneyFusion;

final class PaymentResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $status,
        public readonly bool $verified,
        public readonly ?int $amount,
        public readonly array $raw = [],
    ) {}
}
