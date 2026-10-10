<?php

namespace App\Http\Controllers;

use App\Models\Purchase\Purchase;
use App\Models\Sales\Order;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Number of days (including today) shown in the sales trend chart.
     */
    protected const int TREND_DAYS = 14;

    public function __invoke(Request $request): Response
    {
        $today = today();

        return Inertia::render('dashboard', [
            'stats' => Inertia::defer(fn (): array => $this->todayStats($today), 'stats'),
            'trend' => Inertia::defer(fn (): array => $this->salesTrend($today), 'trend'),
            'recent_orders' => $this->recentOrders(),
        ]);
    }

    /**
     * Today's confirmed sales and purchase totals for the KPI tiles.
     *
     * @return array{sales: array{net: int, yesterday: int, invoices: int}, purchases: array{net: int}, meters: array{purchase: int, sales: array{net: float}}}
     */
    protected function todayStats(CarbonInterface $today): array
    {
        $sales = Order::query()
            ->confirmedOn($today)
            ->selectRaw('COUNT(*) invoices, SUM(net_total) total_amount, SUM(total_qty) total_qty')
            ->first();

        $purchases = Purchase::query()
            ->confirmedOn($today)
            ->selectRaw('SUM(total) total_amount, SUM(total_qty) total_qty')
            ->first();

        return [
            'sales' => [
                'net' => (int) $sales->total_amount,
                'yesterday' => (int) Order::query()->confirmedOn($today->copy()->subDay())->sum('net_total'),
                'invoices' => (int) $sales->invoices,
            ],
            'purchases' => [
                'net' => (int) $purchases->total_amount,
            ],
            'meters' => [
                'purchase' => (int) $purchases->total_qty,
                'sales' => [
                    'net' => round($sales->total_qty ?? 0, 2),
                ],
            ],
        ];
    }

    /**
     * Confirmed sales per day for the last TREND_DAYS days, zero-filled.
     *
     * @return list<array{date: string, total: int}>
     */
    protected function salesTrend(CarbonInterface $today): array
    {
        $start = $today->copy()->subDays(self::TREND_DAYS - 1);

        $totals = Order::query()
            ->confirmedBetween($start, $today)
            ->selectRaw('DATE(transaction_date) as day, SUM(net_total) as total')
            ->groupBy('day')
            ->toBase()
            ->pluck('total', 'day');

        return collect(range(0, self::TREND_DAYS - 1))
            ->map(function (int $offset) use ($start, $totals): array {
                $date = Carbon::parse($start)->addDays($offset)->toDateString();

                return ['date' => $date, 'total' => (int) ($totals[$date] ?? 0)];
            })
            ->all();
    }

    /**
     * Latest sales invoices for the activity list.
     *
     * @return list<array{id: int, invoice_no: ?string, customer_name: ?string, city: ?string, net_total: int, status: int, transaction_date: mixed}>
     */
    protected function recentOrders(): array
    {
        return Order::query()
            ->withCustomer()
            ->latest(Order::qCol('id'))
            ->limit(6)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'invoice_no' => $order->invoice_no,
                'customer_name' => $order->customer_name,
                'city' => $order->city,
                'net_total' => (int) $order->net_total,
                'status' => $order->status->value,
                'transaction_date' => $order->transaction_date ?? $order->created_at,
            ])
            ->all();
    }
}
