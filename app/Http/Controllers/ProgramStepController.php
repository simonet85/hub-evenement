<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ProgramStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProgramStepController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageProgram', $event);

        $position = (int) $event->programSteps()->reorder()->max('position') + 1;

        $event->programSteps()->create($this->validated($request) + ['position' => $position]);

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Étape ajoutée au programme.');
    }

    public function update(Request $request, Event $event, ProgramStep $step): RedirectResponse
    {
        Gate::authorize('manageProgram', $event);
        abort_unless($step->event_id === $event->id, 404);

        $step->update($this->validated($request));

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Étape mise à jour.');
    }

    public function destroy(Event $event, ProgramStep $step): RedirectResponse
    {
        Gate::authorize('manageProgram', $event);
        abort_unless($step->event_id === $event->id, 404);

        $step->delete();

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Étape supprimée.');
    }

    public function move(Request $request, Event $event, ProgramStep $step): RedirectResponse
    {
        Gate::authorize('manageProgram', $event);
        abort_unless($step->event_id === $event->id, 404);

        $direction = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction'];

        DB::transaction(function () use ($event, $step, $direction) {
            $ids = ProgramStep::query()
                ->where('event_id', $event->id)
                ->orderBy('position')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $index = array_search($step->id, $ids, true);
            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! isset($ids[$target])) {
                return;
            }

            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

            foreach ($ids as $position => $id) {
                ProgramStep::query()->whereKey($id)->update(['position' => $position]);
            }
        });

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Programme réordonné.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'title' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
