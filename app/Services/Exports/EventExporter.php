<?php

namespace App\Services\Exports;

use App\Models\Event;
use App\Models\Guest;
use App\Services\EventMetrics;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

class EventExporter
{
    private const GUEST_HEADER = [
        'Foyer',
        'E-mail',
        'Téléphone',
        'Places autorisées',
        'Réponse',
        'Places confirmées',
        'Prénoms',
        'Régime / allergies',
        'Message',
        'Répondu le',
        'Réponse hors délai',
        'Pointé le',
        'Pointé par',
    ];

    private const CATERER_HEADER = [
        'Foyer',
        'Couverts',
        'Prénoms',
        'Régime / allergies',
    ];

    private EventMetrics $metrics;

    public function __construct(private Event $event)
    {
        $this->metrics = new EventMetrics($event);
    }

    /**
     * @return list<list<int|string>>
     */
    public function guestRows(): array
    {
        return $this->metrics->guests()
            ->map(fn (Guest $guest) => [
                $guest->name,
                $guest->email ?? '',
                $guest->phone ?? '',
                $guest->allowed_seats,
                $guest->rsvp_status->label(),
                $guest->confirmedSeats(),
                $this->attendeeList($guest),
                $guest->dietary_notes ?? '',
                $guest->rsvp_message ?? '',
                $guest->rsvp_responded_at?->format('d/m/Y H:i') ?? '',
                $guest->rsvp_is_late ? 'Oui' : 'Non',
                $guest->checked_in_at?->format('d/m/Y H:i') ?? '',
                $guest->checkedInBy?->name ?? '',
            ])
            ->all();
    }

    /**
     * @return list<list<int|string>>
     */
    public function catererRows(): array
    {
        return $this->catererHouseholds()
            ->map(fn (array $household) => [
                $household['name'],
                $household['seats'],
                $household['attendees'],
                $household['dietary'],
            ])
            ->all();
    }

    /**
     * @return Collection<int, array{name: string, seats: int, attendees: string, dietary: string}>
     */
    public function catererHouseholds(): Collection
    {
        return $this->metrics->attending()
            ->map(fn (Guest $guest) => [
                'name' => $guest->name,
                'seats' => $guest->confirmedSeats(),
                'attendees' => $this->attendeeList($guest),
                'dietary' => $guest->dietary_notes ?? '',
            ])
            ->values();
    }

    /**
     * @return list<array{0: string, 1: int|string}>
     */
    public function summaryRows(): array
    {
        $summary = $this->metrics->summary();

        return [
            ['Foyers', $summary['households']],
            ['Places prévues', $summary['seats']],
            ['Réservations reçues', $summary['responded']],
            ['Foyers présents', $summary['attending']],
            ['Foyers absents', $summary['declined']],
            ['Foyers en attente', $summary['pending']],
            ['Couverts confirmés', $summary['coming']],
            ['Foyers avec régime', $summary['diet_households']],
            ['Réponses hors délai', $summary['late_responses']],
            ['Généré le', now()->format('d/m/Y H:i')],
        ];
    }

    public function guestCsv(): string
    {
        return $this->csv(self::GUEST_HEADER, $this->guestRows());
    }

    public function guestXlsx(): string
    {
        return $this->xlsx([
            [
                'name' => 'Invités',
                'header' => self::GUEST_HEADER,
                'rows' => $this->guestRows(),
                'widths' => [24.0, 28.0, 18.0, 11.0, 12.0, 11.0, 30.0, 32.0, 40.0, 16.0, 11.0, 16.0, 20.0],
                'freeze' => true,
            ],
            [
                'name' => 'Synthèse',
                'header' => ['Indicateur', 'Valeur'],
                'rows' => $this->summaryRows(),
                'widths' => [28.0, 22.0],
                'freeze' => false,
            ],
        ]);
    }

    public function catererCsv(): string
    {
        return $this->csv(self::CATERER_HEADER, $this->catererRows());
    }

    public function catererXlsx(): string
    {
        return $this->xlsx([
            [
                'name' => 'Couverts',
                'header' => self::CATERER_HEADER,
                'rows' => $this->catererRows(),
                'widths' => [28.0, 11.0, 40.0, 40.0],
                'freeze' => true,
            ],
            [
                'name' => 'Synthèse',
                'header' => ['Indicateur', 'Valeur'],
                'rows' => $this->summaryRows(),
                'widths' => [28.0, 22.0],
                'freeze' => false,
            ],
        ]);
    }

    public function catererPdf(): string
    {
        return Pdf::loadView('exports.caterer-pdf', [
            'event' => $this->event,
            'households' => $this->catererHouseholds(),
            'summary' => $this->metrics->summary(),
            'summaryRows' => $this->summaryRows(),
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->output();
    }

    public function guestFilename(string $extension): string
    {
        return $this->filename('invites', $extension);
    }

    public function catererFilename(string $extension): string
    {
        return $this->filename('couverts-traiteur', $extension);
    }

    private function attendeeList(Guest $guest): string
    {
        $names = preg_split('/\R/u', (string) $guest->attendee_names, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(', ', array_map('trim', $names));
    }

    private function filename(string $suffix, string $extension): string
    {
        $slug = Str::slug($this->event->name) ?: 'evenement';

        return sprintf('%s-%s-%s.%s', $slug, $suffix, now()->format('Ymd'), $extension);
    }

    /**
     * @param  list<string>  $header
     * @param  list<list<int|string>>  $rows
     */
    private function csv(array $header, array $rows): string
    {
        $lines = [$this->csvLine($header)];

        foreach ($rows as $row) {
            $lines[] = $this->csvLine($row);
        }

        return "\xEF\xBB\xBF".implode("\r\n", $lines)."\r\n";
    }

    /**
     * @param  list<int|string>  $cells
     */
    private function csvLine(array $cells): string
    {
        return implode(';', array_map(fn (int|string $cell) => $this->csvCell($cell), $cells));
    }

    private function csvCell(int|string $cell): string
    {
        if (is_int($cell)) {
            return (string) $cell;
        }

        $value = $this->neutralizeFormula($cell);

        if (preg_match('/[";\r\n]/', $value) === 1) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }

    private function neutralizeFormula(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }

    /**
     * @param  list<array{name: string, header: list<string>, rows: list<list<int|string>>, widths: list<float>, freeze: bool}>  $sheets
     */
    private function xlsx(array $sheets): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'hub-export-');
        $writer = new Writer;

        try {
            $writer->openToFile($path);

            try {
                $headerStyle = (new Style)
                    ->setFontBold()
                    ->setBackgroundColor('F1EAE0');

                foreach ($sheets as $index => $definition) {
                    $sheet = $index === 0
                        ? $writer->getCurrentSheet()
                        : $writer->addNewSheetAndMakeItCurrent();

                    $sheet->setName($definition['name']);

                    foreach ($definition['widths'] as $position => $width) {
                        $sheet->setColumnWidth($width, $position + 1);
                    }

                    if ($definition['freeze']) {
                        $sheet->setSheetView((new SheetView)->setFreezeRow(2));
                    }

                    $writer->addRow(Row::fromValues($definition['header'], $headerStyle));

                    foreach ($definition['rows'] as $row) {
                        $writer->addRow(Row::fromValues($row));
                    }
                }
            } finally {
                $writer->close();
            }

            return (string) file_get_contents($path);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
