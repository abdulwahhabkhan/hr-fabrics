<?php

namespace App\Traits;

use App\Contracts\PermissionChecker;
use App\Models\Role;
use Illuminate\Support\Collection;

trait HasPermission
{
    /**
     * User as permissions via Role
     */
    public function permissions(): Collection|array
    {
        return Role::permissionsByRole($this->getAttribute('role_id'));
    }

    public function checkPermissionTo(string $ability): bool
    {
        return $this->hasPermission($ability);
    }

    public function hasPermission(string $ability): bool
    {
        return app(PermissionChecker::class)->check($this->getAttribute('role_id'), $ability);
    }
}
