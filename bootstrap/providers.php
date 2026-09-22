<?php

use App\Providers\AppServiceProvider;
use App\Providers\EssentialsServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\ModelServiceProvider;
use App\Providers\SudoServiceProvider;
use App\Services\Attendance\AttendanceServiceProvider;

return [
    AppServiceProvider::class,
    EssentialsServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    ModelServiceProvider::class,
    SudoServiceProvider::class,
    AttendanceServiceProvider::class,
];
