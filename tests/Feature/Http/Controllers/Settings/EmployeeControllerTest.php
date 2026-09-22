<?php

use App\Models\Attendance\Employee;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
    $user = $this->getAdmin();
    $this->actingAs($user);
    $this->fakeHavePermission();
});

test('employees list loaded', function () {
    Employee::factory()->count(10)->create();

    $response = $this->get(route('settings.employees.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Settings/Employee/EmployeeIndex')
        ->has('employees')
        ->has('employees.data', 10)
        ->has('filters')
    );
});

test('employees list filtered by name', function () {
    Employee::factory()->create(['name' => 'Shehryar']);
    Employee::factory()->create(['name' => 'Ahmad']);

    $response = $this->get(route('settings.employees.index', ['search' => 'sheh']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Settings/Employee/EmployeeIndex')
        ->has('employees.data', 1)
        ->where('employees.data.0.name', 'Shehryar')
    );
});

test('employee marked as deleted', function () {
    $employee = Employee::factory()->create();

    $response = $this->delete(route('settings.employees.destroy', $employee));

    $response->assertRedirect(route('settings.employees.index'));
    assertSoftDeleted($employee);
});

test('deleted employees are excluded from the listing', function () {
    $employee = Employee::factory()->create();
    $employee->delete();

    $response = $this->get(route('settings.employees.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('employees.data', 0)
    );
    assertDatabaseCount(Employee::class, 1);
});
