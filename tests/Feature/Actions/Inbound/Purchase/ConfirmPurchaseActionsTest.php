<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Inbound\FabricReceiving\FabricReceivingConfirmed;
use App\Actions\Inbound\Purchase\ConfirmPurchaseActions;
use App\Enums\JournalHead;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Action\Log;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Stock\Inventory;

use function Pest\Laravel\assertDatabaseHas;

test('confirming a receipt marks stocks invoiced, posts the ledger, updates inventory cost and logs the action', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = FabricReceiving::factory()->hasItems(1)->confirmed()->create(['invoiced' => false]);
    $item = $stock->items->first();
    resolve(FabricReceivingConfirmed::class)->handle($stock);

    $receipt = Purchase::factory()->create([
        'stock_id' => $stock->id,
        'stock_ids' => (string) $stock->id,
        'supplier_id' => $stock->supplier_id,
        'total' => 5000,
        'transaction_date' => today(),
    ]);
    PurchaseItem::factory()->create([
        'purchase_id' => $receipt->id,
        'product_id' => $item->product_id,
        'unit' => $item->unit,
        'size' => $item->size,
        'qty' => $item->qty,
        'total_qty' => $item->total_qty,
        'price' => 4250,
        'total' => 5000,
    ]);

    // Act
    resolve(ConfirmPurchaseActions::class)->handle($receipt, $user);

    // Assert
    assertDatabaseHas(FabricReceiving::class, ['id' => $stock->id, 'invoiced' => true]);
    assertDatabaseHas(Inventory::class, [
        'product_id' => $item->product_id,
        'unit' => $item->unit,
        'cost' => 4250,
    ]);
    assertDatabaseHas(Journal::class, [
        'resource_id' => $receipt->id,
        'resource_type' => $receipt->getMorphClass(),
        'head' => JournalHead::Purchases->value,
    ]);
    assertDatabaseHas(JournalDetail::class, [
        'account_id' => $receipt->supplier_id,
        'cr' => $receipt->total,
        'dr' => 0,
    ]);
    assertDatabaseHas(Log::class, [
        'loggable_type' => $receipt->getMorphClass(),
        'loggable_id' => $receipt->id,
        'log' => json_encode(['action' => 'Receipt Confirmed', 'user' => ['id' => $user->id, 'name' => $user->name]]),
    ]);
});

test('confirming a receipt applies each box line price by size when the same product has two box lines', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = FabricReceiving::factory()->confirmed()->create(['invoiced' => false]);
    $product = Product::factory()->create();
    $firstItem = $stock->items()->create([
        'product_id' => $product->id,
        'voucher_no' => 'LN-1',
        'unit' => 'Box',
        'size' => 4.5,
        'qty' => 120,
        'total_qty' => 540,
        'status' => 0,
    ]);
    $secondItem = $stock->items()->create([
        'product_id' => $product->id,
        'voucher_no' => 'LN-2',
        'unit' => 'Box',
        'size' => 5,
        'qty' => 3,
        'total_qty' => 15,
        'status' => 0,
    ]);
    resolve(FabricReceivingConfirmed::class)->handle($stock);

    $receipt = Purchase::factory()->create([
        'stock_id' => $stock->id,
        'stock_ids' => (string) $stock->id,
        'supplier_id' => $stock->supplier_id,
        'transaction_date' => today(),
    ]);
    PurchaseItem::factory()->create([
        'purchase_id' => $receipt->id,
        'product_id' => $firstItem->product_id,
        'unit' => $firstItem->unit,
        'size' => $firstItem->size,
        'qty' => $firstItem->qty,
        'total_qty' => $firstItem->total_qty,
        'price' => 4250,
        'total' => $firstItem->qty * 4250,
    ]);
    PurchaseItem::factory()->create([
        'purchase_id' => $receipt->id,
        'product_id' => $secondItem->product_id,
        'unit' => $secondItem->unit,
        'size' => $secondItem->size,
        'qty' => $secondItem->qty,
        'total_qty' => $secondItem->total_qty,
        'price' => 5000,
        'total' => $secondItem->qty * 5000,
    ]);

    // Act
    resolve(ConfirmPurchaseActions::class)->handle($receipt, $user);

    // Assert: each box size keeps the price of its own receipt line, not the last one processed
    assertDatabaseHas(Inventory::class, [
        'product_id' => $firstItem->product_id,
        'unit' => $firstItem->unit,
        'size' => $firstItem->size,
        'cost' => 4250,
    ]);
    assertDatabaseHas(Inventory::class, [
        'product_id' => $secondItem->product_id,
        'unit' => $secondItem->unit,
        'size' => $secondItem->size,
        'cost' => 5000,
    ]);
});

test('confirming a receipt updates non-box inventory cost by product and unit even when qty and meters differ across combined stock invoices', function () {
    // Arrange: a receipt (voucher) merging two separate stock invoices for the same Thaan product,
    // each carrying different qty/meters — the receipt still owes them the same negotiated price.
    $user = $this->getAdmin();
    $product = Product::factory()->create();

    $stockA = FabricReceiving::factory()->confirmed()->create(['invoiced' => false]);
    $itemA = $stockA->items()->create([
        'product_id' => $product->id,
        'voucher_no' => 'LN-A',
        'unit' => 'Thaan',
        'size' => 0,
        'qty' => 50,
        'total_qty' => 1200,
        'status' => 0,
    ]);
    resolve(FabricReceivingConfirmed::class)->handle($stockA);

    $stockB = FabricReceiving::factory()->confirmed()->create(['invoiced' => false, 'supplier_id' => $stockA->supplier_id]);
    $itemB = $stockB->items()->create([
        'product_id' => $product->id,
        'voucher_no' => 'LN-B',
        'unit' => 'Thaan',
        'size' => 0,
        'qty' => 30,
        'total_qty' => 700,
        'status' => 0,
    ]);
    resolve(FabricReceivingConfirmed::class)->handle($stockB);

    $receipt = Purchase::factory()->create([
        'stock_id' => $stockA->id,
        'stock_ids' => $stockA->id.','.$stockB->id,
        'supplier_id' => $stockA->supplier_id,
        'transaction_date' => today(),
    ]);
    PurchaseItem::factory()->create([
        'purchase_id' => $receipt->id,
        'product_id' => $product->id,
        'unit' => 'Thaan',
        'size' => 0,
        'qty' => $itemA->qty,
        'total_qty' => $itemA->total_qty,
        'price' => 6500,
        'total' => $itemA->total_qty * 6500,
    ]);
    PurchaseItem::factory()->create([
        'purchase_id' => $receipt->id,
        'product_id' => $product->id,
        'unit' => 'Thaan',
        'size' => 0,
        'qty' => $itemB->qty,
        'total_qty' => $itemB->total_qty,
        'price' => 6500,
        'total' => $itemB->total_qty * 6500,
    ]);

    // Act
    resolve(ConfirmPurchaseActions::class)->handle($receipt, $user);

    // Assert: both stocks' inventory rows priced despite differing qty/meters
    assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'qty' => $itemA->qty,
        'meters' => $itemA->total_qty,
        'cost' => 6500,
    ]);
    assertDatabaseHas(Inventory::class, [
        'product_id' => $product->id,
        'qty' => $itemB->qty,
        'meters' => $itemB->total_qty,
        'cost' => 6500,
    ]);
});
