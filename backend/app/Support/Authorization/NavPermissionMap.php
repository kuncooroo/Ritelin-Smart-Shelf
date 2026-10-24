<?php

namespace App\Support\Authorization;

use App\Enums\PermissionName;
use App\Models\User;

final class NavPermissionMap
{
    public static function canSee(User $user, string $key): bool
    {
        return match ($key) {
            'home' => true,
            'devices' => $user->hasPermission(PermissionName::DevicesView),
            'products' => $user->hasPermission(PermissionName::DevicesView),
            'sensors' => $user->hasPermission(PermissionName::SensorsView),
            'events' => $user->hasPermission(PermissionName::EventsView),
            'anomalies' => $user->hasPermission(PermissionName::AnomaliesView),
            'pos' => $user->hasPermission(PermissionName::PosManage),
            'reconciliation' => $user->hasPermission(PermissionName::ReconciliationView),
            'reports' => $user->hasPermission(PermissionName::ReportsView),
            'settings' => $user->hasPermission(PermissionName::SettingsManage),
            'users' => $user->hasPermission(PermissionName::UsersView),
            default => false,
        };
    }
}
