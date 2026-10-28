<?php

namespace App\Services\MoneyFusion;

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use Illuminate\Support\Str;

class FakeMoneyFusionGateway implements PaymentGateway
{
    public function create(Contribution $contribution, string $returnUrl, string $webhookUrl): PaymentSession
    {
        $token = 'sim_'.Str::random(32);

        return new PaymentSession($token, route('payments.simulator', $token));
    }

    public function status(Contribution $contribution): PaymentResult
    {
        $status = match ($contribution->status) {
            ContributionStatus::Paid, ContributionStatus::Refunded => 'paid',
            ContributionStatus::Failed => 'failed',
            default => 'pending',
        };

        return new PaymentResult(
            status: $status,
            verified: true,
            amount: $contribution->amount,
            raw: ['simulated' => true],
        );
    }
}
