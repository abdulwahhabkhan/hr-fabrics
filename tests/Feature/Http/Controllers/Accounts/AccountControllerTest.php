<?php

use App\Enums\AccountType;
use App\Models\Accounts\Account;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $user = $this->getAdmin();
    $this->actingAs($user);
    $this->fakeHavePermission();
});

test('account index page can be rendered', function () {
    // Arrange
    Account::factory()->count(3)->create(['type' => AccountType::Expenses]);

    // Act
    $response = $this->get(route('accounts.accounts.index'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Accounts/AccountIndex')
        ->has('accounts.data', 3)
        ->has('filters')
    );
});

test('account index page can filter by type', function () {
    // Arrange
    Account::factory()->expense()->count(2)->create();
    Account::factory()->bank()->create();

    // Act
    $response = $this->get(route('accounts.accounts.index', ['type' => AccountType::Bank->value]));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('accounts.data', 1)
        ->where('accounts.data.0.type', AccountType::Bank->value)
        ->where('filters.type', AccountType::Bank->value)
    );
});

test('account create page can be rendered', function () {
    // Act
    $response = $this->get(route('accounts.accounts.create'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Accounts/AccountForm')
        ->where('account', null)
        ->has('expense_accounts')
    );
});

test('account edit page can be rendered', function () {
    // Arrange
    $account = Account::factory()->create(['type' => AccountType::Expenses]);

    // Act
    $response = $this->get(route('accounts.accounts.edit', $account));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/Accounts/AccountForm')
        ->has('account')
        ->has('expense_accounts')
    );
});

test('account can be created', function () {
    // Arrange
    $data = [
        'name' => 'Test Expense Account',
        'type' => AccountType::Expenses->value,
        'phone' => '1234567890',
        'email' => 'expense@example.com',
    ];

    // Act
    $response = $this->post(route('accounts.accounts.store'), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('accounts.accounts.index'));
    $response->assertSessionHas('success', 'Account created Successfully');

    $this->assertDatabaseHas(Account::class, [
        'name' => 'Test Expense Account',
        'type' => AccountType::Expenses->value,
        'email' => 'expense@example.com',
        'expense_account' => 0,
        'created_by' => auth()->id(),
    ]);
});

test('account requires an expense account when type is agent', function () {
    // Arrange
    $data = [
        'name' => 'Test Agent',
        'type' => AccountType::Agent->value,
    ];

    // Act
    $response = $this->post(route('accounts.accounts.store'), $data);

    // Assert
    $response->assertSessionHasErrors('expense_account');
    $this->assertDatabaseCount(Account::class, 0);
});

test('account can be updated', function () {
    // Arrange
    $account = Account::factory()->create(['type' => AccountType::Expenses]);
    $data = [
        'name' => 'Updated Account Name',
        'type' => AccountType::Expenses->value,
        'phone' => '9876543210',
        'email' => 'updated@example.com',
    ];

    // Act
    $response = $this->put(route('accounts.accounts.update', $account), $data);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('accounts.accounts.index'));
    $response->assertSessionHas('success', 'Account updated Successfully');

    $this->assertDatabaseHas(Account::class, [
        'id' => $account->id,
        'name' => 'Updated Account Name',
        'email' => 'updated@example.com',
    ]);
});

test('account can be deleted', function () {
    // Arrange
    $account = Account::factory()->create(['type' => AccountType::Expenses]);

    // Act
    $response = $this->delete(route('accounts.accounts.destroy', $account));

    // Assert
    $response->assertRedirect(route('accounts.accounts.index'));
    $response->assertSessionHas('success', 'Account deleted Successfully');
    $this->assertSoftDeleted(Account::class, ['id' => $account->id]);
});
