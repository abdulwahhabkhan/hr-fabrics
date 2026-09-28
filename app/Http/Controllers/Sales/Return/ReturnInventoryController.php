<?php

namespace App\Http\Controllers\Sales\Return;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesReturn;
use Inertia\Inertia;

class ReturnInventoryController extends Controller
{
    public function __invoke(SalesReturn $return)
    {
        $this->authorize('inventory', $return);
        $returnUrl = route('sales.returns.index');
        $inventories = $return->inventories()
            ->with([
                'product' => function ($query) {
                    $query->select('id', 'name');
                },
            ])
            ->get();
        $return->load('customer:id,name,address');

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => $returnUrl,
            'inventories' => $inventories->each(function ($r) use ($return) {
                $r['reference_no'] = $return->invoice_no;
                $r['lot_no'] = '';

                return $r;
            }),
            'page_header' => 'Sale Return: Inventory Detail',
            'parent' => [
                'label' => 'Sales return',
                'reference_no' => $return->invoice_no,
                'url' => route('sales.returns.show', $return),
                'status' => $return->status?->name,
                'party' => [
                    'label' => 'Customer',
                    'name' => $return->customer?->name,
                    'address' => $return->customer?->address,
                ],
                'details' => [
                    ['label' => 'Date', 'value' => $return->transaction_date?->toDateString(), 'type' => 'date'],
                    ['label' => 'Total qty', 'value' => $return->total_qty, 'type' => 'number'],
                    ['label' => 'Total amount', 'value' => $return->total_amount, 'type' => 'number'],
                ],
            ],
        ]);
    }
}
