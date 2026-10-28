<?php

namespace App\Services\MoneyFusion;

use App\Models\Contribution;

interface PaymentGateway
{
    public function create(Contribution $contribution, string $returnUrl, string $webhookUrl): PaymentSession;

    public function status(Contribution $contribution): PaymentResult;
}
