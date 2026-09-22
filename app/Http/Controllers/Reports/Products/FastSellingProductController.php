<?php

namespace App\Http\Controllers\Reports\Products;

use App\Http\Controllers\Controller;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\SalesReturn;
use App\Models\Sales\SalesReturnItem;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FastSellingProductController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = ['start_date' => now()->startOfMonth(), 'end_date' => now()];
        $query_string = $request->only(['start_date', 'end_date']);
        if ($query_string) {
            $filters['start_date'] = Carbon::create($request->start_date)->startOfDay();
            $filters['end_date'] = Carbon::create($request->end_date)->endOfDay();

        }
        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];
        $products = $this->getProductSold($start_date, $end_date);
        $returns = $this->getProductReturn($start_date, $end_date);
        $total_qty = $products->sum('total_qty');
        $total_meters = $products->sum('total_meters');
        $return_qty = $returns->sum('total_qty');
        $return_meters = $returns->sum('total_meters');
        $net_total_qty = $total_qty - $return_qty;
        $net_total_meters = $total_meters - $return_meters;

        $ordersReturns = $products
            ->concat($returns);

        $vendors = $ordersReturns
            ->groupBy('vendor_name')
            ->map(function ($r, $name) use ($net_total_meters) {
                $sold = $r->where('transaction_type', 'sales');
                $returned = $r->where('transaction_type', 'return');
                $soldQty = $sold?->sum('total_qty');
                $returnQty = $returned?->sum('total_qty');
                $soldMeters = $sold?->sum('total_meters');
                $returnMeters = $returned?->sum('total_meters');
                $net_qty = $soldQty - $returnQty;
                $net_meters = $soldMeters - $returnMeters;

                return [
                    'name' => $name,
                    'sold_qty' => $soldQty,
                    'returned_qty' => $returnQty,
                    'sold_meter' => $soldMeters,
                    'returned_meter' => $returnMeters,
                    'net_qty' => $net_qty,
                    'net_meter' => $net_meters,
                    'percentage' => round($net_meters / $net_total_meters * 100, 1),
                ];
            })->values();

        $transactions = $ordersReturns
            ->groupBy('product_id')
            ->map(function ($r) use ($net_total_meters) {
                $sold = $r->where('transaction_type', 'sales');
                $returned = $r->where('transaction_type', 'return');
                $soldQty = $sold?->sum('total_qty');
                $returnQty = $returned?->sum('total_qty');
                $soldMeters = $sold?->sum('total_meters');
                $returnMeters = $returned?->sum('total_meters');
                $net_qty = $soldQty - $returnQty;
                $total_meters = $soldMeters - $returnMeters;
                // $r->percentage = round($r->total_meters / $net_meters * 100, 1);
                $item = $r->first();

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'unit' => $item->unit,
                    'total_qty' => $net_qty,
                    'total_meters' => $total_meters,
                    'percentage' => round($total_meters / $net_total_meters * 100, 1),
                    'sold' => $sold,
                    'returned' => $returned,
                ];
            })
            ->sortByDesc('total_meters')
            ->values();

        return Inertia::render('Reports/Products/FastMovingProductReport', [
            'products' => $transactions,
            'total_qty' => $net_total_qty,
            'total_meters' => $net_total_meters,
            'vendors' => $vendors,
            'filters' => [
                'start_date' => $start_date->format('Y-m-d'),
                'end_date' => $end_date->format('Y-m-d'),
            ],
        ]);
    }

    public function getProductSold($start_date, $end_date)
    {
        return OrderItem::query()
            ->joinRelation('product.vendor')
            ->select([
                'product_id', 'products.name as product_name', 'accounts.name as vendor_name', 'unit', 'vendor_id',
            ])
            ->selectRaw("'sales' as transaction_type")
            ->selectRaw('SUM(qty) as total_qty')
            ->selectRaw('SUM(total_qty) as total_meters')
            ->whereIn('order_id',
                function (Builder $query) use ($start_date, $end_date) {
                    $query->fromSub(Order::query()
                        ->confirmedBetween($start_date, $end_date)
                        ->select('id')->toRawSql(), 'orders');

                })
            ->groupBy(['product_id', 'products.name', 'accounts.name', 'unit', 'transaction_type', 'vendor_id'])
            ->orderByDesc('total_meters')
            ->get();
    }

    public function getProductReturn($start_date, $end_date)
    {
        $closedOrders = SalesReturn::query()
            ->select(['id'])
            ->confirmedBetween($start_date, $end_date)
            ->toRawSql();

        return SalesReturnItem::query()
            ->joinRelation('product.vendor')
            ->select([
                'product_id',
                'products.name as product_name',
                'unit',
                'vendor_id',
                'accounts.name as vendor_name',
            ])
            ->selectRaw("'return' as transaction_type")
            ->selectRaw('SUM(qty) as total_qty')
            ->selectRaw('SUM(total_qty) as total_meters')
            ->whereRaw("sales_return_id in ($closedOrders)")
            ->groupBy(['product_id', 'product_name', 'unit', 'transaction_type', 'vendor_id', 'accounts.name'])
            ->orderByDesc('total_meters')
            ->get();
    }
}
