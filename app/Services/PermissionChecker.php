<?php

namespace App\Services;

use App\Contracts\PermissionChecker as PermissionCheckerContract;
use App\Models\Role;

class PermissionChecker implements PermissionCheckerContract
{
    public function check(int $roleId, string $ability): bool
    {
        $permissions = Role::rolePermissions($roleId);

        return array_key_exists($ability, $permissions);
    }
}
