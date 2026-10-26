<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('envoie un lien de réinitialisation', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('réinitialise le mot de passe avec un jeton valide', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nouveaumdp',
        'password_confirmation' => 'nouveaumdp',
    ])->assertRedirect('/login');

    expect(Hash::check('nouveaumdp', $user->fresh()->password))->toBeTrue();
});

it('refuse un jeton invalide', function () {
    $user = User::factory()->create();

    $this->post('/reset-password', [
        'token' => 'jeton-invalide',
        'email' => $user->email,
        'password' => 'nouveaumdp',
        'password_confirmation' => 'nouveaumdp',
    ])->assertSessionHasErrors('email');
});
