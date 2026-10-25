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
            RoleName::Superadmin => PermissionName::cases(),
            RoleName::Admin => array_values(array_filter(
                PermissionName::cases(),
                fn (PermissionName $permission): bool => ! in_array($permission, [
                    PermissionName::UsersView,
                    PermissionName::UsersManage,
                ], true),
            )),
            RoleName::Staff => [
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
