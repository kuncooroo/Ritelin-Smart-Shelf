<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Pengguna'])]
class Users extends Component
{
    use AuthorizesShelf;

    public string $message = '';

    public ?int $editingUserId = null;

    public string $role = '';

    public function openUser(int $userId): void
    {
        $this->authorizeShelf(PermissionName::UsersManage);
        $account = User::query()->with('roles')->findOrFail($userId);
        $this->editingUserId = $account->id;
        $this->role = (string) ($account->roles->first()->name ?? '');
        $this->resetErrorBag();
    }

    public function closeUser(): void
    {
        $this->editingUserId = null;
        $this->role = '';
        $this->resetErrorBag();
    }

    public function saveRole(AuditLogger $audit): void
    {
        if ($this->editingUserId === null) {
            return;
        }

        $this->setRole($this->editingUserId, $this->role, $audit);
        if ($this->getErrorBag()->isEmpty()) {
            $this->closeUser();
        }
    }

    public function setActive(int $userId, bool $active, AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::UsersManage);
        $account = User::query()->findOrFail($userId);

        if ($account->is(auth()->user()) && ! $active) {
            $this->addError('users', 'Akun yang sedang masuk tidak dapat dinonaktifkan sendiri.');

            return;
        }

        if (! $active && $account->hasRole(RoleName::Admin) && $this->activeAdminCount() <= 1) {
            $this->addError('users', 'Admin aktif terakhir tidak dapat dinonaktifkan.');

            return;
        }

        $account->is_active = $active;
        $account->save();
        $audit->record('user.activation', $account->email.' '.($active ? 'diaktifkan' : 'dinonaktifkan').'.', $account);
        $this->resetErrorBag();
        $this->message = 'Status akun disimpan.';
    }

    public function setRole(int $userId, string $role, AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::UsersManage);
        $account = User::query()->with('roles')->findOrFail($userId);
        Validator::make(
            ['role' => $role],
            ['role' => ['required', Rule::in(array_column(RoleName::cases(), 'value'))]],
        )->validate();

        $next = RoleName::from($role);

        if ($account->hasRole(RoleName::Admin) && $next !== RoleName::Admin && $this->activeAdminCount() <= 1) {
            $this->addError('users', 'Peran admin terakhir tidak dapat diubah.');

            return;
        }

        $roleModel = Role::query()->where('name', $next->value)->firstOrFail();
        $account->roles()->sync([$roleModel->id => ['created_at' => now()]]);
        $audit->record('user.role', 'Peran '.$account->email.' menjadi '.$next->displayName().'.', $account);
        $this->resetErrorBag();
        $this->message = 'Peran akun disimpan.';
    }

    public function render(): View
    {
        return view('livewire.users', [
            'users' => User::query()->with('roles')->orderBy('name')->get(),
            'roles' => Role::query()->orderBy('display_name')->get(),
        ]);
    }

    private function activeAdminCount(): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::Admin->value))
            ->count();
    }
}
