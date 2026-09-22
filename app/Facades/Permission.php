<?php

namespace App\Facades;

use App\Contracts\PermissionChecker;
use App\Testing\Fakes\PermissionFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool check(int $roleId, string $ability)
 *
 * @see PermissionChecker
 */
class Permission extends Facade
{
    /**
     * @param  array<string, bool>  $abilities  Ability (or wildcard pattern, e.g. 'purchases.receipt.*') => allowed.
     *                                          An ability not covered by any key is denied.
     */
    public static function fake(array $abilities = []): PermissionFake
    {
        $fake = new PermissionFake($abilities);

        static::swap($fake);

        static::getFacadeApplication()->instance(PermissionChecker::class, $fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return PermissionChecker::class;
    }
}
