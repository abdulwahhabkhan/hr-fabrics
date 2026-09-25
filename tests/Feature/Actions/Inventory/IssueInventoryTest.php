<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Inventory\IssueInventory;
use App\Enums\PackingType;
use App\Exceptions\UnableToAllocateStockException;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\PurchaseReturnItem;
use App\Models\Stock\Inventory;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('issues full inventory for thaan purchase return', function () {
    // Ensure the required user exists for factories
    $this->getAdmin();

    // Arrange: create a product and one thaan inventory line that is available (has transaction_date)
    $product = Product::factory()->thaan()->create();

    $transactionDate = now()->subDay();
    /** @var Inventory $inv */
    $inv = Inventory::factory()
        ->withPurchase()
        ->thaan()
        ->create([
            'product_id' => $product->id,
            // ensure it is considered available()
            'transaction_date' => $transactionDate,
            // make the values deterministic
            'size' => 36.0,
            'qty' => 3,
            'meters' => 108.0, // 3 * 36
        ]);

    // Outbound: a purchase return with a line item for this product
    $por = PurchaseReturn::factory()->closed()->create();

    $item = PurchaseReturnItem::factory()->create([
        'purchase_return_id' => $por->id,
        'product_id' => $product->id,
        'unit' => PackingType::Thaan,
        'qty' => $inv->qty,
        'size' => 36.0,
        'total_qty' => $inv->meters,
    ]);

    // Act: issue exactly the same quantity/meters, so no split should occur
    $issued = new IssueInventory()
        ->setOutbound($por)
        ->setTransactionDate($por->transaction_date)
        ->setOutboundItemId($item->id)
        ->setProductId($product->id)
        ->setUnit(PackingType::Thaan)
        // size is ignored for Thaan in a query, but set anyway
        ->setSize(36.0)
        ->setQuantity($inv->qty)
        ->setMeters($inv->meters)
        ->issue();

    // Assert
    assertDatabaseCount(Inventory::class, 1);
    expect($issued)
        ->toBeArray()
        ->toHaveCount(1);

    // the issued record should be the same original inventory (no split)
    $inv->refresh();

    // morph association set
    expect($inv->outbound_id)->toBe($por->id)
        ->and($inv->outbound_type)->toBe($por->getMorphClass())
        ->and($inv->outbound_item_id)->toBe($item->id)
        ->and($inv->qty)->toBe(3)
        ->and($inv->meters)->toBeFloat()->toBe(108.0);

    // untouched qty/meters because we allocated full line (no split)
});
it('issues partial inventory for thaan purchase return', function () {
    // Ensure the required user exists for factories
    $this->getAdmin();

    // Arrange: create a product and one thaan inventory line that is available (has transaction_date)
    $product = Product::factory()->thaan()->create();

    $transactionDate = now()->subDay();
    /** @var Inventory $inv */
    $inventories = Inventory::factory(2)
        ->withPurchase()
        ->thaan()
        ->sequence(['transaction_date' => $transactionDate], ['transaction_date' => $transactionDate->subDays(3)])
        ->create([
            'product_id' => $product->id,
            'size' => 0,
            'qty' => 3,
            'meters' => 108.0, // 3 * 36
        ]);
    $inventories->first();
    $inventories->last();

    // Outbound: a purchase return with a line item for this product
    $por = PurchaseReturn::factory()->closed()->create();

    $item = PurchaseReturnItem::factory()->create([
        'purchase_return_id' => $por->id,
        'product_id' => $product->id,
        'unit' => PackingType::Thaan,
        'qty' => 5,
        'size' => 36,
        'total_qty' => 5 * 36,
    ]);

    // Act: issue exactly the same quantity/meters, so no split should occur
    $issued = new IssueInventory()
        ->setTransactionDate($por->transaction_date)
        ->setOutbound($por)
        ->setOutboundItemId($item->id)
        ->setProductId($product->id)
        ->setUnit(PackingType::Thaan)
        // size is ignored for Thaan in a query, but set anyway
        ->setSize(36.0)
        ->setQuantity($item->qty)
        ->setMeters($item->total_qty)
        ->issue();

    // Assert
    assertDatabaseCount(Inventory::class, 3);
    expect($issued)
        ->toBeArray()
        ->toHaveCount(2);
    $availableInventory = Inventory::query()
        ->available()
        ->where('product_id', $product->id)
        ->selectRaw('sum(meters) as meters, sum(qty) as qty')
        ->first();
    $issuedInventory = $por->inventories()
        ->selectRaw('sum(meters) as meters, sum(qty) as qty')
        ->first();
    expect($issuedInventory)
        ->meters->toBeFloat()->toEqual(5 * 36)
        ->and($availableInventory)
        ->meters->toBeFloat()->toEqual(36);
});
it('issues full inventory with box/suit with size filter and FIFO', function (PackingType $unit) {
    // Ensure the required user exists for factories
    $this->getAdmin();
    // Arrange: product and two box inventory lines of the same size, different dates
    $product = Product::factory()->create([
        'is_box' => $unit === PackingType::Box,
    ]);

    $size = 5.0;
    $qty = 10;
    $size2 = $size + 1.5;
    Inventory::factory(2)->withPurchase()
        ->sequence(
            ['transaction_date' => now()->subDays(4), 'size' => $size, 'meters' => $qty * $size],
            ['transaction_date' => now()->subDays(3), 'size' => $size2, 'meters' => $qty * ($size2)],
        )
        ->create([
            'product_id' => $product->id,
            'qty' => $qty,
            'unit' => $unit,
        ]);

    $por = PurchaseReturn::factory()->closed()->create();
    $item = PurchaseReturnItem::factory()->create([
        'purchase_return_id' => $por->id,
        'product_id' => $product->id,
        'unit' => $unit,
        'qty' => $qty,
        'size' => $size,
        'total_qty' => 50,
    ]);

    // Act: Request exactly older line quantities so only the FIFO-first is used
    $issued = new IssueInventory()
        ->setProductId($product->id)
        ->setUnit($unit)
        ->setSize($size)
        ->setQuantity($item->qty)
        ->setMeters($item->total_qty)
        ->setOutbound($por)
        ->setTransactionDate($por->transaction_date)
        ->setOutboundItemId($item->id)
        ->issue();

    // Assert: exactly one issued, and it should be the older one
    expect($issued)->toHaveCount(1);
    assertDatabaseCount(Inventory::class, 2);
    expect($por->inventories)
        ->sum('qty')->toBe($qty)
        ->sum('meters')->toBe($qty * $size);
    assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'qty' => $qty,
        'meters' => $qty * $size2,
        'unit' => $unit->name,
        'size' => $size2,
        'outbound_type' => null,
        'outbound_item_id' => null,
        'outbound_id' => null,
    ]);
})->with([
    [PackingType::Box],
    [PackingType::Suit],
]);

