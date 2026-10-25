<?php

namespace App\Providers;

use App\Models\Anomaly;
use App\Models\Device;
use App\Models\PosTransaction;
use App\Models\ShelfEvent;
use App\Support\DashboardPulse;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $pulse = fn () => app(DashboardPulse::class)->ping();
        Device::saved($pulse);
        ShelfEvent::saved($pulse);
        Anomaly::saved($pulse);
        PosTransaction::saved($pulse);
    }
}
