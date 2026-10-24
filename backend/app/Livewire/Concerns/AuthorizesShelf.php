<?php

namespace App\Livewire\Concerns;

use App\Enums\PermissionName;

trait AuthorizesShelf
{
    protected function authorizeShelf(PermissionName $permission): void
    {
        abort_unless(auth()->user()?->hasPermission($permission) ?? false, 403);
    }
}
