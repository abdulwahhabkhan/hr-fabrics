<?php

use App\Console\Commands\Accounts\CalculateCustomerBalance;
use App\Console\Commands\Attendance\ImportAttendanceLogsCommand;
use App\Console\Commands\Attendance\ImportWorkersCommand;
use App\Console\Commands\PruneDeletedFilesCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
$email = 'abdulwahhabkhan@hotmail.com';
// Schedule::command('inspire')->hourly();
Schedule::command('queue:work database --stop-when-empty')->everyMinute()->withoutOverlapping(15);
Schedule::command(CalculateCustomerBalance::class)
    ->daily()
    ->withoutOverlapping(500)
    ->emailOutputOnFailure($email);

Schedule::command(ImportAttendanceLogsCommand::class)
    ->hourly()
    ->emailOutputOnFailure($email);

Schedule::command(ImportWorkersCommand::class)
    ->daily()
    ->emailOutputOnFailure($email);

Schedule::command(PruneDeletedFilesCommand::class)
    ->daily()
    ->emailOutputOnFailure($email);
