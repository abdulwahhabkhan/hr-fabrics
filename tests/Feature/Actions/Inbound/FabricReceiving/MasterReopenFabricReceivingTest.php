<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Inbound\FabricReceiving\FabricReceivingConfirmed;
use App\Actions\Inbound\FabricReceiving\MasterReopenFabricReceiving;
use App\Actions\Inbound\Purchase\ConfirmPurchaseActions;
use App\Actions\Outbound\SaleOrders\ConfirmOrder;
use App\Enums\PackingType;
use App\Enums\StatusText;
use App\Models\Accounts\Journal;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Stock\Inventory;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->creatCashAccount();
    $product = Product::factory()->create();

    $this->receiving = FabricReceiving::factory()->confirmed()->create(['transaction_date' => '2026-10-01']);
    $this->receivingItem = FabricReceivingItem::factory()->create([
        'fabric_receiving_id' => $this->receiving->id,
        'product_id' => $product->id,
        'unit' => PackingType::Thaan->value,
        'qty' => 2,
        'size' => 0,
        'total_qty' => 50,
    ]);
    resolve(FabricReceivingConfirmed::class)->handle($this->receiving);

    $this->purchase = Purchase::factory()->confirmed()->create([
        'stock_ids' => (string) $this->receiving->id,
        'supplier_id' => $this->receiving->supplier_id,
        'transaction_date' => '2026-10-02',
    ]);
    PurchaseItem::factory()->create([
        'purchase_id' => $this->purchase->id,
        'product_id' => $product->id,
        'unit' => PackingType::Thaan,
        'qty' => 2,
        'size' => 0,
        'total_qty' => 50,
        'price' => 100,
        'total' => 5000,
    ]);
    $this->purchase->update(['total' => 5000]);
    resolve(ConfirmPurchaseActions::class)->handle($this->purchase, $this->user);

    $this->order = Order::factory()->closed()->create(['transaction_date' => '2026-10-05']);
    $this->orderItem = OrderItem::factory()->thaan()->create([
        'order_id' => $this->order->id,
        'product_id' => $product->id,
        'qty' => 1,
        'size' => 20,
        'total_qty' => 20,
    ]);
    resolve(ConfirmOrder::class)->handle($this->order, $this->user);
});

test('reopen opens the receiving and its voucher, keeping sold stock booked', function () {
    resolve(MasterReopenFabricReceiving::class)->handle($this->receiving, $this->user);

    expect($this->receiving->refresh())
        ->status->toBe(StatusText::Open)
        ->transaction_date->toDateString()->toBe('2026-10-01')
        ->invoiced->toBeFalse()
        ->and($this->purchase->refresh())
        ->status->toBe(StatusText::Open)
        ->transaction_date->toDateString()->toBe('2026-10-02');
    expect($this->purchase->journal()->exists())->toBeFalse();
    assertDatabaseCount(Inventory::class, 1);
    assertDatabaseHas(Inventory::class, [
        'outbound_type' => Order::morphClass(),
        'outbound_id' => $this->order->id,
        'meters' => 20,
    ]);
});

test('confirming the corrected receiving and voucher reallocates the sale order at the new cost', function () {
    resolve(MasterReopenFabricReceiving::class)->handle($this->receiving, $this->user);
    $this->receivingItem->update(['total_qty' => 45]);
    $this->receiving->update(['status' => StatusText::Close]);
    resolve(FabricReceivingConfirmed::class)->handle($this->receiving);
    $this->purchase->items()->update(['total_qty' => 45, 'price' => 120, 'total' => 5400]);
    $this->purchase->refresh()->update(['status' => StatusText::Close, 'total' => 5400]);

    resolve(ConfirmPurchaseActions::class)->handle($this->purchase, $this->user);

    assertDatabaseHas(Inventory::class, [
        'stockable_id' => $this->receiving->id,
        'outbound_id' => $this->order->id,
        'outbound_item_id' => $this->orderItem->id,
        'outbound_on' => '2026-10-05 00:00:00',
        'meters' => 20,
        'cost' => 120,
    ]);
    assertDatabaseHas(Inventory::class, [
        'stockable_id' => $this->receiving->id,
        'outbound_id' => null,
        'meters' => 25,
        'cost' => 120,
        'transaction_date' => '2026-10-01 00:00:00',
    ]);
    assertDatabaseCount(Inventory::class, 2);
    expect($this->receiving->refresh()->invoiced)->toBeTrue();
    assertDatabaseHas(Journal::class, ['resource_id' => $this->purchase->id, 'posted_at' => '2026-10-02']);
});

test('confirming fails without changes when corrected stock no longer covers the sale', function () {
    resolve(MasterReopenFabricReceiving::class)->handle($this->receiving, $this->user);
    $this->receivingItem->update(['total_qty' => 10]);
    $this->receiving->update(['status' => StatusText::Close]);

    expect(fn () => resolve(FabricReceivingConfirmed::class)->handle($this->receiving))
        ->toThrow(ValidationException::class);

    assertDatabaseCount(Inventory::class, 1);
    assertDatabaseHas(Inventory::class, ['outbound_id' => $this->order->id, 'meters' => 20]);
    assertDatabaseMissing(Inventory::class, ['meters' => 10]);
});
