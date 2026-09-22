<?php

namespace App\Http\Controllers\Purchases\Return;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseReturn;
use Inertia\Inertia;

class ReturnInventoryController extends Controller
{
    public function __invoke(PurchaseReturn $return)
    {
        $this->authorize('inventory', $return);
        $returnUrl = route('purchases.por.index');
        $inventories = $return->inventories()
            ->with([
                'product' => function ($query) {
                    $query->select('id', 'name');
                },
            ])
            ->get();

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => $returnUrl,
            'inventories' => $inventories->each(function ($r) use ($return) {
                $r['reference_no'] = $return->invoice_no;
                $r['bill_no'] = $return->bill_no;
                $r['bilti_no'] = $return->bilti_no;

                return $r;
            }),
            'page_header' => 'PO Return Invoice: Inventory Detail',
        ]);
    }
}
