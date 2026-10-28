<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventMetrics;
use App\Services\Exports\EventExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class CatererController extends Controller
{
    private const FORMATS = ['csv', 'xlsx', 'pdf'];

    public function show(Request $request, Event $event): Response
    {
        $event->load([
            'guests' => fn ($query) => $query->orderBy('name'),
        ]);

        $exporter = new EventExporter($event);
        $expiresAt = Carbon::createFromTimestamp($request->integer('expires'));
        $metrics = new EventMetrics($event);

        $downloads = collect(self::FORMATS)
            ->mapWithKeys(fn (string $format) => [
                $format => URL::temporarySignedRoute('caterer.export', $expiresAt, [
                    'event' => $event,
                    'format' => $format,
                ]),
            ]);

        return response()
            ->view('caterer.show', [
                'event' => $event,
                'summary' => $metrics->summary(),
                'households' => $exporter->catererHouseholds(),
                'expiresAt' => $expiresAt,
                'downloads' => $downloads,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function export(Event $event, string $format): Response
    {
        $exporter = new EventExporter($event);

        [$content, $type] = match ($format) {
            'csv' => [$exporter->catererCsv(), 'text/csv; charset=UTF-8'],
            'xlsx' => [$exporter->catererXlsx(), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'pdf' => [$exporter->catererPdf(), 'application/pdf'],
            default => abort(404),
        };

        return response($content, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => 'attachment; filename="'.$exporter->catererFilename($format).'"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
