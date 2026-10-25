<?php

namespace App\Support;

use App\Events\DashboardUpdated;
use Throwable;

final class DashboardPulse
{
    public function ping(): void
    {
        $request = request();

        if ($request->attributes->get('dashboard.pulsed') === true) {
            return;
        }

        $request->attributes->set('dashboard.pulsed', true);

        app()->terminating(static function (): void {
            try {
                event(new DashboardUpdated);
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }
}
