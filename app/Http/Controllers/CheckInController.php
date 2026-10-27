<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Services\CheckInService;
use App\Services\EntryQrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CheckInController extends Controller
{
    public function __construct(
        private CheckInService $checkIns,
        private EntryQrCode $qrCodes,
    ) {}

    public function scan(Event $event): View
    {
        Gate::authorize('manageGuests', $event);

        return view('check-in.scan', [
            'event' => $event,
            'counters' => $this->checkIns->counters($event),
        ]);
    }

    public function record(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('manageGuests', $event);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:2048'],
            'confirm' => ['sometimes', 'boolean'],
        ]);

        $guest = $this->checkIns->findByToken($this->tokenFrom($validated['code']));

        if ($guest === null || $guest->event_id !== $event->id) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Code inconnu pour cet événement.',
            ], 404);
        }

        $status = $this->checkIns->checkIn($guest, $request->user(), $request->boolean('confirm'));
        $guest->refresh()->load(['event', 'checkedInBy']);

        return response()->json([
            'status' => $status,
            'message' => $this->messageFor($status, $guest),
            'guest' => $this->checkIns->summary($guest),
            'counters' => $this->checkIns->counters($event),
        ]);
    }

    public function undo(Event $event, Guest $guest): JsonResponse
    {
        Gate::authorize('manageGuests', $event);

        abort_unless($guest->event_id === $event->id, 404);

        $this->checkIns->undo($guest);
        $guest->refresh()->load(['event', 'checkedInBy']);

        return response()->json([
            'status' => 'undone',
            'message' => 'Pointage annulé.',
            'guest' => $this->checkIns->summary($guest),
            'counters' => $this->checkIns->counters($event),
        ]);
    }

    public function qr(Event $event, Guest $guest): Response
    {
        Gate::authorize('manageGuests', $event);

        abort_unless($guest->event_id === $event->id, 404);

        return response($this->qrCodes->svg($guest), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="qr-entree-foyer-'.$guest->id.'.svg"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function show(string $entryToken): View
    {
        $guest = $this->guest($entryToken);

        Gate::authorize('manageGuests', $guest->event);

        return view('check-in.show', [
            'event' => $guest->event,
            'guest' => $guest,
            'counters' => $this->checkIns->counters($guest->event),
        ]);
    }

    public function store(Request $request, string $entryToken): RedirectResponse
    {
        $guest = $this->guest($entryToken);

        Gate::authorize('manageGuests', $guest->event);

        $status = $this->checkIns->checkIn($guest, $request->user(), $request->boolean('confirm'));
        $guest->refresh()->load(['event', 'checkedInBy']);

        return back()->with(
            $status === CheckInService::REQUIRES_CONFIRMATION ? 'error' : 'status',
            $this->messageFor($status, $guest),
        );
    }

    public function undoForm(string $entryToken): RedirectResponse
    {
        $guest = $this->guest($entryToken);

        Gate::authorize('manageGuests', $guest->event);

        $this->checkIns->undo($guest);

        return back()->with('status', 'Pointage annulé pour '.$guest->name.'.');
    }

    private function guest(string $entryToken): Guest
    {
        $guest = $this->checkIns->findByToken($entryToken);

        abort_if($guest === null, 404);

        return $guest;
    }

    private function tokenFrom(string $code): string
    {
        $path = parse_url(trim($code), PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : trim($code);

        $segments = array_values(array_filter(explode('/', $path), fn (string $segment) => $segment !== ''));
        $token = (string) end($segments);

        return preg_replace('/[^A-Za-z0-9]/', '', $token) ?? '';
    }

    private function messageFor(string $status, Guest $guest): string
    {
        return match ($status) {
            CheckInService::CHECKED_IN => 'Entrée enregistrée pour '.$guest->name.'.',
            CheckInService::ALREADY_CHECKED_IN => 'Déjà pointé : '.$guest->name
                .' depuis le '.$guest->checked_in_at->translatedFormat('j F Y \à H\hi').'.',
            CheckInService::REQUIRES_CONFIRMATION => 'Ce foyer est « '.$guest->rsvp_status->label()
                .' ». Confirmez pour pointer malgré tout.',
            default => 'Code invalide.',
        };
    }
}
