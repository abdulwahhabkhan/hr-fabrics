<?php

namespace App\Console\Commands\Util;

use App\Enums\Role as RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class PermissionGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:generate {--fresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate routes for roles permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Permission generator start ...');

        if ($this->option('fresh')) {
            User::query()->update(['role_id' => null]);
            Permission::query()->delete();
            Role::query()->delete();
        }

        $existing = Permission::query()
            ->get(['action', 'name', 'module', 'section'])
            ->map(fn (Permission $permission): string => $this->permissionKey($permission->only([
                'action', 'name', 'module', 'section',
            ])))
            ->flip();

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $permission = $this->permissionFromRoute($route);
            if (! $permission || $existing->has($this->permissionKey($permission))) {
                continue;
            }

            Permission::query()->create($permission);
            $existing->put($this->permissionKey($permission), true);
        }

        $this->addSuperAdminRole();

        Cache::flush();
        $this->info('Permission generator end ...');

        return self::SUCCESS;
    }

    public function getMiddleware(RoutingRoute $route): bool
    {
        $middlewares = $route->getAction('middleware');
        if (empty($middlewares) || ! is_array($middlewares)) {
            return false;
        }
        $excluded = $route->getAction('excluded_middleware') ?? [];

        return in_array('auth.role', $middlewares) && ! in_array('auth.role', $excluded);
    }

    /**
     * Build the permission attributes for a role-protected, named controller route.
     *
     * @return array{action: string, name: string, module: string, section: string}|null
     */
    private function permissionFromRoute(RoutingRoute $route): ?array
    {
        $action = $route->getActionName();
        $name = $route->getName();
        if ($action === 'Closure' || ! $name || ! $this->getMiddleware($route)) {
            return null;
        }

        $module = explode('.', $name)[1] ?? $name;
        $section = str_replace('/', '', $route->getAction('prefix') ?? $module);

        return [
            'action' => $action,
            'name' => $name,
            'module' => $module === 'accounts' ? 'account' : $module,
            'section' => $section,
        ];
    }

    /**
     * @param  array{action: string, name: string, module: ?string, section: ?string}  $permission
     */
    private function permissionKey(array $permission): string
    {
        return implode('|',
            [$permission['action'], $permission['name'], $permission['module'], $permission['section']]);
    }

    /**
     * Ensure the Super Admin role exists, grant it every permission and reset its permission cache.
     */
    private function addSuperAdminRole(): void
    {
        $role = Role::query()->find(RoleEnum::SuperAdmin->value);

        if (! $role) {
            $role = Role::query()->forceCreate([
                'id' => RoleEnum::SuperAdmin->value,
                'name' => 'Super Admin',
                'description' => 'Super admin role',
            ]);
            $this->warn('Super admin role created.');
        }

        $role->permissions()->sync(Permission::query()->pluck('id')->toArray());
        Role::clearCache($role->id);
    }
}
