<?php

use App\Models\Attendance\AttendanceLog;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs($this->getAdmin());
    $this->fakeHavePermission();
});

test('index defaults the date filter to today and collapses punches per worker', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => today()->toDateString(),
        'punch_time' => '09:00:00',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => today()->toDateString(),
        'punch_time' => '18:00:00',
    ]);
    AttendanceLog::factory()->forWorker('worker-2', 'Ali')->create([
        'punch_date' => today()->subDay()->toDateString(),
    ]);

    // Action
    $response = $this->get(route('reports.attendance.index'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Attendance/AttendanceReport')
        ->where('filters.start_date', today()->toDateString())
        ->where('filters.end_date', today()->toDateString())
        ->has('rows.data', 1)
        ->where('rows.data.0.worker_name', 'Shehryar')
        ->where('rows.data.0.first_punch', '09:00:00')
        ->where('rows.data.0.last_punch', '18:00:00')
    );
});

test('index filters by employee name and date range', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create(['punch_date' => '2026-07-10']);
    AttendanceLog::factory()->forWorker('worker-2', 'Ali')->create(['punch_date' => '2026-07-18']);

    // Action
    $response = $this->get(route('reports.attendance.index', [
        'start_date' => '2026-07-15',
        'end_date' => '2026-07-20',
    ]));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page
        ->has('rows.data', 1)
        ->where('rows.data.0.worker_name', 'Ali')
    );

    // Action
    $response = $this->get(route('reports.attendance.index', [
        'worker_name' => 'sheh',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page
        ->has('rows.data', 1)
        ->where('rows.data.0.worker_name', 'Shehryar')
    );
});

test('detail shows every punch for the given employee and date, ordered by time', function () {
    // Arrange
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '18:00:00',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '09:00:00',
    ]);
    AttendanceLog::factory()->forWorker('worker-2', 'Ali')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '10:00:00',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-19',
        'punch_time' => '09:00:00',
    ]);

    // Action
    $response = $this->get(route('reports.attendance.detail', [
        'worker_name' => 'Shehryar',
        'punch_date' => '2026-07-18',
    ]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Attendance/AttendanceReportDetail')
        ->has('punches', 2)
        ->where('punches.0.punch_time', '09:00:00')
        ->where('punches.1.punch_time', '18:00:00')
    );
});
