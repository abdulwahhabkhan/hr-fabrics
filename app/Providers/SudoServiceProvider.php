<?php

namespace App\Providers;

use App\Contracts\PermissionChecker as PermissionCheckerContract;
use App\Services\PermissionChecker;
use App\Util\PermissionRegistrar;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class SudoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PermissionCheckerContract::class, PermissionChecker::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        Carbon::macro('displayDate', fn (): string => $this->format('d-M-Y'));

        CarbonImmutable::macro('displayDate', fn (): string => $this->format('d-M-Y'));
        $this->bootAuth();
    }

    /**
     * @throws BindingResolutionException
     */
    public function bootAuth(): void
    {
        $this->app->singleton(PermissionRegistrar::class);
        $this->app->make(PermissionRegistrar::class)->registerPermissions();
    }
}
