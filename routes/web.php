<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CoOrganizerController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestImportController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('i/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('i/{token}/rsvp', [InvitationController::class, 'rsvp'])
    ->middleware('throttle:20,1')
    ->name('invitations.rsvp');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [EventController::class, 'index'])->name('dashboard');

    Route::get('events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');

    Route::post('events/{event}/guests', [GuestController::class, 'store'])->name('guests.store');
    Route::delete('events/{event}/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');
    Route::post('events/{event}/guests/{guest}/invitation', [GuestController::class, 'regenerateInvitation'])->name('guests.invitation.regenerate');
    Route::post('events/{event}/guests/import', [GuestImportController::class, 'store'])->name('guests.import');

    Route::post('events/{event}/co-organizers', [CoOrganizerController::class, 'store'])->name('co-organizers.store');
    Route::delete('events/{event}/co-organizers/{user}', [CoOrganizerController::class, 'destroy'])->name('co-organizers.destroy');

    Route::get('events/{event}/scan', [CheckInController::class, 'scan'])->name('check-in.scan');
    Route::post('events/{event}/scan', [CheckInController::class, 'record'])->name('check-in.record');
    Route::delete('events/{event}/guests/{guest}/check-in', [CheckInController::class, 'undo'])->name('check-in.undo');
    Route::get('events/{event}/guests/{guest}/entry-qr.svg', [CheckInController::class, 'qr'])->name('check-in.qr');

    Route::get('check-in/{entryToken}', [CheckInController::class, 'show'])->name('check-in.show');
    Route::post('check-in/{entryToken}', [CheckInController::class, 'store'])->name('check-in.store');
    Route::post('check-in/{entryToken}/undo', [CheckInController::class, 'undoForm'])->name('check-in.undo.form');
});
