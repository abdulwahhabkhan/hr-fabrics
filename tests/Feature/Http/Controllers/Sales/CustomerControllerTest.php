<?php

use App\Enums\AccountType;
use App\Enums\DiscountType;
use App\Facades\Permission;
use App\Models\Accounts\Account;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\post;

beforeEach(function () {
    $user = $this->getAdmin();
    $this->actingAs($user);

    Permission::fake(['sales.customers.*' => true]);
});

test('customer index page can be rendered', function () {
    // Arrange
    Account::factory()->customer()->count(5)->create();

    // Act
    $response = $this->get(route('sales.customers.index'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Customers/CustomerIndex')
        ->has('customers.data', 5)
        ->has('filters')
    );
});

test('customer index page can filter results', function () {
    // Arrange
    Account::factory()->customer()->count(3)->create();
    Account::factory()->customer()->create(['name' => 'Test Customer']);

    // Act
    $response = $this->get(route('sales.customers.index', ['search' => 'Test']));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Customers/CustomerIndex')
        ->has('customers.data', 1)
    );
});

test('customer index page can filter by status', function () {
    // Arrange
    Account::factory()->customer()->count(2)->create(['suspended' => false]);
    Account::factory()->customer()->create(['suspended' => true]);

    // Act
    $response = $this->get(route('sales.customers.index', ['status' => 'suspended']));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Customers/CustomerIndex')
        ->has('customers.data', 1)
    );
});

test('customer index page can filter by credit', function () {
    // Arrange
    Account::factory()->customer()->count(2)->create(['credit' => false]);
    Account::factory()->customer()->create(['credit' => true]);

    // Act
    $response = $this->get(route('sales.customers.index', ['credit' => '1']));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Customers/CustomerIndex')
        ->has('customers.data', 1)
    );
});

test('customer create page can be rendered', function () {
    // Act
    $response = $this->get(route('sales.customers.create'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Customers/CustomerForm')
        ->where('customer', null)
        ->has('agents')
        ->has('brands')
        ->has('cities')
        ->has('discountTypes')
    );
});

test('customer edit page can be rendered', function () {
    // Arrange
    $customer = Account::factory()->customer()->create();

    // Act
    $response = $this->get(route('sales.customers.edit', $customer));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Sales/Customers/CustomerForm')
        ->has('customer')
        ->has('agents')
        ->has('brands')
        ->has('cities')
        ->has('discountTypes')
    );
});

test('customer can be created', function () {
    // Arrange
    $address = [
        'address' => 'Test Address',
        'region' => 'Test Region',
        'city' => 'Test City',
    ];

    $data = [
        'name' => 'New Customer',
        'name_urdu' => 'نیا گاہک',
        'address' => $address,
        'phone' => '1234567890',
        'email' => 'test@example.com',
        'discount' => 10,
        'discount_type' => DiscountType::PercentageOnTotal->value,
        'limit' => 5000,
        'credit' => 1,
    ];

    // Act

    $response = post(route('sales.customers.store'), $data);

    // Assert
    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.customers.index'))
        ->assertSessionHas('success', 'Customer created Successfully');

    // Check database
    unset($data['address']);
    $data['address'] = json_encode($address);
    $data['type'] = AccountType::Customer;
    $this->assertDatabaseHas(Account::class, [
        'name' => 'New Customer',
        'email' => 'test@example.com',
    ]);
});

test('customer can be updated', function () {
    // Arrange
    $customer = Account::factory()->customer()->create();

    $address = [
        'address' => 'Updated Address',
        'region' => 'Updated Region',
        'city' => 'Updated City',
    ];

    $data = [
        'name' => 'Updated Customer',
        'name_urdu' => 'اپڈیٹ شدہ گاہک',
        'address' => $address,
        'phone' => '9876543210',
        'email' => 'updated@example.com',
        'discount' => 15,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'limit' => 10000,
        'credit' => 1,
    ];

    // Act
    $response = $this->put(route('sales.customers.update', $customer), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('sales.customers.index'));
    $response->assertSessionHas('success', 'Customer updated Successfully');

    // Check database
    $this->assertDatabaseHas(Account::class, [
        'id' => $customer->id,
        'name' => 'Updated Customer',
        'email' => 'updated@example.com',
    ]);
});

