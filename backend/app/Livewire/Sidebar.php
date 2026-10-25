<?php

namespace App\Livewire;

use App\Livewire\Concerns\RefreshesWithDashboard;
use App\Models\User;
use App\Support\Navigation\NavBuilder;
use Illuminate\View\View;
use Livewire\Component;

class Sidebar extends Component
{
    use RefreshesWithDashboard;

    public function render(NavBuilder $nav): View
    {
        $user = auth()->user();

        return view('livewire.sidebar', [
            'navGroups' => $user instanceof User ? $nav->grouped($user) : [],
        ]);
    }
}
