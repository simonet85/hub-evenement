<?php

namespace App\Services;

use App\Enums\ContributionKind;
use App\Enums\ContributionStatus;
use App\Exceptions\PaymentProviderException;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\Guest;
use App\Models\User;
use App\Services\MoneyFusion\PaymentGateway;
use App\Services\MoneyFusion\PaymentResult;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Throwable;

class ContributionService
{
    public const MIN_AMOUNT = 500;

    public const MAX_CAGNOTTE_AMOUNT = 2_000_000;

    public const EXPIRATION_HOURS = 2;

    public function __construct(private PaymentGateway $gateway) {}

    public function contributeToCagnotte(Guest $guest, int $amount, string $phone, ?string $message, bool $anonymous): Contribution
    {
        if (! $guest->event->cagnotte_enabled) {
            throw ValidationException::withMessages([
                'amount' => 'La cagnotte de cet événement est fermée.',
            ]);
        }

        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_CAGNOTTE_AMOUNT) {
            throw ValidationException::withMessages([
                'amount' => 'Indiquez un montant entre '.Money::format(self::MIN_AMOUNT).' et '.Money::format(self::MAX_CAGNOTTE_AMOUNT).'.',
            ]);
        }

        $contribution = $guest->event->contributions()->create([
            'guest_id' => $guest->id,
            'gift_id' => null,
            'kind' => ContributionKind::Cagnotte,
            'amount' => $amount,
            'quantity' => null,
            'contributor_name' => $guest->name,
            'phone' => $phone,
            'message' => $message,
            'anonymous' => $anonymous,
            'status' => ContributionStatus::Pending,
            'expires_at' => now()->addHours(self::EXPIRATION_HOURS),
        ]);

