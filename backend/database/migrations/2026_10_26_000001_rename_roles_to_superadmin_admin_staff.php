<?php

use App\Enums\RoleName;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('roles')) {
            return;
        }

        $this->renameRole('admin', 'superadmin');
        $this->renameRole('petugas', 'staff');
        $this->ensureRole('staff', RoleName::Staff);
        $this->moveRoleUsers('pemilik', 'staff');
        $this->renameRole('pemilik', 'admin');

        (new RolePermissionSeeder)->run();
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('roles')) {
            return;
        }

        $this->renameRole('admin', 'pemilik');
        $this->renameRole('staff', 'petugas');
        $this->renameRole('superadmin', 'admin');
    }

    private function renameRole(string $from, string $to): void
    {
        $source = DB::table('roles')->where('name', $from)->first();
        $target = DB::table('roles')->where('name', $to)->first();
        if ($source === null || $target !== null) {
            return;
        }

        $role = RoleName::tryFrom($to);
        DB::table('roles')->where('id', $source->id)->update([
            'name' => $to,
            'display_name' => $role?->displayName() ?? $to,
            'description' => $role?->description(),
            'updated_at' => now(),
        ]);
    }

    private function ensureRole(string $name, RoleName $role): void
    {
        if (DB::table('roles')->where('name', $name)->exists()) {
            return;
        }

        DB::table('roles')->insert([
            'name' => $name,
            'display_name' => $role->displayName(),
            'description' => $role->description(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function moveRoleUsers(string $from, string $to): void
    {
        $source = DB::table('roles')->where('name', $from)->first();
        $target = DB::table('roles')->where('name', $to)->first();
        if ($source === null || $target === null) {
            return;
        }

        $userIds = DB::table('role_user')->where('role_id', $source->id)->pluck('user_id');
        foreach ($userIds as $userId) {
            $already = DB::table('role_user')
                ->where('user_id', $userId)
                ->where('role_id', $target->id)
                ->exists();
            if ($already) {
                DB::table('role_user')->where('user_id', $userId)->where('role_id', $source->id)->delete();

                continue;
            }

            DB::table('role_user')->where('user_id', $userId)->where('role_id', $source->id)->update([
                'role_id' => $target->id,
            ]);
        }
    }
};
