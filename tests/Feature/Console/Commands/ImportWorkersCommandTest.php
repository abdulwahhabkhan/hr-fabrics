<?php

use App\Console\Commands\Attendance\ImportWorkersCommand;
use App\Models\Attendance\Employee;
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

test('imports workers into the employees table', function () {
    // Arrange
    $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/Attendance/workers.json')), true);

    Http::fake([
        '*/workers*' => Http::response($fixture),
    ]);

    // Action
    artisan(ImportWorkersCommand::class)->assertSuccessful();

    // Assert
    assertDatabaseCount(Employee::class, 3);
    assertDatabaseHas(Employee::class, [
        'id' => '6a5b910c3e7469f650ccb75e',
        'name' => 'Shehryar',
    ]);
});

test('upserts on rerun instead of duplicating rows', function () {
    // Arrange: same id, but the worker's name changes between imports.
    Http::fake([
        '*/workers*' => Http::sequence()
            ->push([
                [
                    'id' => 'w1', 'deviceUserId' => '1', 'name' => 'Ali', 'nameSource' => 'device',
                    'lastMethod' => 'fingerprint',
                ],
            ])
            ->push([
                [
                    'id' => 'w1', 'deviceUserId' => '1', 'name' => 'Ali Renamed', 'nameSource' => 'device',
                    'lastMethod' => 'fingerprint',
                ],
            ]),
    ]);

    // Action
    artisan(ImportWorkersCommand::class)->assertSuccessful();
    assertDatabaseCount(Employee::class, 1);

    artisan(ImportWorkersCommand::class)->assertSuccessful();

    // Assert
    assertDatabaseCount(Employee::class, 1);
    assertDatabaseHas(Employee::class, [
        'id' => 'w1',
        'name' => 'Ali Renamed',
    ]);
});

test('soft-deleted employees still receive updates on re-import', function () {
    // Arrange
    $employee = Employee::factory()->create(['id' => 'w1', 'name' => 'Ali']);
    $employee->delete();

    Http::fake([
        '*/workers*' => Http::response([
            [
                'id' => 'w1', 'deviceUserId' => '1', 'name' => 'Ali Renamed', 'nameSource' => 'device',
                'lastMethod' => 'fingerprint',
            ],
        ]),
    ]);

    // Action
    artisan(ImportWorkersCommand::class)->assertSuccessful();

    // Assert: name refreshed, but row stays soft-deleted.
    assertDatabaseCount(Employee::class, 1);
    assertDatabaseHas(Employee::class, [
        'id' => 'w1',
        'name' => 'Ali Renamed',
    ]);
    expect($employee->fresh()->trashed())->toBeTrue();
});

test('reports when there is nothing to import', function () {
    // Arrange
    Http::fake([
        '*/workers*' => Http::response([]),
    ]);

    // Action
    artisan(ImportWorkersCommand::class)
        ->expectsOutputToContain('No workers found')
        ->assertSuccessful();

    // Assert
    assertDatabaseCount(Employee::class, 0);
});
