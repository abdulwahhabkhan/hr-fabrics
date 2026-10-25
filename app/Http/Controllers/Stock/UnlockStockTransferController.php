<?php

namespace App\Http\Controllers\Stock;

use App\Actions\Stock\StockTransfers\UnlockStockTransfer;
use App\Http\Controllers\Controller;
use App\Models\Stock\StockTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Throwable;

class UnlockStockTransferController extends Controller
{
    /**
     * Reverse a confirmed transfer so it can be edited or deleted.
     *
     * @throws Throwable
     */
    public function __invoke(StockTransfer $stockTransfer, Request $request, UnlockStockTransfer $unlockStockTransfer): RedirectResponse
    {
        Gate::authorize('unlock', $stockTransfer);
        $unlockStockTransfer->handle($stockTransfer, $request->user());

        return Redirect::route('stocks.stock-transfers.edit', $stockTransfer->id)
            ->with(['success' => "Stock transfer {$stockTransfer->transfer_no} unlocked"]);
    }
}