test('customer without agent and with discount', function () {
    // Arrange
    $address = [
        'address' => fake()->streetAddress(),
        'region' => fake()->postcode(),
        'city' => fake()->city(),
    ];
    $body = [
        'name' => fake()->name(),
        'name_urdu' => fake('ar_EG')->name,
        'address' => $address,
        'phone' => fake()->phoneNumber(),
        'email' => fake()->email(),
        'discount' => 10,
        'discount_type' => DiscountType::PercentageOnTotal->value,
        'limit' => 10,
        'credit' => 1,
    ];

    // Action
    $response = $this->post(route('sales.customers.store'), $body);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('sales.customers.index'));
    unset($body['address']);
    $body['address'] = json_encode($address);
    $this->assertDatabaseCount(Account::class, 1);
    $this->assertDatabaseHas(Account::class, $body);

});

test('customer with agent but without discount', function () {
    // Arrange
    $agent = Account::factory()->agent()->create();
    $commission_rates = ['brand_1' => 10, 'brand_2' => 2.4];
    $address = [
        'address' => fake()->streetAddress(),
        'region' => fake()->postcode(),
        'city' => fake()->city(),
    ];
    $body = [
        'name' => fake()->name(),
        'name_urdu' => fake('ar_EG')->name,
        'address' => $address,
        'agent' => $agent,
        'commission_rate' => $commission_rates,
        'phone' => fake()->phoneNumber(),
        'email' => fake()->email(),
        'discount' => 0,
        'discount_type' => DiscountType::PercentageOnTotal->value,
        'limit' => 0,
        'credit' => 0,
    ];

    // Action
    $response = $this->post(route('sales.customers.store'), $body);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('sales.customers.index'));
    unset($body['address']);
    unset($body['agent']);
    unset($body['commission_rate']);
    $body['agent_id'] = $agent->id;
    $body['address'] = json_encode($address);
    $body['commission_rate'] = json_encode($commission_rates);
    $this->assertDatabaseCount(Account::class, 2);
    $this->assertDatabaseHas(Account::class, $body);

});

test('update customer without agent but with discount', function () {
    $customer = Account::factory()->customer()->create();
    $address = $customer->address;
    $body = Arr::except($customer->toArray(), ['name', 'created_by', 'updated_at']);
    $body['name'] = fake()->name();
    $body['discount'] = fake()->randomNumber(2);
    $body['discount_type'] = DiscountType::FixedPerMeter->value;

    // Act
    $response = $this->put(route('sales.customers.update',
        $customer->id), $body);

    // Assert
    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect(route('sales.customers.index'));
    unset($body['address']);
    $body['address'] = json_encode($address);
    $this->assertDatabaseCount(Account::class, 1);
    $this->assertDatabaseHas(Account::class, $body);

});

test('update customer with agent without discount', function () {
    $agent = Account::factory()->agent()->create();
    $customer = Account::factory()->customer()->create();
    $commission_rates = ['brand_1' => 10, 'brand_2' => 2.4];
    $address = [
        'address' => fake()->streetAddress(),
        'region' => fake()->postcode(),
        'city' => fake()->city(),
    ];
    $body = [
        'name' => fake()->name(),
        'name_urdu' => fake('ar_EG')->name,
        'address' => $address,
        'agent' => $agent,
        'commission_rate' => $commission_rates,
        'phone' => fake()->phoneNumber(),
        'email' => fake()->email(),
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'credit' => 0,
        'limit' => 0,
    ];

    // Act
    $response = $this->put(route('sales.customers.update',
        $customer->id), $body);

    // Assert
    $response->assertSessionDoesntHaveErrors();
    $response->assertRedirect(route('sales.customers.index'));
    unset($body['address']);
    unset($body['agent']);
    unset($body['commission_rate']);
    $body['agent_id'] = $agent->id;
    $body['address'] = json_encode($address);
    $body['commission_rate'] = json_encode($commission_rates);
    $this->assertDatabaseCount(Account::class, 2);
    $this->assertDatabaseHas(Account::class, $body);

});

