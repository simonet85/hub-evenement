<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CatererController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CoOrganizerController;
use App\Http\Controllers\DemoInvitationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\GuestbookController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestImportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PilotageController;
use App\Http\Controllers\ProgramStepController;
use App\Http\Controllers\RegistryController;
use App\Http\Controllers\ReminderController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('demo', DemoInvitationController::class)
    ->middleware('throttle:20,1')
    ->name('demo.invitation');

Route::get('i/{token}', [InvitationController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('invitations.show');
Route::get('i/{token}/programme', [InvitationController::class, 'programme'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('invitations.programme');
Route::get('i/{token}/galerie', [GalleryController::class, 'guest'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('invitations.gallery');
Route::get('i/{token}/cadeaux', [RegistryController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('invitations.registry');
Route::post('i/{token}/cagnotte', [RegistryController::class, 'storeCagnotte'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:10,1')
    ->name('contributions.cagnotte');
Route::post('i/{token}/cadeaux/{gift}', [RegistryController::class, 'storeGift'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:10,1')
    ->name('contributions.gift');
Route::post('i/{token}/rsvp', [InvitationController::class, 'rsvp'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:20,1')
    ->name('invitations.rsvp');

Route::post('i/{token}/livre-dor', [GuestbookController::class, 'store'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:5,1')
    ->name('guestbook.store');

Route::get('galerie/photos/{photo}/{variant}', [GalleryController::class, 'serve'])
    ->middleware('signed')
    ->name('gallery.photo');

Route::get('traiteur/{event}', [CatererController::class, 'show'])
    ->middleware('signed')
    ->name('caterer.show');
Route::get('traiteur/{event}/exports/{format}', [CatererController::class, 'export'])
    ->middleware('signed')
    ->whereIn('format', ['csv', 'xlsx', 'pdf'])
    ->name('caterer.export');

Route::get('paiements/retour/{contribution}', [PaymentController::class, 'show'])
    ->middleware('signed')
    ->name('payments.return');
Route::post('paiements/webhook/{contribution}', [PaymentController::class, 'webhook'])
    ->middleware(['signed', 'throttle:120,1'])
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->name('payments.webhook');
Route::get('paiement-simule/{token}', [PaymentController::class, 'simulator'])
    ->name('payments.simulator');
Route::post('paiement-simule/{token}', [PaymentController::class, 'simulate'])
    ->middleware('throttle:10,1')
    ->name('payments.simulator.complete');

Route::get('relances/desinscription/{guest}', [ReminderController::class, 'unsubscribeShow'])
    ->middleware('signed')
    ->name('reminders.unsubscribe.show');
Route::post('relances/desinscription/{guest}', [ReminderController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('reminders.unsubscribe');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');
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
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    Route::post('events/{event}/guests', [GuestController::class, 'store'])->name('guests.store');
    Route::delete('events/{event}/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');
    Route::post('events/{event}/guests/{guest}/invitation', [GuestController::class, 'regenerateInvitation'])->name('guests.invitation.regenerate');
    Route::post('events/{event}/guests/import', [GuestImportController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('guests.import');

    Route::get('events/{event}/relances', [ReminderController::class, 'index'])->name('reminders.index');
    Route::post('events/{event}/relances', [ReminderController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('reminders.store');

    Route::get('events/{event}/livre-dor', [GuestbookController::class, 'index'])->name('guestbook.index');
    Route::post('events/{event}/livre-dor/{entry}/approuver', [GuestbookController::class, 'approve'])->name('guestbook.approve');
    Route::post('events/{event}/livre-dor/{entry}/masquer', [GuestbookController::class, 'hide'])->name('guestbook.hide');
    Route::post('events/{event}/livre-dor/{entry}/rejeter', [GuestbookController::class, 'reject'])->name('guestbook.reject');

    Route::get('events/{event}/galerie', [GalleryController::class, 'index'])->name('gallery.index');
    Route::post('events/{event}/galerie', [GalleryController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('gallery.store');
    Route::get('events/{event}/galerie/{photo}/apercu/{variant?}', [GalleryController::class, 'preview'])->name('gallery.preview');
    Route::put('events/{event}/galerie/{photo}', [GalleryController::class, 'caption'])->name('gallery.caption');
    Route::post('events/{event}/galerie/{photo}/approuver', [GalleryController::class, 'approve'])->name('gallery.approve');
    Route::post('events/{event}/galerie/{photo}/masquer', [GalleryController::class, 'hide'])->name('gallery.hide');
    Route::post('events/{event}/galerie/{photo}/rejeter', [GalleryController::class, 'reject'])->name('gallery.reject');

    Route::get('events/{event}/cadeaux', [GiftController::class, 'index'])->name('registry.index');
    Route::post('events/{event}/cadeaux', [GiftController::class, 'storeGift'])->name('gifts.store');
    Route::put('events/{event}/cadeaux/{gift}', [GiftController::class, 'updateGift'])->name('gifts.update');
    Route::put('events/{event}/cagnotte', [GiftController::class, 'updateCagnotte'])->name('cagnotte.update');
    Route::post('events/{event}/contributions/{contribution}/journal', [GiftController::class, 'reconcile'])->name('contributions.reconcile');

    Route::post('events/{event}/co-organizers', [CoOrganizerController::class, 'store'])->name('co-organizers.store');
    Route::delete('events/{event}/co-organizers/{user}', [CoOrganizerController::class, 'destroy'])->name('co-organizers.destroy');

    Route::post('events/{event}/programme', [ProgramStepController::class, 'store'])->name('program-steps.store');
    Route::put('events/{event}/programme/{step}', [ProgramStepController::class, 'update'])->name('program-steps.update');
    Route::delete('events/{event}/programme/{step}', [ProgramStepController::class, 'destroy'])->name('program-steps.destroy');
    Route::post('events/{event}/programme/{step}/move', [ProgramStepController::class, 'move'])->name('program-steps.move');

    Route::get('events/{event}/scan', [CheckInController::class, 'scan'])->name('check-in.scan');
    Route::post('events/{event}/scan', [CheckInController::class, 'record'])
        ->middleware('throttle:120,1')
        ->name('check-in.record');
    Route::delete('events/{event}/guests/{guest}/check-in', [CheckInController::class, 'undo'])->name('check-in.undo');
    Route::get('events/{event}/guests/{guest}/entry-qr.svg', [CheckInController::class, 'qr'])->name('check-in.qr');

    Route::get('events/{event}/pilotage', [PilotageController::class, 'show'])->name('pilotage.show');
    Route::post('events/{event}/pilotage/lien-traiteur', [PilotageController::class, 'catererLink'])->name('pilotage.caterer-link');

    Route::get('events/{event}/exports/invites.csv', [ExportController::class, 'guestsCsv'])->name('exports.guests.csv');
    Route::get('events/{event}/exports/invites.xlsx', [ExportController::class, 'guestsXlsx'])->name('exports.guests.xlsx');
    Route::get('events/{event}/exports/traiteur.pdf', [ExportController::class, 'catererPdf'])->name('exports.caterer.pdf');

    Route::get('check-in/{entryToken}', [CheckInController::class, 'show'])->name('check-in.show');
    Route::post('check-in/{entryToken}', [CheckInController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('check-in.store');
    Route::post('check-in/{entryToken}/undo', [CheckInController::class, 'undoForm'])->name('check-in.undo.form');
});
