<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GuestController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);

        $event->guests()->create($this->validated($request));

        return back()->with('status', 'Foyer ajouté.');
    }

    public function destroy(Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);

        abort_unless($guest->event_id === $event->id, 404);

        $guest->delete();

        return back()->with('status', 'Foyer supprimé.');
    }

    public function regenerateInvitation(Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);

        abort_unless($guest->event_id === $event->id, 404);

        $guest->forceFill([
            'invitation_token' => Guest::generateInvitationToken(),
        ])->save();

        return back()->with('status', "Nouveau lien généré. L'ancien ne fonctionne plus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'allowed_seats' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
    }
}
