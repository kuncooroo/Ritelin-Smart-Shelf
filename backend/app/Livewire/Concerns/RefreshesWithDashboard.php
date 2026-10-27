<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\On;

trait RefreshesWithDashboard
{
    #[On('dashboard-refreshed')]
    public function refreshFromDashboard(): void
    {
    }
}
