<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withPivot('created_at');
    }

    public function hasRole(RoleName|string $role): bool
    {
        $name = $role instanceof RoleName ? $role->value : $role;

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn (Role $assigned): bool => $assigned->name === $name);
        }

        return $this->roles()->where('roles.name', $name)->exists();
    }

    public function hasPermission(PermissionName|string $permission): bool
    {
        $name = $permission instanceof PermissionName ? $permission->value : $permission;

        return in_array($name, $this->permissionNames(), true);
    }

    /**
     * @return list<string>
     */
    public function permissionNames(): array
    {
        $this->loadMissing('roles.permissions');

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();
    }
}
