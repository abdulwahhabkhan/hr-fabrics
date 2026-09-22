<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Event;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class EssentialsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureCommands();
        $this->configureDates();
        $this->configureUrls();
        $this->seedPermissionsForTest();
    }

    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands(
            $this->app->isProduction(),
        );
    }

    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);

        Carbon::macro('date', fn () => $this->format(config('settings.date_format')));

        Carbon::macro('formDate', fn () => $this->format('d-M-Y'));

        Carbon::macro('daysBetween', function (CarbonInterface $end): Collection {
            $dates = CarbonPeriod::create($this, $end)
                ->toArray();

            return collect($dates);
        });
    }

    private function configureUrls(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }

    private function seedPermissionsForTest(): void
    {
        if (app()->runningUnitTests()) {
            Event::listen(MigrationsEnded::class, function (MigrationsEnded $event) {
                // artisan('permissions:generate');
            });
        }
    }
}
