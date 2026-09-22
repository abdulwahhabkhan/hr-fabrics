<?php

namespace App\Http\Controllers\Reports\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesBySuitController extends Controller
{
    protected string $sessionKey = 'reports.sales.sales-by-suit';

    public function __invoke(Request $request)
    {
        $query_string = $request->only(['start_date', 'end_date']);
        if ($query_string) {
            $filters = $query_string;
            session([$this->sessionKey => $filters]);
        } else {
            $filters = session($this->sessionKey, [
                'start_date' => today()->startOfMonth()->displayDate(),
                'end_date' => today()->displayDate(),
            ]);
        }

        if ($filters) {
            $dates = [
                Carbon::parse($filters['start_date'])
                    ->startOfDay(),
                Carbon::parse($filters['end_date'])
                    ->endOfDay(),
            ];
        } else {
            $dates = [now()->startOfMonth(), now()];
        }

        $sales = OrderItem::query()
            ->with('product')
            ->select('product_id')
            ->selectRaw('SUM(total_amount) amount, SUM(total_qty) as meter , count(distinct order_id) orders ')
            ->whereIn('order_id',
                function (Builder $query) use ($dates) {
                    $query->select(['id'])->from(Order::tName())
                        ->whereBetween('confirmed_at', $dates);
                })
            ->groupBy('product_id')
            ->get()
            ->map(function ($row) {
                $row->meter = $meters = round($row->meter);
                $row->suit = $suits = round($meters / 4.5, 2);
                $row->avg_suit = round($row->amount / $suits);

                return $row;
            });
        $total_amount = round($sales->sum('amount'));
        $total_meters = round($sales->sum('meter'));
        $total_suits = round($sales->sum('suit'));

        return Inertia::render('Reports/Sales/SalesBySuit',
            [
                'filters' => $filters,
                'sales' => $sales,
                'total_amount' => $total_amount,
                'total_meters' => $total_meters,
                'total_suits' => $total_suits,
            ]);
    }
}
