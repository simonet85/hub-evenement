<?php

namespace App\Http\Controllers;

use App\Enums\GuestbookStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestbookEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GuestbookController extends Controller
{
    public function store(Request $request, string $token): RedirectResponse
    {
        $guest = Guest::query()
            ->with('event')
            ->where('invitation_token', $token)
            ->firstOrFail();

        if (filled($request->input('website_url'))) {
            return $this->confirmed($guest);
        }

        $validated = $request->validate([
            'author_name' => ['required', 'string', 'max:80'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $guest->event->guestbookEntries()->create([
            'guest_id' => $guest->id,
            'author_name' => trim($validated['author_name']),
            'message' => trim($validated['message']),
            'status' => GuestbookStatus::Pending,
        ]);

        return $this->confirmed($guest);
    }

    public function index(Event $event): View
    {
        Gate::authorize('moderateGuestbook', $event);

        $entries = $event->guestbookEntries()
            ->with(['guest:id,name', 'moderator:id,name'])
            ->latest()
            ->get();

        return view('guestbook.index', [
            'event' => $event,
            'pending' => $this->withStatus($entries, GuestbookStatus::Pending),
            'approved' => $this->withStatus($entries, GuestbookStatus::Approved),
            'hidden' => $this->withStatus($entries, GuestbookStatus::Hidden),
            'rejected' => $this->withStatus($entries, GuestbookStatus::Rejected),
        ]);
    }

    public function approve(Request $request, Event $event, GuestbookEntry $entry): RedirectResponse
    {
        return $this->moderate($request, $event, $entry, GuestbookStatus::Approved, 'Message publié.');
    }

    public function hide(Request $request, Event $event, GuestbookEntry $entry): RedirectResponse
    {
        return $this->moderate($request, $event, $entry, GuestbookStatus::Hidden, 'Message masqué.');
    }

    public function reject(Request $request, Event $event, GuestbookEntry $entry): RedirectResponse
    {
        return $this->moderate($request, $event, $entry, GuestbookStatus::Rejected, 'Message rejeté.');
    }

    private function moderate(Request $request, Event $event, GuestbookEntry $entry, GuestbookStatus $status, string $message): RedirectResponse
    {
        Gate::authorize('moderateGuestbook', $event);
        abort_unless($entry->event_id === $event->id, 404);

        $entry->forceFill([
            'status' => $status,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ])->save();

        return redirect()
            ->route('guestbook.index', $event)
            ->with('status', $message);
    }

    private function confirmed(Guest $guest): RedirectResponse
    {
        return redirect()
            ->route('invitations.show', $guest->invitation_token)
            ->withFragment('livre-dor')
            ->with('status', 'Merci ! Votre message sera publié après validation par les organisateurs.');
    }

    /**
     * @param  Collection<int, GuestbookEntry>  $entries
     * @return Collection<int, GuestbookEntry>
     */
    private function withStatus(Collection $entries, GuestbookStatus $status): Collection
    {
        return $entries
            ->filter(fn (GuestbookEntry $entry) => $entry->status === $status)
            ->values();
    }
}