        return $this->startPayment($contribution);
    }

    public function contributeToGift(
        Guest $guest,
        Gift $gift,
        string $mode,
        ?int $amount,
        ?int $quantity,
        string $phone,
        ?string $message,
        bool $anonymous,
    ): Contribution {
        $contribution = DB::transaction(function () use ($guest, $gift, $mode, $amount, $quantity, $phone, $message, $anonymous) {
            $locked = Gift::query()->whereKey($gift->id)->lockForUpdate()->firstOrFail();

            abort_unless($locked->event_id === $guest->event_id && $locked->is_active, 404);

            $available = $locked->availableAmount();

            if ($available <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Ce cadeau est déjà entièrement financé.',
                ]);
            }

            if ($mode === 'reserve') {
                $units = (int) $quantity;

                if ($units < 1 || $units > $locked->availableUnits()) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Choisissez entre 1 et '.$locked->availableUnits().' exemplaire(s) disponible(s).',
                    ]);
                }

                $amount = $locked->price * $units;
            } else {
                $amount = (int) $amount;
                $units = null;

                if ($amount < self::MIN_AMOUNT) {
                    throw ValidationException::withMessages([
                        'amount' => 'Indiquez au moins '.Money::format(self::MIN_AMOUNT).'.',
                    ]);
                }
            }

            if ($amount > $available) {
                throw ValidationException::withMessages([
                    'amount' => 'Il ne reste que '.Money::format($available).' à financer sur ce cadeau.',
                ]);
            }

            return $locked->event->contributions()->create([
                'guest_id' => $guest->id,
                'gift_id' => $locked->id,
                'kind' => ContributionKind::Gift,
                'amount' => $amount,
                'quantity' => $units,
                'contributor_name' => $guest->name,
                'phone' => $phone,
                'message' => $message,
                'anonymous' => $anonymous,
                'status' => ContributionStatus::Pending,
                'expires_at' => now()->addHours(self::EXPIRATION_HOURS),
            ]);
        });

        return $this->startPayment($contribution);
    }

    public function applyStatus(Contribution $contribution, PaymentResult $result): Contribution
    {
        return DB::transaction(function () use ($contribution, $result) {
            $locked = Contribution::query()->whereKey($contribution->id)->lockForUpdate()->firstOrFail();

            $normalized = match (strtolower($result->status)) {
                'paid' => ContributionStatus::Paid,
                'failed', 'cancelled' => ContributionStatus::Failed,
                default => null,
            };

            if ($normalized === null || ! $result->verified) {
                return $locked;
            }

            if (in_array($locked->status, [ContributionStatus::Paid, ContributionStatus::Refunded], true)) {
                return $locked;
            }

            if ($normalized === ContributionStatus::Paid && $result->amount !== null && $result->amount !== $locked->amount) {
                Log::warning('MoneyFusion: montant confirmé différent de la contribution', [
                    'reference' => $locked->reference,
                    'expected' => $locked->amount,
                    'received' => $result->amount,
                ]);

                return $locked;
            }

            if ($normalized === ContributionStatus::Failed && $locked->status !== ContributionStatus::Pending) {
                return $locked;
            }

            $locked->forceFill([
                'status' => $normalized,
                'paid_at' => $normalized === ContributionStatus::Paid ? now() : null,
                'failed_at' => $normalized === ContributionStatus::Failed ? now() : null,
                'payload' => $result->raw === [] ? null : $result->raw,
            ])->save();

            return $locked;
        });
    }

    public function reconcile(Contribution $contribution, User $user, string $action, ?string $note): Contribution
    {
        $note = $note !== null ? trim($note) : null;
        $note = $note === '' ? null : $note;

        if ($action === 'paid') {
            if (! $contribution->status->canBeMarkedPaid()) {
                throw ValidationException::withMessages(['action' => 'Ce paiement ne peut plus être encaissé manuellement.']);
            }

            $contribution->forceFill([
                'status' => ContributionStatus::Paid,
                'paid_at' => $contribution->paid_at ?? now(),
                'reconciled_by' => $user->id,
                'reconciled_at' => now(),
                'reconciliation_note' => $note,
            ])->save();

            return $contribution;
        }

        if ($action === 'failed') {
            if ($contribution->status !== ContributionStatus::Pending) {
                throw ValidationException::withMessages(['action' => 'Seul un paiement en attente peut être marqué en échec.']);
            }

            $contribution->forceFill([
                'status' => ContributionStatus::Failed,
                'failed_at' => now(),
                'reconciled_by' => $user->id,
                'reconciled_at' => now(),
                'reconciliation_note' => $note,
            ])->save();

            return $contribution;
        }

        if ($action === 'refunded') {
            if (! $contribution->status->canBeRefunded()) {
                throw ValidationException::withMessages(['action' => 'Seul un paiement confirmé peut être remboursé.']);
            }

            if ($note === null) {
                throw ValidationException::withMessages(['note' => 'Indiquez la référence ou le motif du remboursement.']);
            }

            $contribution->forceFill([
                'status' => ContributionStatus::Refunded,
                'refunded_at' => now(),
                'refund_note' => $note,
                'refunded_by' => $user->id,
            ])->save();

            return $contribution;
        }

        throw ValidationException::withMessages(['action' => 'Action de rapprochement inconnue.']);
    }

    public function expirePending(): int
    {
        return Contribution::query()
            ->where('status', ContributionStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => ContributionStatus::Expired,
                'updated_at' => now(),
            ]);
    }

    private function startPayment(Contribution $contribution): Contribution
    {
        $returnUrl = URL::temporarySignedRoute(
            'payments.return',
            now()->addHours((int) config('services.moneyfusion.return_ttl_hours', 24)),
            ['contribution' => $contribution],
        );

        $webhookUrl = URL::temporarySignedRoute(
            'payments.webhook',
            now()->addDays((int) config('services.moneyfusion.webhook_ttl_days', 7)),
            ['contribution' => $contribution],
        );

        try {
            $session = $this->gateway->create($contribution, $returnUrl, $webhookUrl);
        } catch (Throwable $exception) {
            $contribution->forceFill([
                'status' => ContributionStatus::Failed,
                'failed_at' => now(),
                'payload' => ['error' => mb_substr($exception->getMessage(), 0, 500)],
            ])->save();

            report($exception);

            throw new PaymentProviderException('Le paiement n’a pas pu être initialisé. Réessayez dans un instant.', previous: $exception);
        }

        $contribution->forceFill([
            'provider_token' => $session->token,
            'provider_url' => $session->url,
        ])->save();

        return $contribution;
    }
}
