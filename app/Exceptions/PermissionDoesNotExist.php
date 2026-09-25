<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * @phpstan-consistent-constructor
 */
class PermissionDoesNotExist extends InvalidArgumentException
{
    public static function create(string $permissionName): static
    {
        return new static("There is no permission named `{$permissionName}` ");
    }

    public static function withId(int $permissionId): static
    {
        return new static("There is no [permission] with id `{$permissionId}` ");
    }
}
