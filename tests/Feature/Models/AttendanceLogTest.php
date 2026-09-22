<?php

use App\Models\Attendance\AttendanceLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

test('factory persists a row with correct casts', function () {
    // Arrange
    $log = AttendanceLog::factory()->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '19:38:32',
    ]);

    // Assert
    expect($log->punch_date)->toBeInstanceOf(CarbonImmutable::class)
        ->and($log->punch_date->toDateString())->toBe('2026-07-18')
        ->and($log->punch_time)->toBe('19:38:32');
});

test('worker id is derived from the info payload', function () {
    // Arrange
    $log = AttendanceLog::factory()->create();

    // Action
    $log->refresh();

    // Assert
    expect($log->worker_id)->toBe($log->info['worker']['id']);
});

test('worker id is null when the info payload has no worker id', function () {
    // Arrange
    $log = AttendanceLog::factory()->create([
        'info' => ['id' => 'log-without-worker', 'punchedAt' => '2026-07-18T19:38:32.000Z'],
    ]);

    // Action
    $log->refresh();

    // Assert
    expect($log->worker_id)->toBeNull();
});

test('for worker keeps the same worker id across punches', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create();
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create();

    // Action
    $logs = AttendanceLog::query()->get()->each->refresh();

    // Assert
    expect($logs->pluck('worker_id')->unique()->all())->toBe(['worker-1']);
});

test('daily report collapses multiple punches for one worker into one row', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '19:02:43',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '19:38:32',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '10:00:00',
    ]);

    // Action
    $rows = AttendanceLog::query()->dailyReport([])->get();

    // Assert
    expect($rows)->toHaveCount(1);
    expect($rows->first())
        ->worker_name->toBe('Shehryar')
        ->first_punch->toBe('10:00:00')
        ->last_punch->toBe('19:38:32')
        ->punch_count->toBe(3);
});

test('daily report only records first punch when worker has a single entry', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '19:02:43',
    ]);

    // Action
    $rows = AttendanceLog::query()->dailyReport([])->get();

    // Assert
    expect($rows)->toHaveCount(1);
    expect($rows->first())
        ->first_punch->toBe('19:02:43')
        ->last_punch->toBeNull()
        ->punch_count->toBe(1);
});

test('daily report separates different workers and different days', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create(['punch_date' => '2026-07-18']);
    AttendanceLog::factory()->forWorker('worker-2', 'Ali')->create(['punch_date' => '2026-07-18']);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create(['punch_date' => '2026-07-19']);

    // Action
    $rows = AttendanceLog::query()->dailyReport([])->get();

    // Assert
    expect($rows)->toHaveCount(3);
});

test('daily report filters by date range and worker name', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create(['punch_date' => '2026-07-10']);
    AttendanceLog::factory()->forWorker('worker-2', 'Ali')->create(['punch_date' => '2026-07-18']);

    // Action
    $rows = AttendanceLog::query()->dailyReport([
        'start_date' => '2026-07-15',
        'end_date' => '2026-07-20',
    ])->get();

    // Assert
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->worker_name)->toBe('Ali');

    // Action
    $rows = AttendanceLog::query()->dailyReport(['worker_name' => 'sheh'])->get();

    // Assert
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->worker_name)->toBe('Shehryar');
});

test('daily report keeps adjacent utc days separate', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '23:59:59',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-19',
        'punch_time' => '00:00:01',
    ]);

    // Action
    $rows = AttendanceLog::query()->dailyReport([])->get();

    // Assert
    expect($rows)->toHaveCount(2);
});

test('external id must be unique', function () {
    // Arrange
    AttendanceLog::factory()->create(['external_id' => 'log-1']);

    // Action / Assert
    expect(fn () => AttendanceLog::factory()->create(['external_id' => 'log-1']))
        ->toThrow(QueryException::class);
});
