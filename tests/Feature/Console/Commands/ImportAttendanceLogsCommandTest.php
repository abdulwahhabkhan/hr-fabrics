<?php

use App\Console\Commands\Attendance\ImportAttendanceLogsCommand;
use App\Enums\AttendanceStatus;
use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    config([
        'services.attendance.base_url' => 'https://api.shehryar.me/api/public/attendance',
        'services.attendance.api_key' => 'att_test_key',
    ]);
});

test('imports punch logs into the attendance_logs table', function () {
    // Arrange
    $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/Attendance/attendance.json')), true);

    Http::fake([
        '*/logs*' => Http::response($fixture),
    ]);

    // Action
    artisan(ImportAttendanceLogsCommand::class)->assertSuccessful();

    // Assert
    assertDatabaseCount(AttendanceLog::class, 2);
    assertDatabaseHas(AttendanceLog::class, [
        'external_id' => '6a5bd63a5eb04baf64c3ea1b',
        'worker_name' => 'Shehryar',
        // punchedAt is 2026-07-18T19:38:32Z; app.timezone (Asia/Karachi, UTC+5) rolls it into the next day.
        'punch_date' => '2026-07-19',
        'punch_time' => '00:38:32',
        'method' => 'fingerprint',
    ]);
});

test('saves punch date/time converted to the configured app timezone', function () {
    // Arrange
    config(['app.timezone' => 'America/New_York']); // UTC-4 in July (DST)

    Http::fake([
        '*/logs*' => Http::response([
            'logs' => [
                [
                    'id' => 'tz-log',
                    'worker' => ['id' => 'w1', 'deviceUserId' => '1', 'name' => 'Ali'],
                    'punchedAt' => '2026-07-18T02:00:00.000Z',
                    'method' => 'card',
                    'rawVerify' => '4',
                    'sn' => 'TTQ5251000193',
                ],
            ],
        ]),
    ]);

    // Action
    artisan(ImportAttendanceLogsCommand::class)->assertSuccessful();

    // Assert
    assertDatabaseHas(AttendanceLog::class, [
        'external_id' => 'tz-log',
        'punch_date' => '2026-07-17',
        'punch_time' => '22:00:00',
    ]);
});

test('upserts on rerun instead of duplicating rows', function () {
    // Arrange: same external_id, but the worker's name changes between imports.
    Http::fake([
        '*/logs*' => Http::sequence()
            ->push([
                'logs' => [
                    [
                        'id' => 'log-1',
                        'worker' => ['id' => 'w1', 'deviceUserId' => '1', 'name' => 'Ali'],
                        'punchedAt' => '2026-07-18T08:00:00.000Z',
                        'method' => 'card',
                        'rawVerify' => '4',
                        'sn' => 'TTQ5251000193',
                    ],
                ],
            ])
            ->push([
                'logs' => [
                    [
                        'id' => 'log-1',
                        'worker' => ['id' => 'w1', 'deviceUserId' => '1', 'name' => 'Ali Renamed'],
                        'punchedAt' => '2026-07-18T08:00:00.000Z',
                        'method' => 'card',
                        'rawVerify' => '4',
                        'sn' => 'TTQ5251000193',
                    ],
                ],
            ]),
    ]);

    // Action
    artisan(ImportAttendanceLogsCommand::class)->assertSuccessful();
    assertDatabaseCount(AttendanceLog::class, 1);

    artisan(ImportAttendanceLogsCommand::class)->assertSuccessful();

    // Assert
    assertDatabaseCount(AttendanceLog::class, 1);
    assertDatabaseHas(AttendanceLog::class, [
        'external_id' => 'log-1',
        'worker_name' => 'Ali Renamed',
    ]);
});

test('projects imported punches into the attendances table', function () {
    // Arrange
    $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/Attendance/attendance.json')), true);

    Http::fake([
        '*/logs*' => Http::response($fixture),
    ]);

    // Action
    artisan(ImportAttendanceLogsCommand::class)
        ->expectsOutputToContain('Projected 1 attendance day')
        ->assertSuccessful();

    // Assert: both fixture punches belong to the same worker/day (Asia/Karachi rolls them into 2026-07-19).
    assertDatabaseCount(Attendance::class, 1);
    assertDatabaseHas(Attendance::class, [
        'worker_id' => '6a5b910c3e7469f650ccb75e',
        'date' => '2026-07-19',
        'in' => '00:02:43',
        'out' => '00:38:32',
        'status' => AttendanceStatus::Present->value,
    ]);
});

