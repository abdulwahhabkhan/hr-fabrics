<?php

use App\Http\Controllers\Purchases\Purchase\UnlockPurchaseController;
use App\Http\Controllers\Sales\Order\UnlockOrderController;
use App\Http\Controllers\Sales\Return\UnlockSaleReturnController;
use App\Http\Controllers\Stock\UnlockStoreTransferController;
use Illuminate\Support\Facades\Route;

// Admin Action
Route::prefix('actions')->middleware(['auth'])->name('actions.')->group(function () {

    Route::post('purchase/purchase/{receipt}/unlock', UnlockPurchaseController::class)
        ->name('purchase.open');
    Route::post('sales/order/{order}', UnlockOrderController::class)
        ->name('order.open');

    Route::post('sales/return/{return}', UnlockSaleReturnController::class)
        ->name('returns.open');

    Route::post('stocks/store-transfer/{storeTransfer}', UnlockStoreTransferController::class)
        ->name('store-transfer.open');
});
