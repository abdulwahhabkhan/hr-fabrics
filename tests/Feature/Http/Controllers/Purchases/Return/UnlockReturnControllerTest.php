<?php

use App\Enums\ReturnStatus;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Action\Log;
use App\Models\Catalog\Product;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\FabricReceiving;
use App\Models\Stock\Inventory;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;


test('unlock po return', function () {
    // Arrange
    $user = $this->getAdmin();
    actingAs($user);
    $product = Product::factory()->box()->create();
    $stock = FabricReceiving::factory()->create();
    $porId = fake()->randomNumber(4);
    $inventories = Inventory::factory(3)
        ->recycle($product)
        ->create([
            'stockable_id' => $stock->id,
            'stockable_item_id' => $stock->id,
            'stockable_type' => $stock->getMorphClass(),
            'transaction_date' => fake()->dateTimeThisMonth(),
            'outbound_id' => $porId,
            'outbound_type' => PurchaseReturn::morphClass(),
            'outbound_item_id' => $porId + 1,
            'qty' => fake()->numberBetween(10, 30),
        ]);
    $items = [];
    foreach ($inventories as $index => $inventory) {
        if ($index % 2 === 1) {
            continue;
        }
        $items[] = [
            ...$inventory->toArray(),
            'inventory_id' => $inventory->id,
            'return_qty' => $inventory->qty,
        ];
    }
    $por = PurchaseReturn::factory()
        ->closed()
        ->create(['id' => $porId]);
    Journal::factory()
        ->hasTransactions(2)
        ->create([
            'resource_type' => $por->getMorphClass(),
            'resource_id' => $por->id,
        ]);
    // Action
    $response = post(route('purchases.por.unlock', $por));
    // Assert
    $response->assertSessionHasNoErrors()
        ->assertRedirectToRoute('purchases.por.index');
    $por->refresh();
    expect($por)
        ->transaction_date->not()->toBeNull()
        ->status->toBe(ReturnStatus::Open);
    assertDatabaseHas(Log::class, [
        'loggable_type' => $por->getMorphClass(),
        'loggable_id' => $por->id,
    ]);
    foreach ($items as $item) {
        assertDatabaseHas(Inventory::class, [
            'id' => $item['inventory_id'],
            'transaction_date' => null,
            'outbound_type' => null,
            'outbound_id' => null,
            'outbound_item_id' => null,
        ]);
    }

    assertDatabaseCount(Journal::class, 0);
    assertDatabaseCount(JournalDetail::class, 0);
});