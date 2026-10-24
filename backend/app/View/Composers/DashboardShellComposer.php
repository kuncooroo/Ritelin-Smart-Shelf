<?php

namespace App\View\Composers;

use App\Models\User;
use App\Support\Navigation\NavBuilder;
use Illuminate\View\View;

final class DashboardShellComposer
{
    public function __construct(private readonly NavBuilder $nav) {}

    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            $view->with(['navGroups' => []]);

            return;
        }

        $user->loadMissing('roles');

        $view->with([
            'navGroups' => $this->nav->grouped($user),
        ]);
    }
}
