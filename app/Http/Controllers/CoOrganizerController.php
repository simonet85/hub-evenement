<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CoOrganizerController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageCoOrganizers', $event);

        $request->validate([
            'co_email' => ['required', 'string', 'email'],
        ]);

        $user = User::query()
            ->where('email', mb_strtolower($request->string('co_email')->value()))
            ->first();

        if ($user === null) {
            return back()->withInput()->withErrors([
                'co_email' => 'Aucun compte ne correspond à cette adresse.',
            ]);
        }

        if ($user->id === $event->user_id) {
            return back()->withInput()->withErrors([
                'co_email' => 'Vous êtes déjà propriétaire de cet événement.',
            ]);
        }

        if ($event->roles()->where('user_id', $user->id)->exists()) {
            return back()->withInput()->withErrors([
                'co_email' => 'Cette personne est déjà co-organisatrice.',
            ]);
        }

        $event->roles()->create([
            'user_id' => $user->id,
            'role' => EventRole::ROLE_CO_ORGANIZER,
        ]);

        return back()->with('status', 'Co-organisateur ajouté.');
    }

    public function destroy(Event $event, User $user): RedirectResponse
    {
        Gate::authorize('manageCoOrganizers', $event);

        $event->roles()->where('user_id', $user->id)->delete();

        return back()->with('status', 'Co-organisateur retiré.');
    }
}
