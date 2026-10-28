<?php

use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config([
        'app.env' => 'production',
        'app.debug' => false,
        'app.url' => 'https://hub-evenement.example',
        'session.secure' => true,
        'session.encrypt' => true,
        'session.http_only' => true,
        'logging.default' => 'stack',
        'logging.channels.stack.channels' => ['daily'],
        'logging.channels.daily.level' => 'warning',
        'mail.default' => 'smtp',
        'queue.default' => 'database',
        'cache.default' => 'database',
    ]);
});

it('valide une configuration de production conforme', function () {
    $this->artisan('app:check-production')
        ->expectsOutputToContain('Configuration de production conforme.')
        ->assertSuccessful();
});

it('échoue quand le débogage est actif', function () {
    config(['app.debug' => true]);

    $this->artisan('app:check-production')
        ->expectsOutputToContain('APP_DEBUG doit valoir false.')
        ->assertFailed();
});

it('échoue quand les cookies ou le journal ne sont pas durcis', function () {
    config([
        'session.secure' => false,
        'logging.channels.daily.level' => 'debug',
    ]);

    $this->artisan('app:check-production')
        ->expectsOutputToContain('SESSION_SECURE_COOKIE doit valoir true.')
        ->expectsOutputToContain('LOG_LEVEL doit être warning ou plus strict.')
        ->assertFailed();
});

it('envoie un e-mail de test avec l’option mail', function () {
    Mail::fake();

    $this->artisan('app:check-production', ['--mail' => 'verification@example.test'])
        ->expectsOutputToContain('E-mail de test envoyé à verification@example.test')
        ->assertSuccessful();
});

it('échoue proprement quand l’adresse de test est invalide', function () {
    $this->artisan('app:check-production', ['--mail' => 'adresse-invalide'])
        ->expectsOutputToContain('Adresse de test invalide')
        ->assertFailed();
});
