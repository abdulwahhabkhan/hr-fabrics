<?php

use App\Models\Model;
use App\Services\Attendance\AttendanceServiceProvider;

arch()->preset()->php();
arch()->preset()->laravel()->ignoring([
    Model::class,
    AttendanceServiceProvider::class,
    'App\Http\Controllers',
]);
arch()->preset()->security()->ignoring([
    'Database',
]);
