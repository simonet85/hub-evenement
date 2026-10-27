<?php

namespace App\Http\Controllers;

use App\Jobs\SendReminderEmail;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReminderController extends Controller
{
    public function index(Event $event): View
    {
        Gate::authorize('manageGuests', $event);

        $guests = $event->guests()
            ->with(['reminders' => fn ($query) => $query->orderBy('created_at')])
            ->orderBy('name')
            ->get();

        $pending = $guests->filter(fn (Guest $guest) => ! $guest->hasResponded())->values();

        return view('reminders.index', [
            'event' => $event,
            'pending' => $pending,
            'remindersSent' => $event->reminders()->whereNotNull('sent_at')->count(),
            'remindersQueued' => $event->reminders()->whereNull('sent_at')->count(),
            'optedOut' => $guests->filter(fn (Guest $guest) => $guest->hasOptedOutOfReminders())->count(),
            'eligible' => $pending->filter(fn (Guest $guest) => $guest->canReceiveReminder())->count(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);

        $data = $request->validate([
            'all' => ['nullable', 'boolean'],
            'guest_ids' => ['required_without:all', 'array'],
            'guest_ids.*' => ['integer'],
        ]);

        $ids = $request->boolean('all')
            ? $event->guests()->pluck('id')->all()
            : array_unique($data['guest_ids'] ?? []);

        $queued = 0;
        $ignored = 0;

        foreach ($ids as $id) {
            $reminder = DB::transaction(function () use ($event, $id, $request) {
                $guest = Guest::whereKey($id)->lockForUpdate()->first();

                if (! $guest || $guest->event_id !== $event->id || ! $guest->canReceiveReminder()) {
                    return null;
                }

                return $guest->reminders()->create([
                    'event_id' => $event->id,
                    'user_id' => $request->user()->id,
                ]);
            });

            if ($reminder === null) {
                $ignored++;

                continue;
            }

            // Un seul worker traite la file : les envois sont sérialisés sans délai artificiel.
            SendReminderEmail::dispatch($reminder);
            $queued++;
        }

        if ($queued === 0) {
            throw ValidationException::withMessages([
                'guest_ids' => 'Aucun foyer sélectionné ne peut recevoir de relance.',
            ]);
        }

        $message = $queued === 1 ? '1 relance mise en file.' : "{$queued} relances mises en file.";

        if ($ignored > 0) {
            $message .= $ignored === 1 ? ' 1 foyer ignoré.' : " {$ignored} foyers ignorés.";
        }

        return redirect()
            ->route('reminders.index', $event)
            ->with('status', $message);
    }

    public function unsubscribeShow(Guest $guest): View
    {
        $guest->loadMissing('event');

        return view('reminders.unsubscribe', [
            'guest' => $guest,
            'event' => $guest->event,
            'unsubscribeAction' => URL::signedRoute('reminders.unsubscribe', ['guest' => $guest]),
        ]);
    }

    public function unsubscribe(Guest $guest): RedirectResponse
    {
        if (! $guest->hasOptedOutOfReminders()) {
            $guest->forceFill(['reminders_opted_out_at' => now()])->save();
        }

        return redirect(URL::signedRoute('reminders.unsubscribe.show', ['guest' => $guest]))
            ->with('status', 'Vous ne recevrez plus de relances pour cet événement.');
    }
}