it('issues partial inventory with box/suit with size filter and FIFO', function (PackingType $unit) {
    // Ensure the required user exists for factories
    $this->getAdmin();
    // Arrange: product and two box inventory lines of the same size, different dates
    $product = Product::factory()->create([
        'is_box' => $unit === PackingType::Box,
    ]);

    $size = 5.0;
    $qty = 10;

    Inventory::factory(2)->withPurchase()
        ->sequence(
            ['transaction_date' => now()->subDays(4), 'size' => $size, 'meters' => $qty * $size],
            ['transaction_date' => now()->subDays(3), 'size' => $size, 'meters' => $qty * $size],
        )
        ->create([
            'product_id' => $product->id,
            'qty' => $qty,
            'unit' => $unit,
        ]);
    $issueQty = $qty + 2;
    $availableQty = ($qty * 2) - ($qty + 2);
    $por = PurchaseReturn::factory()->closed()->create();
    $item = PurchaseReturnItem::factory()->create([
        'purchase_return_id' => $por->id,
        'product_id' => $product->id,
        'unit' => $unit,
        'qty' => $issueQty,
        'size' => $size,
        'total_qty' => $issueQty * $size,
    ]);

    // Act: Request exactly older line quantities so only the FIFO-first is used
    $issued = new IssueInventory()
        ->setProductId($product->id)
        ->setUnit($unit)
        ->setSize($size)
        ->setQuantity($item->qty)
        ->setMeters($item->total_qty)
        ->setOutbound($por)
        ->setTransactionDate($por->transaction_date)
        ->setOutboundItemId($item->id)
        ->issue();

    // Assert: exactly one issued, and it should be the older one
    expect($issued)->toHaveCount(2);
    assertDatabaseCount(Inventory::class, 2 + 1);
    expect($por->inventories)
        ->sum('meters')->toBe($issueQty * $size);
    assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'meters' => $availableQty * $size,
        'unit' => $unit->name,
        'size' => $size,
        'outbound_type' => null,
        'outbound_item_id' => null,
        'outbound_id' => null,
    ]);
})->with([
    [PackingType::Box],
    [PackingType::Suit],
]);

