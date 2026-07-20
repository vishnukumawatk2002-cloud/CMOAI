<?php

namespace App\Models\Concerns;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasAdminRoles
{
    protected ?array $cachedPermissionSlugs = null;

    protected ?bool $cachedIsSuperAdmin = null;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_role');
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn (Role $role) => in_array($role->slug, $roles, true));
        }

        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    public function isSuperAdmin(): bool
    {
        if ($this->cachedIsSuperAdmin !== null) {
            return $this->cachedIsSuperAdmin;
        }

        return $this->cachedIsSuperAdmin = $this->hasRole('super_admin');
    }

    public function permissions(): Collection
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    public function permissionSlugs(): array
    {
        if ($this->cachedPermissionSlugs !== null) {
            return $this->cachedPermissionSlugs;
        }

        if ($this->isSuperAdmin()) {
            return $this->cachedPermissionSlugs = Permission::query()->pluck('slug')->all();
        }

        if ($this->relationLoaded('roles')) {
            $slugs = $this->roles
                ->flatMap(fn (Role $role) => $role->relationLoaded('permissions')
                    ? $role->permissions->pluck('slug')
                    : collect())
                ->unique()
                ->values()
                ->all();

            return $this->cachedPermissionSlugs = $slugs;
        }

        $slugs = $this->roles()
            ->with('permissions:id,slug')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values()
            ->all();

        return $this->cachedPermissionSlugs = $slugs;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissionSlugs(), true);
    }

    public function syncRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
        $this->cachedPermissionSlugs = null;
        $this->cachedIsSuperAdmin = null;
    }
}
