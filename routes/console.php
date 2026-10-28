<?php

use App\Services\ContributionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('contributions:expire', function (ContributionService $contributions) {
    $count = $contributions->expirePending();

    $this->info($count.' contribution(s) expirée(s).');
})->purpose('Expirer les contributions en attente dépassées');

Schedule::command('contributions:expire')->everyFiveMinutes();
Schedule::command('guests:purge')->dailyAt('03:30');
