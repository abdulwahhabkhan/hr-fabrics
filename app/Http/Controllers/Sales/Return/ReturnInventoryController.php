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

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => $returnUrl,
            'inventories' => $inventories->each(function ($r) use ($return) {
                $r['reference_no'] = $return->invoice_no;
                $r['lot_no'] = '';

                return $r;
            }),
            'page_header' => 'Sale Return: Inventory Detail',
        ]);
    }
}