test('migration scenario 01: return', function () {
    // Arrange
    $product = Product::factory()->create();
    $unit = PackingType::Thaan->value;
    $returnQty = 44; // Matching the inventory qty we expect to split
    $returnMeters = 240;
    /** @var PurchaseReturn $return */
    $return = PurchaseReturn::factory()
        ->hasItems(1, [
            'product_id' => $product->id,
            'unit' => $unit,
            'size' => 0,
            'qty' => $returnQty,
            'total_qty' => $returnMeters,
        ])
        ->closed()
        ->create();
    $inventories = [
        ['transaction_date' => '2022-08-31', 'qty' => 44, 'meters' => 666, 'size' => 0],
        ['transaction_date' => '2022-08-31', 'qty' => 22, 'meters' => 198, 'size' => 0],
        ['transaction_date' => '2022-08-31', 'qty' => 70, 'meters' => 1090, 'size' => 0],
        ['transaction_date' => '2022-10-27', 'qty' => 23, 'meters' => 315, 'size' => 14],
        ['transaction_date' => '2022-10-31', 'qty' => 14, 'meters' => 315, 'size' => 22.5],
    ];
    Inventory::factory(5)
        ->sequence(...$inventories)
        ->create([
            'product_id' => $product->id,
            'unit' => $unit,
            'stockable_type' => FabricReceiving::morphClass(),
            'stockable_id' => 0,
            'stockable_item_id' => 0,
        ]);
    // Action
    foreach ($return->items as $item) {
        resolve(IssueInventory::class)
            ->setOutbound($return)
            ->setOutboundItemId($item->id)
            ->setTransactionDate($return->transaction_date)
            ->setProductId($item->product_id)
            ->setSize($item->size)
            ->setQuantity($item->qty)
            ->setUnit($item->unit)
            ->setMeters($item->total_qty)
            ->issue();
    }
    // Assert
    assertDatabaseCount(Inventory::class, 6);
    foreach ($inventories as $index => $inventory) {
        if ($index === 0) {
            assertDatabaseHas(Inventory::class, array_merge($inventory, [
                'outbound_id' => null,
                'qty' => $returnQty,
                'meters' => $inventory['meters'] - $returnMeters,
            ]));
            assertDatabaseHas(Inventory::class, array_merge($inventory, [
                'outbound_id' => $return->id,
                'qty' => 0,
                'meters' => $returnMeters,
            ]));
        } else {
            assertDatabaseHas(Inventory::class, array_merge($inventory, ['outbound_id' => null]));
        }
    }
});
test('migration scenario 02:  return', function () {
    // Arrange
    $product = Product::factory()->create();
    $unit = PackingType::Thaan->value;

    $itemsData = [
        ['product_id' => $product->id, 'unit' => $unit, 'size' => 0, 'qty' => 23, 'total_qty' => 490.5],
    ];
    /** @var PurchaseReturn $return */
    $return = PurchaseReturn::factory()
        ->has(PurchaseReturnItem::factory(count($itemsData))->sequence(...$itemsData), 'items')
        ->closed()
        ->create();
    $inventories = [
        ['transaction_date' => '2022-08-31', 'qty' => 91, 'meters' => 274.5, 'size' => 0],
        // ['transaction_date' => '2022-08-31', 'qty' => 126, 'meters' => 1134, 'size' => 0],
        ['transaction_date' => '2022-08-31', 'qty' => 102, 'meters' => 1746, 'size' => 0],
    ];
    Inventory::factory(count($inventories))
        ->sequence(...$inventories)
        ->create([
            'product_id' => $product->id,
            'unit' => $unit,
            'transaction_date' => now()->subYear(), // Make it available
            'stockable_type' => FabricReceiving::morphClass(),
            'stockable_id' => 0,
            'stockable_item_id' => 0,
        ]);
    // Action
    foreach ($return->items as $item) {
        resolve(IssueInventory::class)
            ->setOutbound($return)
            ->setOutboundItemId($item->id)
            ->setTransactionDate($return->transaction_date)
            ->setProductId($item->product_id)
            ->setSize($item->size)
            ->setQuantity($item->qty)
            ->setUnit($item->unit)
            ->setMeters($item->total_qty)
            ->issue();
    }
    // Assert
    assertDatabaseCount(Inventory::class, 3);
});
test('migration scenario 03:  1 fulfilled by 3', function () {
    // Arrange
    $product = Product::factory()->create();
    $unit = PackingType::Thaan->value;

    $itemsData = [
        ['product_id' => $product->id, 'unit' => $unit, 'size' => 0, 'qty' => 191, 'total_qty' => 4918.5],
    ];
    /** @var PurchaseReturn $return */
    $return = PurchaseReturn::factory()
        ->has(PurchaseReturnItem::factory(count($itemsData))->sequence(...$itemsData), 'items')
        ->closed()
        ->create([
            'transaction_date' => '2022-09-07',
        ]);
    $inventories = [
        ['transaction_date' => '2022-08-31', 'qty' => 63, 'meters' => 1656, 'size' => 0],
        ['transaction_date' => '2022-08-31', 'qty' => 126, 'meters' => 3224, 'size' => 0],
        ['transaction_date' => '2022-09-04', 'qty' => 2, 'meters' => 38.5, 'size' => 0],
    ];
    Inventory::factory(count($inventories))
        ->sequence(...$inventories)
        ->create([
            'product_id' => $product->id,
            'unit' => $unit,
            'transaction_date' => now()->subYear(), // Make it available
            'stockable_type' => FabricReceiving::morphClass(),
            'stockable_id' => 0,
            'stockable_item_id' => 0,
        ]);
    // Action
    foreach ($return->items as $item) {
        resolve(IssueInventory::class)
            ->setOutbound($return)
            ->setOutboundItemId($item->id)
            ->setTransactionDate($return->transaction_date)
            ->setProductId($item->product_id)
            ->setSize($item->size)
            ->setQuantity($item->qty)
            ->setUnit($item->unit)
            ->setMeters($item->total_qty)
            ->issue();
    }
    // Assert
    assertDatabaseCount(Inventory::class, 3);
});

