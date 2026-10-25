<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class MainSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->seedPrimary();
    }

    private function seedPrimary(): void
    {
        $account = config('ops.primary');
        if (! is_array($account)) {
            return;
        }

        $email = $account['email'] ?? null;
        $name = $account['name'] ?? null;
        $password = $account['password'] ?? null;
        if (! is_string($email) || $email === '' || ! is_string($name) || $name === '' || ! is_string($password) || $password === '') {
            return;
        }

        $role = Role::query()->where('name', RoleName::Superadmin->value)->firstOrFail();
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'is_active' => true,
            ],
        );
        $user->roles()->sync([$role->id => ['created_at' => now()]]);
    }
}
