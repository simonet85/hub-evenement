<?php

namespace App\Providers;

use App\Services\MoneyFusion\FakeMoneyFusionGateway;
use App\Services\MoneyFusion\PackageMoneyFusionGateway;
use App\Services\MoneyFusion\PaymentGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function () {
            return config('services.moneyfusion.driver') === 'http'
                ? new PackageMoneyFusionGateway
                : new FakeMoneyFusionGateway;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
    }
}
