<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StoreTransfer;
use Inertia\Inertia;
use Inertia\Response;

class StoreTransferInventoryController extends Controller
{
    public function __invoke(StoreTransfer $storeTransfer): Response
    {
        $this->authorize('inventory', $storeTransfer);
        $inventories = $storeTransfer->inventories()
            ->with([
                'product' => function ($query) {
                    $query->select('id', 'name');
                },
            ])
            ->get();

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => route('stocks.store-transfers.index'),
            'inventories' => $inventories->each(function ($r) use ($storeTransfer) {
                $r['reference_no'] = $storeTransfer->transfer_no;
                $r['lot_no'] = '';

                return $r;
            }),
            'page_header' => 'Store Transfer: Inventory Detail',
        ]);
    }
}
