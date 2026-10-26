<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('inscrit un organisateur et ouvre son espace', function () {
    $this->post('/register', [
        'name' => 'Awa Koné',
        'email' => 'awa@example.com',
        'password' => 'motdepasse',
        'password_confirmation' => 'motdepasse',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticated();

    $user = User::where('email', 'awa@example.com')->firstOrFail();

    expect(Hash::check('motdepasse', $user->password))->toBeTrue();
});

it('refuse un mot de passe trop court', function () {
    $this->post('/register', [
        'name' => 'Awa Koné',
        'email' => 'awa@example.com',
        'password' => 'court',
        'password_confirmation' => 'court',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});

it('connecte un organisateur existant', function () {
    $user = User::factory()->create(['password' => 'motdepasse']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'motdepasse',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('refuse des identifiants invalides', function () {
    $user = User::factory()->create(['password' => 'motdepasse']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'mauvais-mot-de-passe',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('déconnecte un organisateur', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});

it('protège le tableau de bord', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});
