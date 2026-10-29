<?php

namespace App\Http\Controllers;

use App\Enums\RsvpStatus;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;

class DemoInvitationController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $token = config('hub.demo_invitation_token');
        abort_unless(is_string($token) && $token !== '', 404);

        $guest = Guest::query()
            ->where('invitation_token', $token)
            ->firstOrFail();

        $guest->forceFill([
            'rsvp_status' => RsvpStatus::Pending,
            'rsvp_seats' => null,
            'attendee_names' => null,
            'dietary_notes' => null,
            'rsvp_message' => null,
            'rsvp_responded_at' => null,
            'rsvp_is_late' => false,
            'checked_in_at' => null,
            'checked_in_by' => null,
        ])->save();

        return redirect()->route('invitations.show', $guest->invitation_token);
    }
}
