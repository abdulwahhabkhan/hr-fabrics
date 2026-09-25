<?php

namespace App\Http\Controllers;

use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WidgetController extends Controller
{
    protected string $sessionKey = 'widget.sale-avg';

    public function avgSalesMeter(Request $request)
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
                    ->startOfDay()
                    ->format('Y-m-d H:i:s'),
                Carbon::parse($filters['end_date'])
                    ->endOfDay()
                    ->format('Y-m-d H:i:s'),
            ];
        } else {
            $dates = [now()->startOfMonth(), now()];
        }

        $sales = OrderItem::query()
            ->selectRaw('SUM(total_amount) amount, SUM(total_qty) as qty, count(distinct order_id) orders, unit ')
            ->whereIn('order_id',
                Order::query()
                    ->select(['id'])
                    ->confirmedBetween($dates[0], $dates[1])
            )->groupBy('unit')
            ->get()->map(function ($row) {
                $row->qty = (int) round($row->qty);
                $row->avg_meter = round($row->amount / $row->qty);
                $row->per_trans = round($row->amount / $row->orders);

                return $row;
            });

        return Inertia::render('Widget/AvgSaleWidget',
            [
                'filters' => $filters,
                'sales' => $sales,
            ]);
    }
}
