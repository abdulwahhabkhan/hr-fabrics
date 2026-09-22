<?php

use App\Enums\AttendanceStatus;
use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceLog;
use App\Models\Attendance\Employee;
use App\Services\Attendance\AttendanceProjector;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

test('projects a single punch as in with a null out', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '19:02:43',
    ]);

    // Action
    $projected = app(AttendanceProjector::class)->project(['worker-1'], ['2026-07-18']);

    // Assert
    expect($projected)->toBe(1);
    assertDatabaseCount(Attendance::class, 1);
    assertDatabaseHas(Attendance::class, [
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
        'in' => '19:02:43',
        'out' => null,
        'status' => AttendanceStatus::Present->value,
    ]);
});

test('projects the earliest punch as in and the latest as out', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '19:38:32',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '10:00:00',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '14:00:00',
    ]);

    // Action
    app(AttendanceProjector::class)->project(['worker-1'], ['2026-07-18']);

    // Assert
    assertDatabaseHas(Attendance::class, [
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
        'in' => '10:00:00',
        'out' => '19:38:32',
    ]);
});

test('does not overwrite a manually set status on reprojection', function () {
    // Arrange
    Attendance::factory()->halfDay()->create([
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
        'in' => '09:00:00',
        'out' => '13:00:00',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '08:55:00',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '18:00:00',
    ]);

    // Action
    app(AttendanceProjector::class)->project(['worker-1'], ['2026-07-18']);

    // Assert
    assertDatabaseCount(Attendance::class, 1);
    assertDatabaseHas(Attendance::class, [
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
        'in' => '08:55:00',
        'out' => '18:00:00',
        'status' => AttendanceStatus::HalfDay->value,
    ]);
});

test('sets out when a later punch arrives on a subsequent projection', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '09:00:00',
    ]);
    $projector = app(AttendanceProjector::class);
    $projector->project(['worker-1'], ['2026-07-18']);

    // Action
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '17:30:00',
    ]);
    $projector->project(['worker-1'], ['2026-07-18']);

    // Assert
    assertDatabaseCount(Attendance::class, 1);
    assertDatabaseHas(Attendance::class, [
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
        'in' => '09:00:00',
        'out' => '17:30:00',
    ]);
});

test('only projects the requested workers and dates', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create(['punch_date' => '2026-07-18']);
    AttendanceLog::factory()->forWorker('worker-2', 'Ali')->create(['punch_date' => '2026-07-18']);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create(['punch_date' => '2026-07-19']);

    // Action
    app(AttendanceProjector::class)->project(['worker-1'], ['2026-07-18']);

    // Assert
    assertDatabaseCount(Attendance::class, 1);
    assertDatabaseHas(Attendance::class, ['worker_id' => 'worker-1', 'date' => '2026-07-18']);
});

test('skips logs whose info payload has no worker id', function () {
    // Arrange
    AttendanceLog::factory()->create([
        'worker_name' => 'Ghost',
        'punch_date' => '2026-07-18',
        'info' => ['id' => 'log-1', 'punchedAt' => '2026-07-18T09:00:00.000Z'],
    ]);

    // Action
    $projected = app(AttendanceProjector::class)->project([], ['2026-07-18']);

    // Assert
    expect($projected)->toBe(0);
    assertDatabaseCount(Attendance::class, 0);
});

test('projects punches for a worker with no employees row', function () {
    // Arrange: no Employee row exists for 'worker-orphan'.
    AttendanceLog::factory()->forWorker('worker-orphan', 'Unregistered')->create(['punch_date' => '2026-07-18']);

    // Action
    app(AttendanceProjector::class)->project(['worker-orphan'], ['2026-07-18']);

    // Assert
    assertDatabaseHas(Attendance::class, ['worker_id' => 'worker-orphan', 'date' => '2026-07-18']);
});

test('projects punches for a soft deleted employee', function () {
    // Arrange
    $employee = Employee::factory()->create(['id' => 'worker-1']);
    $employee->delete();
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create(['punch_date' => '2026-07-18']);

    // Action
    app(AttendanceProjector::class)->project(['worker-1'], ['2026-07-18']);

    // Assert
    $attendance = Attendance::query()->where('worker_id', 'worker-1')->firstOrFail();
    expect($attendance->employee)->toBeNull();
});

test('writes nothing when given no workers or dates', function () {
    // Action
    $projected = app(AttendanceProjector::class)->project([], []);

    // Assert
    expect($projected)->toBe(0);
    assertDatabaseCount(Attendance::class, 0);
});

test('is idempotent across repeated runs', function () {
    // Arrange: tests/Pest.php freezes time globally.
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '09:00:00',
    ]);
    $projector = app(AttendanceProjector::class);
    $projector->project(['worker-1'], ['2026-07-18']);
    $createdAt = Attendance::query()->firstOrFail()->created_at;

    // Action
    $projector->project(['worker-1'], ['2026-07-18']);

    // Assert
    assertDatabaseCount(Attendance::class, 1);
    expect(Attendance::query()->firstOrFail()->created_at)->toEqual($createdAt);
});
