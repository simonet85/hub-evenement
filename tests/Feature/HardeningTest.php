<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

it('ajoute les en-têtes de sécurité aux réponses web', function () {
    $this->get('/')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()')
        ->assertHeaderMissing('Strict-Transport-Security');

    $guest = Guest::factory()->create();

    $this->get(route('invitations.show', $guest->invitation_token))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'no-referrer');
});

it('ajoute HSTS uniquement sur une requête HTTPS', function () {
    $this->get('https://localhost/')
        ->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

it('bloque la connexion après cinq tentatives par minute', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->post('/login', [
            'email' => 'awa@example.com',
            'password' => 'mauvais-mot-de-passe',
        ])->assertSessionHasErrors('email');
    }

    $this->post('/login', [
        'email' => 'awa@example.com',
        'password' => 'mauvais-mot-de-passe',
    ])
        ->assertStatus(429)
        ->assertSee('Trop de tentatives')
        ->assertSee('Patientez une minute');
});

it('journalise un échec de connexion sans le mot de passe', function () {
    $user = User::factory()->create(['email' => 'awa@example.com']);
    Log::spy();

    $this->post('/login', [
        'email' => 'AWA@example.com',
        'password' => 'mauvais-mot-de-passe',
    ])->assertSessionHasErrors('email');

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context) => $message === 'auth.failed'
            && $context['email'] === 'awa@example.com'
            && filled($context['ip'] ?? null)
            && ! array_key_exists('password', $context),
    );
});

it('affiche une page 404 personnalisée pour une adresse inconnue', function () {
    $this->get('/route-inconnue')
        ->assertNotFound()
        ->assertSee('Page introuvable')
        ->assertSee('Retour à l\'accueil', false)
        ->assertDontSee('Symfony');
});

it('rejette un jeton d’invitation mal formé sans exécuter la page', function () {
    $this->get('/i/trop-court')
        ->assertNotFound()
        ->assertSee('Page introuvable');

    $this->get('/i/'.str_repeat('a', 47))
        ->assertNotFound();
});

it('affiche une page 403 personnalisée pour un événement étranger', function () {
    $event = Event::factory()->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('events.show', $event))
        ->assertForbidden()
        ->assertSee('Accès refusé')
        ->assertDontSee($event->name);
});

it('affiche une page 500 générique sans détail technique', function () {
    config(['app.debug' => false]);

    Route::get('/incident-test', function () {
        throw new RuntimeException('secret-technique-interne');
    });

    $this->get('/incident-test')
        ->assertStatus(500)
        ->assertSee('Une erreur est survenue')
        ->assertSee('L\'incident a été enregistré', false)
        ->assertDontSee('secret-technique-interne')
        ->assertDontSee('RuntimeException');
});
