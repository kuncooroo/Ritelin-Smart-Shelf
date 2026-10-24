<?php

namespace App\Support\Authorization;

use App\Enums\PermissionName;
use App\Enums\RoleName;

final class RolePermissionMatrix
{
    /**
     * @return list<string>
     */
    public static function permissionsFor(RoleName $role): array
    {
        $names = match ($role) {
            RoleName::Admin => PermissionName::cases(),
            RoleName::Petugas, RoleName::Pemilik => [
                PermissionName::DevicesView,
                PermissionName::EventsView,
                PermissionName::SensorsView,
                PermissionName::AnomaliesView,
                PermissionName::ReconciliationView,
                PermissionName::ReportsView,
            ],
        };

        return array_map(fn (PermissionName $permission): string => $permission->value, $names);
    }
}
