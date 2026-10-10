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
        $return->load('supplier:id,name,address');

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => $returnUrl,
            'inventories' => $inventories->each(function ($r) use ($return) {
                $r['reference_no'] = $return->invoice_no;
                $r['bill_no'] = $return->bill_no;
                $r['bilti_no'] = $return->bilti_no;

                return $r;
            }),
            'page_header' => 'PO Return Invoice: Inventory Detail',
            'parent' => [
                'label' => 'Fabric return',
                'reference_no' => $return->invoice_no,
                'url' => route('purchases.por.show', $return),
                'status' => $return->status->name,
                'party' => [
                    'label' => 'Supplier',
                    'name' => $return->supplier?->name,
                    'address' => $return->supplier?->address,
                ],
                'details' => [
                    ['label' => 'Date', 'value' => $return->transaction_date?->toDateString(), 'type' => 'date'],
                    ['label' => 'Bilti no', 'value' => $return->bilti_no],
                    ['label' => 'Bill no', 'value' => $return->bill_no],
                    ['label' => 'Total qty', 'value' => $return->total_qty, 'type' => 'number'],
                    ['label' => 'Total amount', 'value' => $return->total_amount, 'type' => 'number'],
                ],
            ],
        ]);
    }
}
