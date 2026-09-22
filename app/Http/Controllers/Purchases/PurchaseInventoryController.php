<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Purchase\Purchase;
use Inertia\Inertia;

class PurchaseInventoryController extends Controller
{
    public function __invoke(Purchase $receipt)
    {
        $this->authorize('inventory', $receipt);
        $returnUrl = route('purchases.pos.index');
        $inventories = $receipt->inventories()
            ->with([
                'product' => function ($query) {
                    $query->select('id', 'name');
                },
            ])
            ->get();

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => $returnUrl,
            'inventories' => $inventories->each(function ($r) use ($receipt) {
                $r->reference_no = $receipt->invoice_no;
                $r->lot_no = $receipt->lot_no;

                return $r;
            }),
            'page_header' => 'Fabric Receiving Invoice: Inventory Detail',
        ]);
    }
}
