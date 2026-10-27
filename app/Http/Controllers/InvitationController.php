<?php

namespace App\Http\Controllers;

use App\Enums\RsvpStatus;
use App\Mail\RsvpConfirmation;
use App\Models\Guest;
use App\Services\EntryQrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(string $token, EntryQrCode $qrCodes): View
    {
        $guest = $this->guest($token);

        return view('invitations.show', [
            'guest' => $guest,
            'event' => $guest->event,
            'qrCode' => $qrCodes->dataUri($guest),
        ]);
    }

    public function programme(string $token): View
    {
        $guest = $this->guest($token);
        $guest->event->load('programSteps');

        return view('invitations.programme', [
            'guest' => $guest,
            'event' => $guest->event,
        ]);
    }

    public function rsvp(Request $request, string $token): RedirectResponse
    {
        $guest = $this->guest($token);
        abort_unless($guest->canRespond(), 403);

        $event = $guest->event;

        $validated = $request->validate([
            'rsvp_status' => ['required', Rule::in([RsvpStatus::Attending->value, RsvpStatus::Declined->value])],
            'rsvp_seats' => [
                'required_if:rsvp_status,'.RsvpStatus::Attending->value,
                'nullable',
                'integer',
                'min:1',
                'max:'.$guest->allowed_seats,
            ],
            'attendee_names' => ['nullable', 'string', 'max:1000'],
            'dietary_notes' => ['nullable', 'string', 'max:500'],
            'rsvp_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $attending = $validated['rsvp_status'] === RsvpStatus::Attending->value;
        $seats = $attending ? (int) $validated['rsvp_seats'] : null;
        $names = $attending ? $this->cleanNames($validated['attendee_names'] ?? '') : [];

        if ($seats !== null && count($names) > $seats) {
            throw ValidationException::withMessages([
                'attendee_names' => 'Indiquez au plus '.$seats.' prénom(s) pour ce foyer.',
            ]);
        }

        $late = $event->rsvp_deadline !== null && $event->rsvp_deadline->lt(today());

        $guest->forceFill([
            'rsvp_status' => $validated['rsvp_status'],
            'rsvp_seats' => $seats,
            'attendee_names' => $names === [] ? null : implode("\n", $names),
            'dietary_notes' => $validated['dietary_notes'] ?? null,
            'rsvp_message' => $validated['rsvp_message'] ?? null,
            'rsvp_responded_at' => now(),
            'rsvp_is_late' => $late,
        ])->save();

        if ($guest->email !== null) {
            Mail::to($guest->email)->queue(new RsvpConfirmation($guest->loadMissing('event')));
        }

        $status = $attending
            ? 'Merci ! Votre présence pour '.$seats.' personne(s) est enregistrée.'
            : 'Merci, votre absence a bien été enregistrée.';

        if ($late) {
            $status .= ' Cette réponse a été transmise après la date limite.';
        }

        return redirect()
            ->route('invitations.show', $guest->invitation_token)
            ->with('status', $status);
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
    private function cleanNames(string $names): array
    {
        return collect(preg_split('/\R/', $names) ?: [])
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->values()
            ->all();
    }
}
