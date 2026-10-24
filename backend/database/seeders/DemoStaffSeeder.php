<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoStaffSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = config('ops.staff', []);
        if (! is_array($accounts)) {
            return;
        }

        foreach ($accounts as $key => $account) {
            if (! is_array($account)) {
                continue;
            }

            $this->seedAccount(is_string($key) ? $key : 'staff', $account);
        }
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private function seedAccount(string $key, array $account): void
    {
        $name = $account['name'] ?? null;
        $email = $account['email'] ?? null;
        $password = $account['password'] ?? null;
        $roleName = $account['role'] ?? null;

        if (! is_string($name) || $name === ''
            || ! is_string($email) || $email === ''
            || ! is_string($password) || $password === ''
            || ! is_string($roleName) || $roleName === '') {
            $this->command?->warn("DemoStaffSeeder melewati [{$key}]: nama, email, sandi, dan peran wajib diisi.");

            return;
        }

        $roleEnum = RoleName::tryFrom($roleName);
        if ($roleEnum === null) {
            $this->command?->error("DemoStaffSeeder menolak [{$key}]: peran tidak dikenal.");

            return;
        }

        $role = Role::query()->where('name', $roleEnum->value)->firstOrFail();

        $user = User::query()->updateOrCreate(
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
