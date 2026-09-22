<?php

use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\PurchaseReturnItem;
use App\Models\Stock\Inventory;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->actingAs($this->user);
    $this->attachPermissions($this->user, 'purchases.por.update');
});

test('delete item', function () {
    // Arrange
    /** @var PurchaseReturn $return */
    $return = PurchaseReturn::factory()
        ->hasItems(2)
        ->create();
    $item = $return->items->first();
    /** @var Inventory $inventory */
    $inventory = Inventory::factory()
        ->withPurchase()
        ->create([
            'product_id' => $item->product_id,
            'outbound_type' => $return->getMorphClass(),
            'outbound_id' => $return->id,
            'outbound_item_id' => $item->id,
            'qty' => $item->qty,
        ]);

    // Action
    $response = delete(route('ajax.por.item.destroy', [$return->id, $item->id]));
    // Assert
    $response->assertOk();
    assertDatabaseMissing(PurchaseReturnItem::class, $item->toArray());
    assertDatabaseCount(PurchaseReturnItem::class, 1);
    assertDatabaseCount(Inventory::class, 1);
    assertDatabaseHas(Inventory::class, [
        'id' => $inventory->id,
        'outbound_item_id' => null,
        'outbound_type' => null,
        'outbound_id' => null,
    ]);

});
