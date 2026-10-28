<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\Exports\EventExporter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ExportController extends Controller
{
    public function guestsCsv(Event $event): Response
    {
        Gate::authorize('view', $event);

        $exporter = new EventExporter($event);

        return $this->download(
            $exporter->guestCsv(),
            'text/csv; charset=UTF-8',
            $exporter->guestFilename('csv')
        );
    }

    public function guestsXlsx(Event $event): Response
    {
        Gate::authorize('view', $event);

        $exporter = new EventExporter($event);

        return $this->download(
            $exporter->guestXlsx(),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $exporter->guestFilename('xlsx')
        );
    }

    public function catererPdf(Event $event): Response
    {
        Gate::authorize('view', $event);

        $exporter = new EventExporter($event);

        return $this->download(
            $exporter->catererPdf(),
            'application/pdf',
            $exporter->catererFilename('pdf')
        );
    }

    private function download(string $content, string $type, string $filename): Response
    {
        return response($content, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
