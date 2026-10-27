<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GuestImporter
{
    /**
     * @return array{imported: int, errors: list<string>}
     */
    public function import(Event $event, string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ['imported' => 0, 'errors' => ['Impossible de lire le fichier.']];
        }

        $delimiter = $this->detectDelimiter($handle);
        $rows = [];
        $errors = [];
        $lineNumber = 0;
        $firstRow = true;

        while (($data = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $lineNumber++;

            if ($data === [null] || trim(implode('', array_map('strval', $data))) === '') {
                continue;
            }

            $data[0] = ltrim((string) ($data[0] ?? ''), "\xEF\xBB\xBF");

            if ($firstRow) {
                $firstRow = false;

                if (in_array(mb_strtolower(trim($data[0])), ['nom', 'name', 'foyer'], true)) {
                    continue;
                }
            }

            $line = [
                'name' => trim((string) ($data[0] ?? '')),
                'email' => trim((string) ($data[1] ?? '')),
                'phone' => trim((string) ($data[2] ?? '')),
                'allowed_seats' => trim((string) ($data[3] ?? '')),
            ];

            $validator = Validator::make([
                'name' => $line['name'],
                'email' => $line['email'] !== '' ? $line['email'] : null,
                'phone' => $line['phone'] !== '' ? $line['phone'] : null,
                'allowed_seats' => $line['allowed_seats'] !== '' ? $line['allowed_seats'] : 1,
            ], $this->rules());

            if ($validator->fails()) {
                $errors[] = 'Ligne '.$lineNumber.' : '.implode(' ', $validator->errors()->all());

                continue;
            }

            $validated = $validator->validated();
            $rows[] = [
                'event_id' => $event->id,
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'allowed_seats' => $validated['allowed_seats'] ?? 1,
                'invitation_token' => Str::random(48),
                'entry_token' => Str::random(48),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        if ($rows !== []) {
            DB::transaction(fn () => Guest::insert($rows));
        }

        return ['imported' => count($rows), 'errors' => $errors];
    }

    /**
     * @param  resource  $handle
     */
    private function detectDelimiter($handle): string
    {
        $firstLine = (string) fgets($handle);
        rewind($handle);

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'allowed_seats' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }
}
