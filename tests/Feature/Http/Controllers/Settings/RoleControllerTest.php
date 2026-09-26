<?php

use App\Models\Permission;
use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $user = $this->getAdmin();
    $this->actingAs($user);
    $this->fakeHavePermission();
});

test('roles list loaded', function () {

    Role::factory()->count(10)->create();
    $response = $this->get(route('settings.roles.index'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Settings/Role/RoleIndex')
        ->has('roles')
        ->has('roles.data', 11)
        ->has('filters')
    );
});

test('role form loaded', function ($action) {
    if ($action === 'create') {
        $user = null;
        $route = route('settings.roles.create');
    } else {
        $role = Role::factory()->create();
        $route = route('settings.roles.edit', $role->id);
    }

    $response = $this->get($route);
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Settings/Role/RoleForm')
        ->has('permissions')
        ->has('role')
        ->has('rolePermission')
    );
})->with(['create', 'update']);

test('role form permission tree has unique node values when a section repeats across modules', function () {
    // Arrange
    Permission::factory()->create(['name' => 'sales.customers.index', 'section' => 'sales', 'module' => 'customers']);
    Permission::factory()->create(['name' => 'reports.customers.balance', 'section' => 'reports', 'module' => 'customers']);

    // Act
    $response = $this->get(route('settings.roles.create'));

    // Assert
    $response->assertOk();
    $response->assertInertia(function (Assert $page) {
        $values = [];
        $collect = function (array $nodes) use (&$collect, &$values): void {
            foreach ($nodes as $node) {
                $values[] = (string) $node['value'];
                $collect($node['children'] ?? []);
            }
        };
        $collect($page->toArray()['props']['permissions']);

        expect($values)->toContain('sales_customers_section', 'reports_customers_section')
            ->and($values)->toHaveSameSize(array_unique($values));
    });
});
