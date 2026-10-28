<?php

namespace App\Services\MoneyFusion;

use App\Exceptions\PaymentProviderException;
use App\Models\Contribution;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;
use Throwable;

class PackageMoneyFusionGateway implements PaymentGateway
{
    public function create(Contribution $contribution, string $returnUrl, string $webhookUrl): PaymentSession
    {
        try {
            $result = $this->service()->createPayment([
                'total_price' => $contribution->amount,
                'articles' => [[
                    'name' => $contribution->label(),
                    'price' => $contribution->amount,
                    'quantity' => 1,
                ]],
                'nom_client' => $contribution->contributor_name,
                'numero_send' => $contribution->phone,
                'order_id' => $contribution->id,
                'return_url' => $returnUrl,
                'webhook_url' => $webhookUrl,
            ]);
        } catch (Throwable $exception) {
            throw new PaymentProviderException('MoneyFusion a refusé la création du paiement.', previous: $exception);
        }

        return new PaymentSession((string) $result['token'], (string) $result['url']);
    }

    public function status(Contribution $contribution): PaymentResult
    {
        if ($contribution->provider_token === null) {
            throw new PaymentProviderException('Aucun token de paiement pour cette contribution.');
        }

        try {
            $result = $this->service()->checkPaymentStatus($contribution->provider_token);
        } catch (Throwable $exception) {
            throw new PaymentProviderException('Vérification MoneyFusion indisponible.', previous: $exception);
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $amount = $data['Montant'] ?? $data['montant'] ?? null;

        return new PaymentResult(
            status: MoneyFusionService::normalizeStatus($data['statut'] ?? null),
            verified: (bool) ($result['verified'] ?? false),
            amount: $amount !== null ? (int) $amount : null,
            raw: $result,
        );
    }

    private function service(): MoneyFusionService
    {
        return app(MoneyFusionService::class);
    }
}
