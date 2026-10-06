<?php

use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Services\InventoryService;

it('builds product history from confirmed purchase and sale lines', function () {
    $product = Product::factory()->create();
    $receiving = FabricReceiving::factory()->confirmed()->create();
    FabricReceivingItem::factory()->create([
        'fabric_receiving_id' => $receiving->id,
        'product_id' => $product->id,
        'unit' => 'Thaan',
        'size' => 25,
        'qty' => 4,
        'total_qty' => 100,
    ]);
    $order = Order::factory()->closed()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'unit' => 'Thaan',
        'size' => 25,
        'qty' => 2,
        'total_qty' => 50,
    ]);
    FabricReceivingItem::factory()->create([
        'fabric_receiving_id' => FabricReceiving::factory(),
        'product_id' => $product->id,
    ]);

    $history = (new InventoryService)->getProductHistory($product->id)->get();

    expect($history)->toHaveCount(2)
        ->and($history->firstWhere('type', 'Purchase')->only(['invoice_no', 'qty', 'size', 'meters', 'account_id']))
        ->toBe(['invoice_no' => $receiving->invoice_no, 'qty' => 4, 'size' => 25.0, 'meters' => 100.0, 'account_id' => $receiving->supplier_id])
        ->and($history->firstWhere('type', 'Sale')->only(['invoice_no', 'qty', 'size', 'meters', 'account_id']))
        ->toBe(['invoice_no' => $order->invoice_no, 'qty' => 2, 'size' => 25.0, 'meters' => 50.0, 'account_id' => $order->customer_id]);
});
