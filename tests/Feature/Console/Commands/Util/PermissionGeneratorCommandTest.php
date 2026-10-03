<?php

use App\Console\Commands\Util\PermissionGeneratorCommand;
use App\Enums\Role as RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\artisan;

test('creates the super admin role and grants it every generated permission', function () {
    // arrange
    Cache::put(Role::modelCacheKey((string) RoleEnum::SuperAdmin->value), ['stale']);
    Cache::put(Role::modelCacheKey('2_'.RoleEnum::SuperAdmin->value), collect(['stale']));

    // action
    artisan(PermissionGeneratorCommand::class)
        ->expectsOutput('Super admin role created.')
        ->assertSuccessful();

    // assert
    $role = Role::query()->findOrFail(RoleEnum::SuperAdmin->value);
    expect(Permission::query()->count())->toBeGreaterThan(0)
        ->and($role->name)->toBe('Super Admin')
        ->and($role->permissions()->pluck('id')->sort()->values()->all())
        ->toBe(Permission::query()->pluck('id')->sort()->values()->all())
        ->and(Role::permissionsByRole($role->id))->not->toContain('stale');
});

test('grants new permissions to an existing super admin role without duplicating permissions', function () {
    // arrange
    artisan(PermissionGeneratorCommand::class)->assertSuccessful();
    $permissionCount = Permission::query()->count();
    $role = Role::query()->findOrFail(RoleEnum::SuperAdmin->value);
    $role->permissions()->detach(Permission::query()->value('id'));

    // action
    artisan(PermissionGeneratorCommand::class)
        ->doesntExpectOutput('Super admin role created.')
        ->assertSuccessful();

    // assert
    expect(Permission::query()->count())->toBe($permissionCount)
        ->and(Role::query()->count())->toBe(1)
        ->and($role->permissions()->count())->toBe($permissionCount);
});

test('fresh option rebuilds roles and keeps only the super admin role', function () {
    // arrange
    Role::factory()->create(['name' => 'manager']);
    Permission::factory()->create();

    // action
    artisan(PermissionGeneratorCommand::class, ['--fresh' => true])->assertSuccessful();

    // assert
    expect(Role::query()->pluck('id')->all())->toBe([RoleEnum::SuperAdmin->value])
        ->and(Role::query()->find(RoleEnum::SuperAdmin->value)->permissions()->count())
        ->toBe(Permission::query()->count());
});
