<?php

namespace App\Testing\Fakes;

use App\Contracts\PermissionChecker;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

final class PermissionFake implements PermissionChecker
{
    /** @var array<string, bool> */
    private array $exact = [];

    /** @var array<string, bool> */
    private array $wildcards = [];

    /** @var array<int, array{roleId: int, ability: string}> */
    private array $checks = [];

    /**
     * @param  array<string, bool>  $abilities  Ability (or wildcard pattern, e.g. 'purchases.receipt.*') => allowed.
     *                                          An ability not covered by any key is denied.
     */
    public function __construct(array $abilities = [])
    {
        foreach ($abilities as $ability => $allowed) {
            if (str_contains($ability, '*')) {
                $this->wildcards[$ability] = $allowed;
            } else {
                $this->exact[$ability] = $allowed;
            }
        }
    }

    public function check(int $roleId, string $ability): bool
    {
        $this->checks[] = ['roleId' => $roleId, 'ability' => $ability];

        if (array_key_exists($ability, $this->exact)) {
            return $this->exact[$ability];
        }

        foreach ($this->wildcards as $pattern => $allowed) {
            if (Str::is($pattern, $ability)) {
                return $allowed;
            }
        }

        return false;
    }

    public function assertChecked(string $ability, ?int $times = null): void
    {
        $matching = array_values(array_filter($this->checks, fn (array $check): bool => $check['ability'] === $ability));

        PHPUnit::assertNotEmpty($matching, "Permission [{$ability}] was not checked.");

        if ($times !== null) {
            PHPUnit::assertCount($times, $matching, "Permission [{$ability}] was checked ".count($matching)." time(s), expected {$times}.");
        }
    }

    public function assertNotChecked(string $ability): void
    {
        $matching = array_filter($this->checks, fn (array $check): bool => $check['ability'] === $ability);

        PHPUnit::assertEmpty($matching, "Permission [{$ability}] was checked unexpectedly.");
    }

    public function assertNothingChecked(): void
    {
        PHPUnit::assertEmpty($this->checks, 'Expected no permission checks, but some were made.');
    }
}
