<?php

namespace App\Http\Controllers;

use App\Enums\ContributionStatus;
use App\Models\Contribution;
use App\Services\ContributionService;
use App\Services\MoneyFusion\PaymentGateway;
use App\Services\MoneyFusion\PaymentResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Throwable;

class PaymentController extends Controller
{
    public function show(Contribution $contribution, ContributionService $service, PaymentGateway $gateway): View
    {
        if ($contribution->status === ContributionStatus::Pending && $contribution->provider_token !== null) {
            try {
                $result = $gateway->status($contribution);

                if ($result->verified) {
                    $contribution = $service->applyStatus($contribution, $result);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $contribution = $contribution->fresh()->load(['event', 'gift', 'guest']);

        return view('payments.return', ['contribution' => $contribution]);
    }

    public function webhook(Contribution $contribution, ContributionService $service, PaymentGateway $gateway): JsonResponse
    {
        if ($contribution->provider_token === null) {
            return response()->json(['status' => 'ignored'], 422);
        }

        if (in_array($contribution->status, [ContributionStatus::Paid, ContributionStatus::Refunded], true)) {
            return response()->json(['status' => 'already_processed']);
        }

        try {
            $result = $gateway->status($contribution);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['status' => 'verification_unavailable'], 503);
        }

        if (! $result->verified) {
            return response()->json(['status' => 'unverified'], 503);
        }

        $contribution = $service->applyStatus($contribution, $result);

        return response()->json(['status' => $contribution->status->value]);
    }

    public function simulator(string $token): View
    {
        $this->ensureSimulatorEnabled();

        $contribution = Contribution::query()->where('provider_token', $token)->firstOrFail();

        return view('payments.simulator', ['contribution' => $contribution]);
    }

    public function simulate(Request $request, string $token, ContributionService $service): RedirectResponse
    {
        $this->ensureSimulatorEnabled();

        $validated = $request->validate([
            'action' => ['required', 'in:paid,failed'],
        ]);

        $contribution = Contribution::query()->where('provider_token', $token)->firstOrFail();

        $service->applyStatus($contribution, new PaymentResult(
            status: $validated['action'],
            verified: true,
            amount: $contribution->amount,
            raw: ['simulator' => true, 'action' => $validated['action']],
        ));

        return redirect()->to(URL::temporarySignedRoute(
            'payments.return',
            now()->addHours((int) config('services.moneyfusion.return_ttl_hours', 24)),
            ['contribution' => $contribution],
        ));
    }

    private function ensureSimulatorEnabled(): void
    {
        abort_unless(
            config('services.moneyfusion.driver') === 'fake' && ! app()->isProduction(),
            404,
        );
    }
}
