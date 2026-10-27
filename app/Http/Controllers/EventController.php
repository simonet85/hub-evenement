<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $events = Event::query()
            ->visibleTo($request->user())
            ->withCount('guests')
            ->orderBy('starts_at')
            ->get();

        return view('dashboard', ['events' => $events]);
    }

    public function create(): View
    {
        Gate::authorize('create', Event::class);

        return view('events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $event = $request->user()->ownedEvents()->create($this->validated($request));

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Événement créé. Vous pouvez maintenant ajouter vos foyers.');
    }

    public function show(Event $event): View
    {
        Gate::authorize('view', $event);

        $event->load([
            'guests' => fn ($query) => $query->orderBy('name'),
            'roles.user',
        ]);

        return view('events.show', ['event' => $event]);
    }

    public function edit(Event $event): View
    {
        Gate::authorize('update', $event);

        return view('events.edit', ['event' => $event]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $event->update($this->validated($request));

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Événement mis à jour.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::enum(EventType::class)],
            'starts_at' => ['required', 'date'],
            'venue_name' => ['required', 'string', 'max:160'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'rsvp_deadline' => ['nullable', 'date', 'before_or_equal:starts_at'],
        ]);
    }
}
