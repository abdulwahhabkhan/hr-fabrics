<?php

namespace App\Http\Controllers\Sales\Order;

use App\Http\Controllers\Controller;
use App\Models\Sales\Order;
use Inertia\Inertia;

class OrderInventoryController extends Controller
{
    public function __invoke(Order $order)
    {
        $this->authorize('inventory', $order);
        $returnUrl = route('sales.orders.index');
        $inventories = $order->inventories()
            ->with([
                'product' => function ($query) {
                    $query->select('id', 'name');
                },
            ])
            ->get();

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => $returnUrl,
            'inventories' => $inventories->each(function ($r) use ($order) {
                $r['reference_no'] = $order->invoice_no;
                $r['lot_no'] = '';

                return $r;
            }),
            'page_header' => 'Sale Order: Inventory Detail',
        ]);
    }
}
