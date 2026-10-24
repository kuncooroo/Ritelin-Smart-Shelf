<?php

namespace App\Http\Controllers\Web;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardOverview;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardOverview $overview): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('dashboard.home', [
            'user' => $user,
            'overview' => $overview->present(),
            'shortcuts' => array_values(array_filter([
                $user->hasPermission(PermissionName::DevicesView) ? ['label' => 'Perangkat', 'url' => route('dashboard.devices'), 'primary' => true] : null,
                $user->hasPermission(PermissionName::EventsView) ? ['label' => 'Riwayat aktivitas', 'url' => route('dashboard.events'), 'primary' => false] : null,
                $user->hasPermission(PermissionName::AnomaliesView) ? ['label' => 'Anomali', 'url' => route('dashboard.anomalies'), 'primary' => false] : null,
                $user->hasPermission(PermissionName::PosManage) ? ['label' => 'Transaksi kasir', 'url' => route('dashboard.pos'), 'primary' => false] : null,
            ])),
        ]);
    }
}
