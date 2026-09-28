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
        $receipt->load('supplier:id,name,address');

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => $returnUrl,
            'inventories' => $inventories->each(function ($r) use ($receipt) {
                $r->setAttribute('reference_no', $receipt->invoice_no);
                $r->setAttribute('lot_no', $receipt->lot_no);

                return $r;
            }),
            'page_header' => 'Fabric Receiving Invoice: Inventory Detail',
            'parent' => [
                'label' => 'Fabric purchase',
                'reference_no' => $receipt->invoice_no,
                'url' => route('purchases.pos.show', $receipt),
                'status' => $receipt->status?->value,
                'party' => [
                    'label' => 'Supplier',
                    'name' => $receipt->supplier?->name,
                    'address' => $receipt->supplier?->address,
                ],
                'details' => [
                    ['label' => 'Date', 'value' => $receipt->transaction_date?->toDateString(), 'type' => 'date'],
                    ['label' => 'Bilti no', 'value' => $receipt->bilti_no],
                    ['label' => 'Lot no', 'value' => $receipt->lot_no],
                    ['label' => 'Bill no', 'value' => $receipt->bill_no],
                    ['label' => 'Total qty', 'value' => $receipt->total_qty, 'type' => 'number'],
                    ['label' => 'Total amount', 'value' => $receipt->total, 'type' => 'number'],
                ],
            ],
        ]);
    }
}
