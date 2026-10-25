<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\Inventory;
use App\Models\Stock\StockTransfer;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferInventoryController extends Controller
{
    /**
     * Show the lots a confirmed transfer opened at the destination location.
     */
    public function __invoke(StockTransfer $stockTransfer): Response
    {
        Gate::authorize('inventory', $stockTransfer);
        $inventories = $stockTransfer->arrivedInventories()
            ->with('product:id,name')
            ->fifo()
            ->get();

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => route('stocks.stock-transfers.index'),
            'inventories' => $inventories->each(function (Inventory $inventory) use ($stockTransfer) {
                $inventory['reference_no'] = $stockTransfer->transfer_no;
                $inventory['lot_no'] = '';
            }),
            'page_header' => 'Stock Transfer: Inventory Detail',
            'parent' => [
                'label' => 'Stock transfer',
                'reference_no' => $stockTransfer->transfer_no,
                'status' => $stockTransfer->status->name,
                'details' => [
                    ['label' => 'Date', 'value' => $stockTransfer->transaction_date?->toDateString(), 'type' => 'date'],
                    ['label' => 'From', 'value' => $stockTransfer->from_location->label()],
                    ['label' => 'To', 'value' => $stockTransfer->to_location->label()],
                    ['label' => 'Qty', 'value' => $stockTransfer->qty, 'type' => 'number'],
                    ['label' => 'Meters', 'value' => $stockTransfer->meters, 'type' => 'number'],
                ],
            ],
        ]);
    }
}
