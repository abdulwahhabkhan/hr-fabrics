<?php

use App\Enums\AttendanceStatus;
use App\Facades\Permission as PermissionFacade;
use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceLog;
use App\Models\Attendance\Employee;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs($this->getAdmin());
    PermissionFacade::fake(['reports.attendance.*' => true]);
});

test('index defaults the date to today and marks employees with no record as absent', function () {
    // Arrange
    Employee::factory()->create(['id' => 'worker-1', 'name' => 'Shehryar']);

    // Action
    $response = $this->get(route('reports.attendance.register'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Attendance/AttendanceRegister')
        ->where('date', today()->toDateString())
        ->has('employees', 1)
        ->where('employees.0.id', 'worker-1')
        ->where('employees.0.name', 'Shehryar')
        ->where('employees.0.in', null)
        ->where('employees.0.out', null)
        ->where('employees.0.status', AttendanceStatus::Absent->value)
        ->where('employees.0.status_label', 'Absent')
    );
});

test('index reports in, out and status for an employee with a recorded attendance', function () {
    // Arrange
    Employee::factory()->create(['id' => 'worker-1', 'name' => 'Shehryar']);
    Attendance::factory()->present()->create([
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
        'in' => '09:02:43',
        'out' => '18:00:00',
    ]);

    // Action
    $response = $this->get(route('reports.attendance.register', ['date' => '2026-07-18']));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page
        ->where('date', '2026-07-18')
        ->where('employees.0.in', '09:02:43')
        ->where('employees.0.out', '18:00:00')
        ->where('employees.0.status', AttendanceStatus::Present->value)
        ->where('employees.0.status_label', 'Present')
    );
});

test('index preserves a manually assigned status such as leave or half day', function () {
    // Arrange
    Employee::factory()->create(['id' => 'worker-1', 'name' => 'Shehryar']);
    Attendance::factory()->leave()->create([
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
    ]);

    // Action
    $response = $this->get(route('reports.attendance.register', ['date' => '2026-07-18']));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page
        ->where('employees.0.status', AttendanceStatus::Leave->value)
        ->where('employees.0.status_label', 'Leave')
    );
});

test('index only matches attendance rows for the requested date', function () {
    // Arrange
    Employee::factory()->create(['id' => 'worker-1', 'name' => 'Shehryar']);
    Attendance::factory()->present()->create([
        'worker_id' => 'worker-1',
        'date' => '2026-07-17',
    ]);

    // Action
    $response = $this->get(route('reports.attendance.register', ['date' => '2026-07-18']));

    // Assert: no attendance row on 2026-07-18, so the employee reads absent.
    $response->assertInertia(fn (Assert $page) => $page
        ->where('employees.0.status', AttendanceStatus::Absent->value)
    );
});

test('index lists employees ordered by name', function () {
    // Arrange
    Employee::factory()->create(['id' => 'worker-2', 'name' => 'Zain']);
    Employee::factory()->create(['id' => 'worker-1', 'name' => 'Ahmad']);

    // Action
    $response = $this->get(route('reports.attendance.register'));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page
        ->has('employees', 2)
        ->where('employees.0.name', 'Ahmad')
        ->where('employees.1.name', 'Zain')
    );
});

test('index excludes soft deleted employees', function () {
    // Arrange
    $employee = Employee::factory()->create(['id' => 'worker-1', 'name' => 'Shehryar']);
    $employee->delete();

    // Action
    $response = $this->get(route('reports.attendance.register'));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page->has('employees', 0));
});

test('detail shows the employee attendance and every raw punch for that day', function () {
    // Arrange
    Employee::factory()->create(['id' => 'worker-1', 'name' => 'Shehryar']);
    Attendance::factory()->present()->create([
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
        'in' => '09:02:43',
        'out' => '18:00:00',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '09:02:43',
        'method' => 'fingerprint',
    ]);
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-18',
        'punch_time' => '18:00:00',
        'method' => 'fingerprint',
    ]);
    // Different day for the same worker, must not show up.
    AttendanceLog::factory()->forWorker('worker-1', 'Shehryar')->create([
        'punch_date' => '2026-07-17',
        'punch_time' => '09:00:00',
    ]);

    // Action
    $response = $this->get(route('reports.attendance.register.detail', [
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
    ]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Attendance/AttendanceRegisterDetail')
        ->where('employee.id', 'worker-1')
        ->where('employee.name', 'Shehryar')
        ->where('date', '2026-07-18')
        ->where('attendance.in', '09:02:43')
        ->where('attendance.out', '18:00:00')
        ->where('attendance.status', AttendanceStatus::Present->value)
        ->where('attendance.status_label', 'Present')
        ->has('logs', 2)
        ->where('logs.0.punch_time', '09:02:43')
        ->where('logs.1.punch_time', '18:00:00')
    );
});

test('detail reports absent with no punches when there is no attendance record', function () {
    // Arrange
    Employee::factory()->create(['id' => 'worker-1', 'name' => 'Shehryar']);

    // Action
    $response = $this->get(route('reports.attendance.register.detail', [
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
    ]));

    // Assert
    $response->assertInertia(fn (Assert $page) => $page
        ->where('attendance.in', null)
        ->where('attendance.out', null)
        ->where('attendance.status', AttendanceStatus::Absent->value)
        ->where('attendance.status_label', 'Absent')
        ->has('logs', 0)
    );
});

test('detail is available for a soft deleted employee', function () {
    // Arrange
    $employee = Employee::factory()->create(['id' => 'worker-1', 'name' => 'Shehryar']);
    $employee->delete();
    Attendance::factory()->present()->create(['worker_id' => 'worker-1', 'date' => '2026-07-18']);

    // Action
    $response = $this->get(route('reports.attendance.register.detail', [
        'worker_id' => 'worker-1',
        'date' => '2026-07-18',
    ]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->where('employee.name', 'Shehryar'));
});

test('detail returns 404 for an unknown employee', function () {
    // Action
    $response = $this->get(route('reports.attendance.register.detail', [
        'worker_id' => 'does-not-exist',
        'date' => '2026-07-18',
    ]));

    // Assert
    $response->assertNotFound();
});
