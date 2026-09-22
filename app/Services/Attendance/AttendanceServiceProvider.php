<?php

namespace App\Services\Attendance;

use Illuminate\Support\ServiceProvider;

class AttendanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AttendanceImporter::class, fn (): AttendanceImporter => new AttendanceImporter(
            baseUrl: (string) config('services.attendance.base_url'),
            apiKey: config('services.attendance.api_key'),
        ));
    }

    public function boot(): void {}
}
