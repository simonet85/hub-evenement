<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CheckProduction extends Command
{
    protected $signature = 'app:check-production
                            {--mail= : Adresse de test pour vérifier l’envoi SMTP}';

    protected $description = 'Vérifier la configuration de production : HTTPS, cookies, journal, files, stockage et SMTP';

    public function handle(): int
    {
        $this->info('Contrôle de production — Hub Événement');
        $this->newLine();

        $channels = $this->logChannels();
        $levels = array_map(
            fn (string $channel) => config("logging.channels.{$channel}.level"),
            $channels,
        );

        $checks = [
            ['Environnement production', config('app.env') === 'production', 'APP_ENV doit valoir production.'],
            ['Débogage désactivé', config('app.debug') === false, 'APP_DEBUG doit valoir false.'],
            ['URL HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'APP_URL doit commencer par https://.'],
            ['Clé applicative', filled(config('app.key')), 'Lancez php artisan key:generate.'],
            ['Cookie sécurisé', filter_var(config('session.secure'), FILTER_VALIDATE_BOOL), 'SESSION_SECURE_COOKIE doit valoir true.'],
            ['Cookie chiffré', filter_var(config('session.encrypt'), FILTER_VALIDATE_BOOL), 'SESSION_ENCRYPT doit valoir true.'],
            ['Cookie HTTPOnly', filter_var(config('session.http_only'), FILTER_VALIDATE_BOOL), 'SESSION_HTTP_ONLY doit valoir true.'],
            ['Journal quotidien', in_array('daily', $channels, true), 'LOG_STACK doit contenir daily pour la rotation.'],
            ['Niveau de journal', $levels !== [] && ! array_intersect($levels, ['debug', 'info']), 'LOG_LEVEL doit être warning ou plus strict.'],
            ['Transport e-mail', ! in_array(config('mail.default'), ['log', 'array'], true), 'MAIL_MAILER doit être un transport réel (smtp).'],
            ['File de tâches', config('queue.default') !== 'sync', 'QUEUE_CONNECTION doit être database ou un service dédié.'],
            ['Cache partagé', config('cache.default') !== 'array', 'CACHE_STORE ne doit pas valoir array.'],
            ['Manifeste Vite', is_file(public_path('build/manifest.json')), 'Lancez npm run build sur la version déployée.'],
            ['Stockage framework', is_writable(storage_path('framework')), 'Corrigez les droits de storage/framework.'],
            ['Stockage photos', is_writable(storage_path('app/private')), 'Corrigez les droits de storage/app/private.'],
            ['Cache bootstrap', is_writable(base_path('bootstrap/cache')), 'Corrigez les droits de bootstrap/cache.'],
            ['Connexion base', $this->databaseReachable(), 'Vérifiez DB_* et les droits MySQL.'],
        ];

        $failures = 0;

        foreach ($checks as [$label, $passed, $hint]) {
            $this->line(($passed ? '<fg=green>✓</>' : '<fg=red>✗</>').' '.$label.($passed ? '' : ' — '.$hint));

            if (! $passed) {
                $failures++;
            }
        }

        if ($this->option('mail') !== null) {
            $failures += $this->sendTestMail((string) $this->option('mail')) ? 0 : 1;
        }

        $this->newLine();

        if ($failures > 0) {
            $this->error($failures.' contrôle(s) en échec. Corrigez la configuration avant la mise en ligne.');

            return self::FAILURE;
        }

        $this->info('Configuration de production conforme.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function logChannels(): array
    {
        $default = (string) config('logging.default');

        if ($default !== 'stack') {
            return $default === '' ? [] : [$default];
        }

        return array_values(array_filter((array) config('logging.channels.stack.channels', [])));
    }

    private function databaseReachable(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function sendTestMail(string $address): bool
    {
        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $this->line('<fg=red>✗</> Adresse de test invalide — '.$address);

            return false;
        }

        try {
            Mail::raw(
                'Cet e-mail confirme que le transport SMTP de Hub Événement fonctionne.',
                fn ($message) => $message->to($address)->subject('Test SMTP — Hub Événement'),
            );
        } catch (Throwable $exception) {
            $this->line('<fg=red>✗</> Envoi SMTP impossible — '.$exception->getMessage());

            return false;
        }

        $this->line('<fg=green>✓</> E-mail de test envoyé à '.$address);

        return true;
    }
}
