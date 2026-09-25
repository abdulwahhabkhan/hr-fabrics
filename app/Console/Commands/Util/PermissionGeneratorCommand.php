<?php

namespace App\Console\Commands\Util;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Permission generator start ...');
        $options = $this->options();
        if ($options['fresh']) {
            User::query()->update(['role_id' => null]);
            Permission::query()->delete();
            Role::query()->delete();
        }

        $routes = Route::getRoutes()->getRoutes();

        foreach ($routes as $route) {
            $action = $route->getActionname();
            $middleware = $this->getMiddleware($route);
            if ($action === 'Closure' || ! $middleware) {
                continue;
            }
            $name = $route->getName();
            $module = explode('.', $name)[1];
            $section = $route->getAction('prefix');
            $section ??= $module;

            $module = $module === 'accounts' ? 'account' : $module;
            $section = str_replace(['/'], '', $section);

            if (! $name) {
                continue;
            }

            $path = Permission::firstOrCreate(
                ['action' => $action, 'name' => $name, 'module' => $module, 'section' => $section]
            );
            if (array_key_exists('role', $route->action)) {
                $role = $route->action['role'];
                $role = Role::firstOrCreate(['name' => $role]);
                $role->permissions()->syncWithoutDetaching($path->id);
            }
        }
        Cache::flush();
        $this->info('Permission generator end ...');

        return 0;
    }

    public function getMiddleware($route): bool
    {
        $middlewares = $route->getAction('middleware');
        if (empty($middlewares)) {
            return false;
        }
        $excluded = $route->getAction('excluded_middleware') ?? [];
        if (is_array($middlewares)) {
            return in_array('auth.role', $middlewares) && ! in_array('auth.role', $excluded);
        }

        return false;
    }
}
