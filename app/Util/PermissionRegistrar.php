<?php

namespace App\Util;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;

class PermissionRegistrar
{
    /**
     * Register the permission check method on the gate.
     * We resolve the Gate fresh here, for the benefit of long-running instances.
     */
    public function registerPermissions(): bool
    {
        app(Gate::class)->before(function (Authorizable $user, string $ability) {
            if (method_exists($user, 'checkPermissionTo')) {
                return $user->checkPermissionTo($ability) ?: null;
            }
        });

        return true;
    }
}