it('filters by receivedBefore', function () {
    $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    Inventory::factory()->thaan()->create([
        'product_id' => $product->id,
        'transaction_date' => now()->subDays(10),
        'meters' => 100,
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => 0,
        'stockable_item_id' => 0,
    ]);
    Inventory::factory()->thaan()->create([
        'product_id' => $product->id,
        'transaction_date' => now()->subDays(5),
        'meters' => 100,
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => 0,
        'stockable_item_id' => 0,
    ]);

    $por = PurchaseReturn::factory()->closed()->create();
    $item = PurchaseReturnItem::factory()->create(['product_id' => $product->id, 'total_qty' => 50]);

    $issued = new IssueInventory()
        ->setOutbound($por)
        ->setTransactionDate($por->transaction_date)
        ->setOutboundItemId($item->id)
        ->setProductId($product->id)
        ->setUnit(PackingType::Thaan)
        ->setReceivedBefore(now()->subDays(7))
        ->setQuantity(1)
        ->setMeters(50)
        ->issue();

    expect($issued)->toHaveCount(1)
        ->and($issued[0]->transaction_date->toDateString())->toBe(now()->subDays(10)->toDateString());
});

it('filters by receivedAfter', function () {
    $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    Inventory::factory()->thaan()->create([
        'product_id' => $product->id,
        'transaction_date' => now()->subDays(10),
        'meters' => 100,
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => 0,
        'stockable_item_id' => 0,
    ]);
    Inventory::factory()->thaan()->create([
        'product_id' => $product->id,
        'transaction_date' => now()->subDays(5),
        'meters' => 100,
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => 0,
        'stockable_item_id' => 0,
    ]);

    $por = PurchaseReturn::factory()->closed()->create();
    $item = PurchaseReturnItem::factory()->create(['product_id' => $product->id, 'total_qty' => 50]);

    $issued = new IssueInventory()
        ->setOutbound($por)
        ->setTransactionDate($por->transaction_date)
        ->setOutboundItemId($item->id)
        ->setProductId($product->id)
        ->setUnit(PackingType::Thaan)
        ->setReceivedAfter(now()->subDays(7))
        ->setQuantity(1)
        ->setMeters(50)
        ->issue();

    expect($issued)->toHaveCount(1)
        ->and($issued[0]->transaction_date->toDateString())->toBe(now()->subDays(5)->toDateString());
});

it('throws validation exception when not enough stock', function () {
    $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    Inventory::factory()->thaan()->create([
        'product_id' => $product->id,
        'transaction_date' => now(),
        'meters' => 10,
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => 0,
        'stockable_item_id' => 0,
    ]);

    $por = PurchaseReturn::factory()->closed()->create();

    new IssueInventory()
        ->setOutbound($por)
        ->setTransactionDate($por->transaction_date)
        ->setOutboundItemId(1)
        ->setProductId($product->id)
        ->setUnit(PackingType::Thaan)
        ->setQuantity(1)
        ->setMeters(50)
        ->issue();
})->throws(ValidationException::class);

it('throws exception if final allocation sum is incorrect', function () {
    $this->getAdmin();
    $product = Product::factory()->thaan()->create();
    Inventory::factory()->thaan()->create([
        'product_id' => $product->id,
        'transaction_date' => now(),
        'meters' => 100,
        'stockable_type' => FabricReceiving::morphClass(),
        'stockable_id' => 0,
        'stockable_item_id' => 0,
    ]);

    $por = PurchaseReturn::factory()->closed()->create();

    // We mock splitLine to return something else to trigger the check
    $action = new class extends IssueInventory
    {
        public function splitLine(Inventory $inventory, int $qty, float $meters): Inventory
        {
            $new = parent::splitLine($inventory, $qty, $meters);
            $new->meters = $meters - 1;

            // Sabotage
            return $new;
        }
    };

    $action->setOutbound($por)
        ->setTransactionDate($por->transaction_date)
        ->setOutboundItemId(1)
        ->setProductId($product->id)
        ->setUnit(PackingType::Thaan)
        ->setQuantity(1)
        ->setMeters(50)
        ->issue();
})->throws(UnableToAllocateStockException::class);
