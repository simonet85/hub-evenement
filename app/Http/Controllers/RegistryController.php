<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentProviderException;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\Guest;
use App\Services\ContributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistryController extends Controller
{
    public function show(string $token): View
    {
        $guest = $this->guest($token);
        $event = $guest->event;

        return view('registry.show', [
            'guest' => $guest,
            'event' => $event,
            'gifts' => $event->gifts()->active()->orderBy('id')->get(),
            'collected' => (int) $event->contributions()->paid()->sum('amount'),
            'recent' => $event->contributions()->paid()->with('gift')->latest('paid_at')->take(10)->get(),
        ]);
    }

    public function storeCagnotte(Request $request, string $token, ContributionService $contributions): RedirectResponse
    {
        $guest = $this->guest($token);
        abort_unless($guest->event->cagnotte_enabled, 404);

        $validated = $request->validate([
            'amount' => ['nullable', 'integer', 'min:'.ContributionService::MIN_AMOUNT, 'max:'.ContributionService::MAX_CAGNOTTE_AMOUNT],
            'custom_amount' => ['nullable', 'integer', 'min:'.ContributionService::MIN_AMOUNT, 'max:'.ContributionService::MAX_CAGNOTTE_AMOUNT],
            'phone' => $this->phoneRules(),
            'message' => ['nullable', 'string', 'max:300'],
            'anonymous' => ['nullable', 'boolean'],
        ]);

        $amount = $validated['custom_amount'] ?? $validated['amount'] ?? null;

        if ($amount === null) {
            throw ValidationException::withMessages([
                'amount' => 'Choisissez un montant suggéré ou saisissez un montant libre.',
            ]);
        }

        try {
            $contribution = $contributions->contributeToCagnotte(
                $guest,
                (int) $amount,
                trim($validated['phone']),
                $this->message($validated),
                $request->boolean('anonymous'),
            );
        } catch (PaymentProviderException $exception) {
            return $this->paymentFailed($guest, 'cagnotte', $exception);
        }

        return $this->redirectToPayment($contribution);
    }

    public function storeGift(Request $request, string $token, Gift $gift, ContributionService $contributions): RedirectResponse
    {
        $guest = $this->guest($token);
        abort_unless($gift->event_id === $guest->event_id && $gift->is_active, 404);

        $validated = $request->validate([
            'mode' => ['required', Rule::in(['reserve', 'contribute'])],
            'quantity' => ['required_if:mode,reserve', 'nullable', 'integer', 'min:1', 'max:99'],
            'amount' => ['required_if:mode,contribute', 'nullable', 'integer', 'min:1'],
            'phone' => $this->phoneRules(),
            'message' => ['nullable', 'string', 'max:300'],
            'anonymous' => ['nullable', 'boolean'],
        ]);

        try {
            $contribution = $contributions->contributeToGift(
                $guest,
                $gift,
                $validated['mode'],
                isset($validated['amount']) ? (int) $validated['amount'] : null,
                isset($validated['quantity']) ? (int) $validated['quantity'] : null,
                trim($validated['phone']),
                $this->message($validated),
                $request->boolean('anonymous'),
            );
        } catch (PaymentProviderException $exception) {
            return $this->paymentFailed($guest, 'cadeau-'.$gift->id, $exception);
        }

        return $this->redirectToPayment($contribution);
    }

    private function guest(string $token): Guest
    {
        return Guest::query()
            ->with('event')
            ->where('invitation_token', $token)
            ->firstOrFail();
    }

    /**
     * @return list<string>
     */
    private function phoneRules(): array
    {
        return ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s().\-]{6,18}$/'];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function message(array $validated): ?string
    {
        $message = trim((string) ($validated['message'] ?? ''));

        return $message === '' ? null : $message;
    }

    private function redirectToPayment(Contribution $contribution): RedirectResponse
    {
        if ($contribution->provider_url === null) {
            return redirect()
                ->route('invitations.registry', $contribution->guest->invitation_token)
                ->withErrors(['payment' => 'Le paiement n’a pas pu être initialisé. Réessayez dans un instant.']);
        }

        return redirect()->away($contribution->provider_url);
    }

    private function paymentFailed(Guest $guest, string $fragment, PaymentProviderException $exception): RedirectResponse
    {
        return redirect()
            ->route('invitations.registry', $guest->invitation_token)
            ->withFragment($fragment)
            ->withErrors(['payment' => $exception->getMessage()]);
    }
}