test('sets out on a later import when a second punch arrives', function () {
    // Arrange
    $morning = [
        'id' => 'log-1',
        'worker' => ['id' => 'w1', 'deviceUserId' => '1', 'name' => 'Ali'],
        'punchedAt' => '2026-07-18T04:00:00.000Z',
        'method' => 'card',
        'rawVerify' => '4',
        'sn' => 'TTQ5251000193',
    ];
    $evening = [
        'id' => 'log-2',
        'worker' => ['id' => 'w1', 'deviceUserId' => '1', 'name' => 'Ali'],
        'punchedAt' => '2026-07-18T13:00:00.000Z',
        'method' => 'card',
        'rawVerify' => '4',
        'sn' => 'TTQ5251000193',
    ];

    Http::fake([
        '*/logs*' => Http::sequence()
            ->push(['logs' => [$morning]])
            ->push(['logs' => [$morning, $evening]]),
    ]);

    // Action
    artisan(ImportAttendanceLogsCommand::class)->assertSuccessful();

    // Assert: only the morning punch so far, out is unset.
    assertDatabaseCount(Attendance::class, 1);
    assertDatabaseHas(Attendance::class, [
        'worker_id' => 'w1',
        'date' => '2026-07-18',
        'in' => '09:00:00',
        'out' => null,
    ]);

    // Action
    artisan(ImportAttendanceLogsCommand::class)->assertSuccessful();

    // Assert: the second import's evening punch fills out.
    assertDatabaseCount(Attendance::class, 1);
    assertDatabaseHas(Attendance::class, [
        'worker_id' => 'w1',
        'date' => '2026-07-18',
        'in' => '09:00:00',
        'out' => '18:00:00',
    ]);
});

test('preserves a manually assigned status across imports', function () {
    // Arrange
    Attendance::factory()->leave()->create([
        'worker_id' => '6a5b910c3e7469f650ccb75e',
        'date' => '2026-07-19',
    ]);

    $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/Attendance/attendance.json')), true);
    Http::fake([
        '*/logs*' => Http::response($fixture),
    ]);

    // Action
    artisan(ImportAttendanceLogsCommand::class)->assertSuccessful();

    // Assert: in/out refreshed from the punches, but the human-set status survives.
    assertDatabaseCount(Attendance::class, 1);
    assertDatabaseHas(Attendance::class, [
        'worker_id' => '6a5b910c3e7469f650ccb75e',
        'date' => '2026-07-19',
        'in' => '00:02:43',
        'out' => '00:38:32',
        'status' => AttendanceStatus::Leave->value,
    ]);
});

test('does not project punches whose payload has no worker id', function () {
    // Arrange
    Http::fake([
        '*/logs*' => Http::response([
            'logs' => [
                [
                    'id' => 'log-no-worker-id',
                    'worker' => ['deviceUserId' => '9', 'name' => 'Unregistered'],
                    'punchedAt' => '2026-07-18T08:00:00.000Z',
                    'method' => 'card',
                    'rawVerify' => '4',
                    'sn' => 'TTQ5251000193',
                ],
            ],
        ]),
    ]);

    // Action
    artisan(ImportAttendanceLogsCommand::class)
        ->expectsOutputToContain('1 punch log(s) had no worker id and were not projected')
        ->assertSuccessful();

    // Assert: the raw punch is still recorded, but nothing is projected.
    assertDatabaseCount(AttendanceLog::class, 1);
    assertDatabaseCount(Attendance::class, 0);
});

test('defaults --from to 5 hours ago when not provided', function () {
    // Arrange
    Http::fake([
        '*/logs*' => Http::response(['logs' => []]),
    ]);

    $before = CarbonImmutable::now('UTC')->subHours(5);

    // Action
    artisan(ImportAttendanceLogsCommand::class)->assertSuccessful();

    $after = CarbonImmutable::now('UTC')->subHours(5);

    // Assert
    Http::assertSent(function (Request $request) use ($before, $after): bool {
        $from = CarbonImmutable::createFromFormat('Y-m-d\TH:i:s.v\Z', $request['from'], 'UTC');

        return $from->between($before, $after);
    });
});

test('explicit --from option overrides the default', function () {
    // Arrange
    Http::fake([
        '*/logs*' => Http::response(['logs' => []]),
    ]);

    // Action
    artisan(ImportAttendanceLogsCommand::class, ['--from' => '2026-07-01T00:00:00Z'])->assertSuccessful();

    // Assert
    Http::assertSent(fn (Request $request): bool => $request['from'] === '2026-07-01T00:00:00.000Z');
});

test('reports when there is nothing to import', function () {
    // Arrange
    Http::fake([
        '*/logs*' => Http::response(['logs' => []]),
    ]);

    // Action
    artisan(ImportAttendanceLogsCommand::class)
        ->expectsOutputToContain('No punch logs found')
        ->assertSuccessful();

    // Assert
    assertDatabaseCount(AttendanceLog::class, 0);
    assertDatabaseCount(Attendance::class, 0);
});
