<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Livewire\Concerns\ConfirmsActions;
use App\Livewire\Concerns\WithPageSize;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Pengguna'])]
class Users extends Component
{
    use AuthorizesShelf;
    use ConfirmsActions;
    use WithPageSize;
    use WithPagination;

    #[Url(as: 'bagian', except: 'pengguna')]
    public string $bagian = 'pengguna';

    public string $search = '';

    public string $message = '';

    public bool $showForm = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public bool $isActive = true;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorizeShelf(PermissionName::UsersView);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorizeShelf(PermissionName::UsersManage);
        $this->reset('editingUserId', 'name', 'email', 'password', 'role');
        $this->role = RoleName::Staff->value;
        $this->isActive = true;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function openUser(int $userId): void
    {
        $this->authorizeShelf(PermissionName::UsersManage);
        $account = User::query()->with('roles')->findOrFail($userId);
        $this->editingUserId = $account->id;
        $this->name = $account->name;
        $this->email = $account->email;
        $this->password = '';
        $this->role = (string) ($account->roles->first()->name ?? RoleName::Staff->value);
        $this->isActive = $account->is_active;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function closeUser(): void
    {
        $this->showForm = false;
        $this->editingUserId = null;
        $this->reset('name', 'email', 'password', 'role');
        $this->resetErrorBag();
    }

    public function saveUser(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::UsersManage);
        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'password' => $this->editingUserId === null ? ['required', 'string', 'min:8'] : ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_column(RoleName::cases(), 'value'))],
        ], [
            'password.required' => 'Kata sandi wajib diisi untuk akun baru.',
            'password.min' => 'Kata sandi paling sedikit 8 karakter.',
            'email.unique' => 'Email sudah dipakai.',
        ]);

        $account = $this->editingUserId === null
            ? new User
            : User::query()->with('roles')->findOrFail($this->editingUserId);

        if ($account->exists && $account->is(auth()->user()) && ! $this->isActive) {
            $this->addError('users', 'Akun yang sedang masuk tidak dapat dinonaktifkan sendiri.');

            return;
        }

        $next = RoleName::from($this->role);
        if ($account->exists && $account->hasRole(RoleName::Superadmin) && ($next !== RoleName::Superadmin || ! $this->isActive) && $this->activeAdminCount() <= 1) {
            $this->addError('users', 'Superadmin aktif terakhir tidak dapat diubah atau dinonaktifkan.');

            return;
        }

        $account->name = $this->name;
        $account->email = $this->email;
        $account->is_active = $this->isActive;
        if ($this->password !== '') {
            $account->password = $this->password;
        }
        $account->save();

        $roleModel = Role::query()->where('name', $next->value)->firstOrFail();
        $account->roles()->sync([$roleModel->id => ['created_at' => now()]]);
        $audit->record(
            $this->editingUserId === null ? 'user.created' : 'user.updated',
            'Akun '.$account->email.' disimpan sebagai '.$next->displayName().'.',
            $account,
        );
        $this->closeUser();
        $this->message = 'Akun disimpan.';
    }

    public function setActive(int $userId, bool $active, AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::UsersManage);
        $account = User::query()->findOrFail($userId);

        if ($account->is(auth()->user()) && ! $active) {
            $this->addError('users', 'Akun yang sedang masuk tidak dapat dinonaktifkan sendiri.');

            return;
        }

        if (! $active && $account->hasRole(RoleName::Superadmin) && $this->activeAdminCount() <= 1) {
            $this->addError('users', 'Superadmin aktif terakhir tidak dapat dinonaktifkan.');

            return;
        }

        $account->is_active = $active;
        $account->save();
        $audit->record('user.activation', $account->email.' '.($active ? 'diaktifkan' : 'dinonaktifkan').'.', $account);
        $this->resetErrorBag();
        $this->message = 'Status akun disimpan.';
    }

    public function openDelete(int $userId): void
    {
        $this->authorizeShelf(PermissionName::UsersManage);
        $this->deletingId = $userId;
        $this->resetErrorBag();
    }

    public function closeDelete(): void
    {
        $this->deletingId = null;
    }

    public function togglePermission(string $roleName, string $permissionName, AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::UsersManage);
        $role = RoleName::tryFrom($roleName);
        $permission = PermissionName::tryFrom($permissionName);
        if ($role === null || $permission === null) {
            return;
        }

        if ($role === RoleName::Superadmin && in_array($permission, [PermissionName::UsersView, PermissionName::UsersManage], true)) {
            $this->addError('roles', 'Peran tertinggi tetap memegang hak melihat dan mengelola akun.');

            return;
        }

        $roleModel = Role::query()->where('name', $role->value)->firstOrFail();
        $permissionModel = Permission::query()->where('name', $permission->value)->firstOrFail();
        $granted = $roleModel->permissions()->where('permissions.id', $permissionModel->id)->exists();

        if ($granted) {
            $roleModel->permissions()->detach($permissionModel->id);
            $summary = $role->displayName().' tidak lagi memiliki hak '.$permission->displayName().'.';
        } else {
            $roleModel->permissions()->attach($permissionModel->id, ['created_at' => now()]);
            $summary = $role->displayName().' mendapat hak '.$permission->displayName().'.';
        }

        $audit->record('role.permission', $summary, $roleModel);
        $this->resetErrorBag();
        $this->message = 'Hak akses disimpan.';
    }

    public function deleteUser(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::UsersManage);
        $account = User::query()->find($this->deletingId);
        if ($account === null) {
            $this->closeDelete();

            return;
        }

        if ($account->is(auth()->user())) {
            $this->addError('users', 'Akun yang sedang masuk tidak dapat dihapus.');

            return;
        }

        if ($account->hasRole(RoleName::Superadmin) && $this->activeAdminCount() <= 1) {
            $this->addError('users', 'Superadmin aktif terakhir tidak dapat dihapus.');

            return;
        }

        $email = $account->email;
        $account->roles()->detach();
        $account->delete();
        $audit->record('user.deleted', 'Akun '.$email.' dihapus.');
        $this->closeDelete();
        $this->message = 'Akun '.$email.' dihapus.';
    }

    public function render(): View
    {
        if (! in_array($this->bagian, ['pengguna', 'peran'], true)) {
            $this->bagian = 'pengguna';
        }

        $users = null;
        if ($this->bagian === 'pengguna') {
            $term = trim($this->search);
            $query = User::query()->with('roles')->orderBy('name');
            if ($term !== '') {
                $query->where(function ($builder) use ($term): void {
                    $builder->where('name', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%');
                });
            }
            $users = $query->paginate($this->pageSize());
        }

        $stored = Role::query()->with('permissions')->get()->keyBy('name');

        return view('livewire.users', [
            'users' => $users,
            'canManage' => auth()->user()?->hasPermission(PermissionName::UsersManage) ?? false,
            'deletingAccount' => $this->deletingId === null ? null : User::query()->find($this->deletingId),
            'roles' => collect(RoleName::cases())->map(function (RoleName $role) use ($stored): array {
                $granted = $stored->get($role->value)?->permissions->pluck('name')->all() ?? [];

                return [
                    'name' => $role->value,
                    'label' => $role->displayName(),
                    'description' => $role->description(),
                    'highest' => $role === RoleName::Superadmin,
                    'granted' => $granted,
                ];
            }),
            'permissions' => PermissionName::cases(),
        ]);
    }

    /**
     * @return list<string>
     */
    protected function confirmableActions(): array
    {
        return ['saveUser', 'deleteUser', 'setActive', 'togglePermission'];
    }

    private function activeAdminCount(): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::Superadmin->value))
            ->count();
    }
}
