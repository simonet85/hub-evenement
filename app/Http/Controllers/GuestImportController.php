<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\GuestImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GuestImportController extends Controller
{
    public function store(Request $request, Event $event, GuestImporter $importer): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $report = $importer->import($event, $request->file('file')->getRealPath());

        $status = $report['imported'].' foyer(s) importé(s).';

        if ($report['errors'] !== []) {
            $status .= ' '.count($report['errors']).' ligne(s) ignorée(s).';
        }

        return back()
            ->with('status', $status)
            ->with('import_errors', $report['errors']);
    }
}
