<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Authorization\RolePermissionMatrix;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $permissionIds = [];

            foreach (PermissionName::cases() as $permission) {
                $model = Permission::query()->updateOrCreate(
                    ['name' => $permission->value],
                    ['display_name' => $permission->displayName()],
                );
                $permissionIds[$permission->value] = $model->id;
            }

            foreach (RoleName::cases() as $roleName) {
                $role = Role::query()->updateOrCreate(
                    ['name' => $roleName->value],
                    [
                        'display_name' => $roleName->displayName(),
                        'description' => $roleName->description(),
                    ],
                );

                $sync = [];
                foreach (RolePermissionMatrix::permissionsFor($roleName) as $grantName) {
                    $sync[$permissionIds[$grantName]] = ['created_at' => now()];
                }

                $role->permissions()->sync($sync);
            }
        });
    }
}
