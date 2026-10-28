<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Event;
use App\Models\Gift;
use App\Services\ContributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GiftController extends Controller
{
    public function index(Event $event): View
    {
        Gate::authorize('manageRegistry', $event);

        return view('registry.index', [
            'event' => $event,
            'gifts' => $event->gifts()->orderBy('id')->get(),
            'contributions' => $event->contributions()
                ->with(['guest:id,name', 'gift:id,name', 'refunder:id,name', 'reconciler:id,name'])
                ->latest()
                ->get(),
            'collected' => (int) $event->contributions()->paid()->sum('amount'),
            'pending' => (int) $event->contributions()->pendingActive()->sum('amount'),
            'refunded' => (int) $event->contributions()->refunded()->sum('amount'),
            'cagnottePaid' => (int) $event->contributions()->paid()->whereNull('gift_id')->sum('amount'),
        ]);
    }

    public function storeGift(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageRegistry', $event);

        $event->gifts()->create($this->validatedGift($request) + [
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()
            ->route('registry.index', $event)
            ->with('status', 'Cadeau ajouté.');
    }

    public function updateGift(Request $request, Event $event, Gift $gift): RedirectResponse
    {
        Gate::authorize('manageRegistry', $event);
        abort_unless($gift->event_id === $event->id, 404);

        $gift->update($this->validatedGift($request) + [
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('registry.index', $event)
            ->with('status', 'Cadeau mis à jour.');
    }

    public function updateCagnotte(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageRegistry', $event);

        $validated = $request->validate([
            'goal' => ['nullable', 'integer', 'min:1000', 'max:100000000'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $message = trim((string) ($validated['message'] ?? ''));

        $event->forceFill([
            'cagnotte_enabled' => $request->boolean('enabled'),
            'cagnotte_goal' => $validated['goal'] ?? null,
            'cagnotte_message' => $message === '' ? null : $message,
        ])->save();

        return redirect()
            ->route('registry.index', $event)
            ->with('status', $request->boolean('enabled') ? 'Cagnotte mise à jour.' : 'Cagnotte désactivée.');
    }

    public function reconcile(Request $request, Event $event, Contribution $contribution, ContributionService $service): RedirectResponse
    {
        Gate::authorize('manageRegistry', $event);
        abort_unless($contribution->event_id === $event->id, 404);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['paid', 'failed', 'refunded'])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $service->reconcile(
                $contribution,
                $request->user(),
                $validated['action'],
                $validated['note'] ?? null,
            );
        } catch (ValidationException $exception) {
            return redirect()
                ->route('registry.index', $event)
                ->withFragment('journal')
                ->withErrors($exception->errors());
        }

        $message = match ($validated['action']) {
            'paid' => 'Paiement encaissé et journalisé.',
            'failed' => 'Paiement marqué en échec.',
            default => 'Paiement remboursé.',
        };

        return redirect()
            ->route('registry.index', $event)
            ->withFragment('journal')
            ->with('status', $message);
    }

    /**
     * @return array{name: string, description: string|null, price: int, quantity: int}
     */
    private function validatedGift(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:500', 'max:10000000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $description = trim((string) ($validated['description'] ?? ''));

        return [
            'name' => trim($validated['name']),
            'description' => $description === '' ? null : $description,
            'price' => (int) $validated['price'],
            'quantity' => (int) $validated['quantity'],
        ];
    }
}