test('customer credit limit validation', function () {
    // Arrange
    $address = [
        'address' => fake()->streetAddress(),
        'region' => fake()->postcode(),
        'city' => fake()->city(),
    ];

    $data = [
        'name' => fake()->name(),
        'name_urdu' => fake('ar_EG')->name,
        'address' => $address,
        'phone' => fake()->phoneNumber(),
        'email' => fake()->email(),
        'discount' => 0,
        // 'limit' => 0,
        'credit' => 1,
    ];

    // Act
    $response = $this->post(route('sales.customers.store'), $data);

    // Assert
    $response->assertSessionHasErrors('limit');
    $response->assertFound();
    $this->assertDatabaseCount(Account::class, 0);
});

test('create customer with credit limit', function () {
    // Arrange
    $address = [
        'address' => fake()->streetAddress(),
        'region' => fake()->postcode(),
        'city' => fake()->city(),
    ];

    $data = [
        'name' => fake()->name(),
        'name_urdu' => fake('ar_EG')->name,
        'address' => $address,
        'phone' => fake()->phoneNumber(),
        'email' => fake()->email(),
        'discount' => 0,
        'discount_type' => DiscountType::FixedPerMeter->value,
        'limit' => fake()->randomNumber(3),
        'credit' => 1,
    ];

    // Act
    $response = $this->post(route('sales.customers.store'), $data);
    // Assert
    // $response->assertOk();
    $response->assertSessionDoesntHaveErrors();
    unset($data['address']);
    $data['address'] = json_encode($address);
    $this->assertDatabaseHas(Account::class, $data);
});

test('customer can be suspended', function () {
    // Arrange
    $customer = Account::factory()->customer()->create([
        'suspended' => false,
        'suspended_at' => null,
    ]);

    // Act
    $response = $this->post(route('sales.customers.suspend', $customer));

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('sales.customers.index'));
    $response->assertSessionHas('success', 'Customer suspended successfully');

    // Check database
    $this->assertDatabaseHas(Account::class, [
        'id' => $customer->id,
        'suspended' => true,
    ]);

    $customer->refresh();
    expect($customer->suspended)->toBeTrue()
        ->and($customer->suspended_at)->not->toBeNull();
});

test('already suspended customer cannot be suspended again', function () {
    // Arrange
    $customer = Account::factory()->customer()->create([
        'suspended' => true,
        'suspended_at' => now(),
    ]);

    // Act
    $response = $this->post(route('sales.customers.suspend', $customer));

    // Assert
    $response->assertRedirect(route('sales.customers.index'));
    $response->assertSessionHas('error', 'Customer is already suspended');
});

test('suspended customer can be activated', function () {
    // Arrange
    $customer = Account::factory()->customer()->create([
        'suspended' => true,
        'suspended_at' => now(),
    ]);

    // Act
    $response = $this->post(route('sales.customers.activate', $customer));

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('sales.customers.index'));
    $response->assertSessionHas('success', 'Customer activated successfully');

    // Check database
    $this->assertDatabaseHas(Account::class, [
        'id' => $customer->id,
        'suspended' => false,
        'suspended_at' => null,
    ]);

    $customer->refresh();
    expect($customer->suspended)->toBeFalse()
        ->and($customer->suspended_at)->toBeNull();
});

test('already activated customer cannot be activated again', function () {
    // Arrange
    $customer = Account::factory()->customer()->create([
        'suspended' => false,
        'suspended_at' => null,
    ]);

    // Act
    $response = $this->post(route('sales.customers.activate', $customer));

    // Assert
    $response->assertRedirect(route('sales.customers.index'));
    $response->assertSessionHas('error', 'Customer is already activated');
});
