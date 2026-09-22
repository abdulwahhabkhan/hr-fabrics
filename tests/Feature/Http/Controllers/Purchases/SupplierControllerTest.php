<?php

use App\Enums\AccountType;
use App\Models\Accounts\Account;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
test('list suppliers', function (): void {
    // Arrange
    $user = $this->getAdmin();
    Account::factory()->supplier()->count(10)->create();

    // Action
    $response = $this->actingAs($user)->get(route('purchases.suppliers.index'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/Suppliers/SupplierIndex')
        ->has('rows.data', 10)
    );
});

test('filter list supplier', function (): void {
    // Arrange
    $user = $this->getAdmin();
    Account::factory()->supplier()->count(2)->create();
    $filter = 'supplier';
    Account::factory()->supplier()->count(1)->create(['name' => $filter]);

    // Action
    $response = $this->actingAs($user)->get(route('purchases.suppliers.index', ['search='.$filter]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/Suppliers/SupplierIndex')
        ->has('rows.data')
    );
});

test('add supplier', function (): void {
    // Arrange
    $user = $this->getAdmin();
    $address = ['city' => 'testcity', 'address' => 'Address1, 2 and 3'];
    $body = ['name' => 'supplier name', 'address' => $address];

    // Action
    $response = $this->actingAs($user)->post(route('purchases.suppliers.store'), $body);

    // Assert
    $response
        ->assertJsonMissingValidationErrors()
        ->assertCreated();
    unset($body['address']);
    $body['address'] = json_encode($address);
    $body['type'] = AccountType::Supplier;
    $this->assertDatabaseCount(Account::class, 1);
    $this->assertDatabaseHas(Account::class, $body);
});

test('update supplier', function (): void {
    // Arrange
    $user = $this->getAdmin();
    $account = Account::factory()->supplier()->create();
    $address = ['city' => 'testcity', 'address' => 'Address1, 2 and 3'];
    $body = ['name' => 'supplier name', 'address' => $address];

    // Action
    $response = $this->actingAs($user)->putJson(route('purchases.suppliers.update', $account->id), $body);

    // Assert
    $response
        ->assertJsonMissingValidationErrors()
        ->assertAccepted();
    unset($body['address']);
    $body['address'] = json_encode($address);
    $this->assertDatabaseCount(Account::class, 1);
    $this->assertDatabaseHas(Account::class, $body);
});

test('edit supplier returns detail', function (): void {
    // Arrange
    $user = $this->getAdmin();
    $account = Account::factory()->supplier()->create();

    // Action
    $response = $this->actingAs($user)->getJson(route('purchases.suppliers.edit', $account->id));

    // Assert
    $response->assertOk();
    $response->assertJson([
        'detail' => $account->toArray(),
    ]);
});

test('add supplier requires name', function (): void {
    // Arrange
    $user = $this->getAdmin();
    $body = ['address' => ['city' => 'testcity']];

    // Action
    $response = $this->actingAs($user)->postJson(route('purchases.suppliers.store'), $body);

    // Assert
    $response->assertJsonValidationErrors('name');
    $this->assertDatabaseCount(Account::class, 0);
});

test('add supplier name must be unique', function (): void {
    // Arrange
    $user = $this->getAdmin();
    $existing = Account::factory()->supplier()->create(['name' => 'duplicate name']);
    $body = ['name' => 'duplicate name', 'address' => ['city' => 'testcity']];

    // Action
    $response = $this->actingAs($user)->postJson(route('purchases.suppliers.store'), $body);

    // Assert
    $response->assertJsonValidationErrors('name');
    $this->assertDatabaseCount(Account::class, 1);
});

test('update supplier requires name', function (): void {
    // Arrange
    $user = $this->getAdmin();
    $account = Account::factory()->supplier()->create();
    $body = ['address' => ['city' => 'testcity']];

    // Action
    $response = $this->actingAs($user)->putJson(route('purchases.suppliers.update', $account->id), $body);

    // Assert
    $response->assertJsonValidationErrors('name');
});

test('update supplier name must be unique among other suppliers', function (): void {
    // Arrange
    $user = $this->getAdmin();
    Account::factory()->supplier()->create(['name' => 'taken name']);
    $account = Account::factory()->supplier()->create(['name' => 'original name']);
    $body = ['name' => 'taken name'];

    // Action
    $response = $this->actingAs($user)->putJson(route('purchases.suppliers.update', $account->id), $body);

    // Assert
    $response->assertJsonValidationErrors('name');
    $this->assertDatabaseHas(Account::class, ['id' => $account->id, 'name' => 'original name']);
});

test('update supplier keeps its own name unchanged', function (): void {
    // Arrange
    $user = $this->getAdmin();
    $account = Account::factory()->supplier()->create(['name' => 'same name']);
    $body = ['name' => 'same name'];

    // Action
    $response = $this->actingAs($user)->putJson(route('purchases.suppliers.update', $account->id), $body);

    // Assert
    $response->assertJsonMissingValidationErrors()->assertAccepted();
});
