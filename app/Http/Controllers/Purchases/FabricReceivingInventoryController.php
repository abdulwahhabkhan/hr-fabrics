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
        $fabric_receiving->load('supplier:id,name,address');
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
            'parent' => [
                'label' => 'Fabric receiving',
                'reference_no' => $fabric_receiving->invoice_no,
                'url' => route('purchases.fabric-receivings.show', $fabric_receiving),
                'status' => $fabric_receiving->status?->value,
                'party' => [
                    'label' => 'Supplier',
                    'name' => $fabric_receiving->supplier?->name,
                    'address' => $fabric_receiving->supplier?->address,
                ],
                'details' => [
                    ['label' => 'Date', 'value' => $fabric_receiving->transaction_date?->toDateString(), 'type' => 'date'],
                    ['label' => 'Bilti no', 'value' => $fabric_receiving->bilti_no],
                    ['label' => 'Lot no', 'value' => $fabric_receiving->lot_no],
                    ['label' => 'Total qty', 'value' => $fabric_receiving->total_qty, 'type' => 'number'],
                    ['label' => 'Total meters', 'value' => $fabric_receiving->total_meters, 'type' => 'number'],
                ],
            ],
        ]);
    }
}
