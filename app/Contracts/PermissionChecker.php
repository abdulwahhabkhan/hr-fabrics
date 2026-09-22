<?php

namespace App\Contracts;

interface PermissionChecker
{
    public function check(int $roleId, string $ability): bool;
}
