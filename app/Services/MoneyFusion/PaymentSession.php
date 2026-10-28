<?php

namespace App\Services\MoneyFusion;

final class PaymentSession
{
    public function __construct(
        public readonly string $token,
        public readonly string $url,
    ) {}
}
