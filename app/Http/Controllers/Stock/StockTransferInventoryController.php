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
     * Show both sides of a confirmed transfer: the lines booked out of the source location
     * and the lots opened at the destination.
     */
    public function __invoke(StockTransfer $stockTransfer): Response
    {
        Gate::authorize('inventory', $stockTransfer);
        $movedOut = $stockTransfer->inventories()->with('product:id,name')->fifo()->get();
        $movedIn = $stockTransfer->arrivedInventories()->with('product:id,name')->fifo()->get();

        $inventories = $movedOut->each(fn (Inventory $inventory) => $inventory['direction'] = 'out')
            ->concat($movedIn->each(fn (Inventory $inventory) => $inventory['direction'] = 'in'))
            ->each(function (Inventory $inventory) use ($stockTransfer) {
                $inventory['reference_no'] = $stockTransfer->transfer_no;
                $inventory['lot_no'] = '';
            });

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => route('stocks.stock-transfers.index'),
            'inventories' => $inventories->values(),
            'sections' => [
                ['direction' => 'out', 'heading' => "Out of {$stockTransfer->from_location->label()}"],
                ['direction' => 'in', 'heading' => "Into {$stockTransfer->to_location->label()}"],
            ],
            'hide_thaan_qty' => true,
            'page_header' => 'Stock Transfer: Inventory Detail',
            'parent' => [
                'label' => 'Stock transfer',
                'reference_no' => $stockTransfer->transfer_no,
                'status' => $stockTransfer->status->name,
                'details' => [
                    ['label' => 'Date', 'value' => $stockTransfer->transaction_date?->toDateString(), 'type' => 'date'],
                    ['label' => 'From', 'value' => $stockTransfer->from_location->label()],
                    ['label' => 'To', 'value' => $stockTransfer->to_location->label()],
                    ...($stockTransfer->isSizedUnit()
                        ? [['label' => 'Qty', 'value' => $stockTransfer->qty, 'type' => 'number']]
                        : []),
                    ['label' => 'Meters', 'value' => $stockTransfer->meters, 'type' => 'number'],
                ],
            ],
        ]);
    }
}
