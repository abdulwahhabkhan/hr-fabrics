<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Purchase\FabricReceiving;
use Inertia\Inertia;

class FabricReceivingInventoryController extends Controller
{
    public function __invoke(FabricReceiving $fabric_receiving)
    {
        $this->authorize('inventory', $fabric_receiving);
        $returnUrl = route('purchases.fabric-receivings.index');
        $inventories = $fabric_receiving->inventories()
            ->with([
                'product' => function ($query) {
                    $query->select('id', 'name');
                },
            ])
            ->get();

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => $returnUrl,
            'inventories' => $inventories->each(function ($r) use ($fabric_receiving) {
                $r['reference_no'] = $fabric_receiving->invoice_no;
                $r['lot_no'] = $fabric_receiving->lot_no;

                return $r;
            }),
            'page_header' => 'Fabric Receiving Note: Inventory Detail',
        ]);
    }
}
