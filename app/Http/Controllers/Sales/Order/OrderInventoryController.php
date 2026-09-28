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
        $order->load('customer:id,name,address');

        return Inertia::render('Inventory/StockInventoryView', [
            'back_url' => $returnUrl,
            'inventories' => $inventories->each(function ($r) use ($order) {
                $r['reference_no'] = $order->invoice_no;
                $r['lot_no'] = '';

                return $r;
            }),
            'page_header' => 'Sale Order: Inventory Detail',
            'parent' => [
                'label' => 'Sales invoice',
                'reference_no' => $order->invoice_no,
                'url' => route('sales.orders.show', $order),
                'status' => $order->status?->name,
                'party' => [
                    'label' => 'Customer',
                    'name' => $order->customer?->name,
                    'address' => $order->customer?->address,
                ],
                'details' => [
                    ['label' => 'Date', 'value' => $order->transaction_date?->toDateString(), 'type' => 'date'],
                    ['label' => 'Payment mode', 'value' => $order->payment_mode],
                    ['label' => 'Total qty', 'value' => $order->total_qty, 'type' => 'number'],
                    ['label' => 'Net total', 'value' => $order->net_total, 'type' => 'number'],
                ],
            ],
        ]);
    }
}
