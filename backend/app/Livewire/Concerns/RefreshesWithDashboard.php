<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\On;

trait RefreshesWithDashboard
{
    #[On('echo-private:dashboard,DashboardUpdated')]
    public function refreshFromDashboard(): void
    {
    }
}
