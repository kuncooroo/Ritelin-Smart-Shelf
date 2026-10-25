<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Livewire\Concerns\RefreshesWithDashboard;
use App\Livewire\Concerns\WithPageSize;
use App\Models\User;
use App\Services\DashboardOverview;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Beranda'])]
class Home extends Component
{
    use RefreshesWithDashboard;
    use WithPageSize;
    use WithPagination;

    public function render(DashboardOverview $overview): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('livewire.home', [
            'user' => $user,
            'overview' => $overview->present($this->pageSize()),
            'shortcuts' => array_values(array_filter([
                $user->hasPermission(PermissionName::DevicesView) ? ['label' => 'Perangkat', 'url' => route('dashboard.devices'), 'primary' => true] : null,
                $user->hasPermission(PermissionName::EventsView) ? ['label' => 'Riwayat aktivitas', 'url' => route('dashboard.events'), 'primary' => false] : null,
                $user->hasPermission(PermissionName::AnomaliesView) ? ['label' => 'Anomali', 'url' => route('dashboard.anomalies'), 'primary' => false] : null,
                $user->hasPermission(PermissionName::PosManage) ? ['label' => 'Transaksi kasir', 'url' => route('dashboard.pos'), 'primary' => false] : null,
            ])),
        ]);
    }

    /**
     * @return list<string>
     */
    protected function pageNames(): array
    {
        return ['recent', 'devices'];
    }
}
