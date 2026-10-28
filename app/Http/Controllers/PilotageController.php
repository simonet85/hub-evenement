<?php

namespace App\Http\Controllers;

use App\Enums\GuestbookStatus;
use App\Enums\PhotoStatus;
use App\Models\Event;
use App\Services\EventMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PilotageController extends Controller
{
    public function show(Event $event): View
    {
        Gate::authorize('view', $event);

        $event->load([
            'guests' => fn ($query) => $query->with('checkedInBy')->orderBy('name'),
        ]);

        $metrics = new EventMetrics($event);

        return view('pilotage.show', [
            'event' => $event,
            'summary' => $metrics->summary(),
            'evolution' => $metrics->evolution(14),
            'dietary' => $metrics->dietary(),
            'queues' => [
                'photos' => $event->photos()->where('status', PhotoStatus::Pending)->count(),
                'guestbook' => $event->guestbookEntries()->where('status', GuestbookStatus::Pending)->count(),
                'contributions' => $event->contributions()->pendingActive()->count(),
            ],
        ]);
    }

    public function catererLink(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('view', $event);

        $validated = $request->validate([
            'days' => ['required', 'integer', Rule::in([1, 7, 30])],
        ]);

        $expiresAt = now()->addDays((int) $validated['days']);

        $url = URL::temporarySignedRoute('caterer.show', $expiresAt, ['event' => $event]);

        return redirect()
            ->route('pilotage.show', $event)
            ->with('status', 'Lien traiteur généré.')
            ->with('caterer_link', $url)
            ->with('caterer_expires_at', Carbon::instance($expiresAt));
    }
}
