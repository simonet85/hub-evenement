<?php

namespace App\Console\Commands;

use App\Models\Contribution;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurgeGuestData extends Command
{
    private const ANONYMOUS_NAME = 'Invité anonymisé';

    protected $signature = 'guests:purge
                            {--days= : Jours de conservation après l’événement}
                            {--dry-run : Afficher le résultat sans rien supprimer}';

    protected $description = 'Supprimer les données personnelles des invités des événements terminés';

    public function handle(): int
    {
        $days = $this->option('days') ?? config('retention.guest_data_days', 90);

        if (! is_numeric($days) || (int) $days < 0) {
            $this->error('Le nombre de jours doit être un entier positif ou nul.');

            return self::FAILURE;
        }

        $days = (int) $days;
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $events = Event::query()
            ->where('starts_at', '<=', $cutoff)
            ->orderBy('id')
            ->get();

        if ($events->isEmpty()) {
            $this->info("Aucun événement terminé depuis au moins {$days} jour(s).");

            return self::SUCCESS;
        }

        $totals = ['guests' => 0, 'guestbook' => 0, 'contributions' => 0];

        foreach ($events as $event) {
            $guests = $event->guests()->count();
            $guestbook = $event->guestbookEntries()->count();
            $contributions = Contribution::query()
                ->where('event_id', $event->id)
                ->where(function ($query) {
                    $query->where('contributor_name', '!=', self::ANONYMOUS_NAME)
                        ->orWhere('phone', '!=', '')
                        ->orWhereNotNull('message')
                        ->orWhereNotNull('payload');
                })
                ->count();

            $totals['guests'] += $guests;
            $totals['guestbook'] += $guestbook;
            $totals['contributions'] += $contributions;

            $this->line(sprintf(
                '%sÉvénement #%d : %d foyer(s), %d message(s), %d contribution(s) anonymisée(s).',
                $dryRun ? 'Simulation — ' : '',
                $event->id,
                $guests,
                $guestbook,
                $contributions,
            ));

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($event) {
                Contribution::query()
                    ->where('event_id', $event->id)
                    ->update([
                        'guest_id' => null,
                        'contributor_name' => self::ANONYMOUS_NAME,
                        'phone' => '',
                        'message' => null,
                        'payload' => null,
                    ]);

                Guest::query()->where('event_id', $event->id)->delete();
            });

            Log::info('guest-data.purged', [
                'event_id' => $event->id,
                'guests' => $guests,
                'guestbook' => $guestbook,
                'contributions' => $contributions,
            ]);
        }

        $this->info(sprintf(
            '%s%d foyer(s), %d message(s), %d contribution(s) traités.',
            $dryRun ? 'Simulation : ' : '',
            $totals['guests'],
            $totals['guestbook'],
            $totals['contributions'],
        ));

        return self::SUCCESS;
    }
}
