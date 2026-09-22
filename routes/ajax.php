<?php

/* Ajax Routes */

use App\Http\Controllers\Purchases\FabricReceiving\AddFabricReceivingItemController;
use App\Http\Controllers\Purchases\FabricReceiving\DeleteFabricReceivingItemController;
use App\Http\Controllers\Purchases\PurchaseController;
use App\Http\Controllers\Purchases\Return\AddReturnItemController;
use App\Http\Controllers\Purchases\Return\DeleteReturnItemController;
use App\Http\Controllers\Sales\Order\AddOrderItemController;
use App\Http\Controllers\Sales\Order\DeleteOrderItemController;
use App\Http\Controllers\Sales\Order\OrderController;
use App\Http\Controllers\Stock\InventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('ajax')
    ->middleware(['auth', 'verified'])
    ->name('ajax.')
    ->group(function () {
        // Sale order Item
        Route::get('sales/items/{order}', [OrderController::class, 'orderItems'])
            ->name('so.items');
        Route::post('sales/items/{order}', AddOrderItemController::class)
            ->name('so.item.add');
        Route::delete('sales/items/{item}', DeleteOrderItemController::class)
            ->name('so.item.destroy');

        // PO Item
        Route::get('purchases/items/{order}', [PurchaseController::class, 'orderItems'])
            ->name('po.items')->where(['id' => '[0-9]+']);
        Route::post('purchases/items/{order}', [PurchaseController::class, 'orderItem'])
            ->name('po.item')->where(['id' => '[0-9]+']);
        Route::delete('purchases/items/{id}', [
            PurchaseController::class,
            'deleteOrderItem',
        ])
            ->name('po.item.destroy')->where(['id' => '[0-9]+']);
        Route::put('purchases/action/{id}', [PurchaseController::class, 'deleteOrderItem'])
            ->name('po.action')->where(['id' => '[0-9]+']);
        Route::post('purchases/fabric-receiving/{fabric_receiving}/item', AddFabricReceivingItemController::class)
            ->name('fabric-receiving.item');
        Route::delete('purchases/fabric-receiving/{item}/item', DeleteFabricReceivingItemController::class)
            ->name('fabric-receiving.item.destroy');
        Route::post('purchases/return/{return}/item', AddReturnItemController::class)
            ->name('return.item.save');
        Route::delete('purchases/return/{return}/item/{item}', DeleteReturnItemController::class)
            ->name('por.item.destroy');

        // stock
        Route::get('stock/product', [InventoryController::class, 'productStock'])
            ->name('stock.product');
    });
