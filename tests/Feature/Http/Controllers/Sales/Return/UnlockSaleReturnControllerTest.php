<?php

use App\Actions\Outbound\SaleReturns\ConfirmSaleReturn;
use App\Enums\ReturnStatus;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Sales\SalesReturn;
use App\Models\Stock\Inventory;
use Illuminate\Auth\Access\AuthorizationException;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

test('unlock closed sale return order', function () {
    $this->withoutExceptionHandling();
    // Arrange
    $user = $this->getAdmin();
    $this->actingAs($user);
    $body = [];
    $return = SalesReturn::factory()
        ->hasReturnItems(3)
        ->closed()
        ->create();
    resolve(ConfirmSaleReturn::class)->handle($return, $user);
    // Act
    $response = $this->post(route('actions.returns.open', $return->id), $body);

    // Assert
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('sales.returns.edit', $return->id));
    assertDatabaseHas(SalesReturn::class, [
        'status' => ReturnStatus::Open->value,
        'id' => $return->id,
    ]);
    // inventory check
    assertDatabaseCount(Inventory::class, 0);
    assertDatabaseCount(Journal::class, 0);
    assertDatabaseCount(JournalDetail::class, 0);
});
test('unlock closed sale return order failed when inventory sold', function () {
    $this->withoutExceptionHandling();
    // Arrange
    $user = $this->getAdmin();
    $this->actingAs($user);
    $body = [];
    $return = SalesReturn::factory()
        ->hasReturnItems(3)
        ->closed()
        ->create();
    resolve(ConfirmSaleReturn::class)->handle($return, $user);
    $return->inventories()->limit(1)->update(['outbound_id' => 1]);
    // Act
    $this->postJson(route('actions.returns.open', $return->id), $body);

})->throws(AuthorizationException::class, 'Return is allocated sales');
