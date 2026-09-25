<?php

namespace App\Http\Controllers\Reports;

use App\Enums\PaymentMode;
use App\Http\Controllers\Controller;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashCreditSaleController extends Controller
{
    protected string $sessionKey = 'reports.so.cash-credit';

    public function __invoke(Request $request): Response
    {
        $filters = ['start_date' => today()->toDateString(), 'end_date' => today()->toDateString()];
        $query_string = $request->only(['start_date', 'end_date']);
        if ($query_string) {
            $filters['start_date'] = $request->start_date;
            $filters['end_date'] = $request->end_date;
        }
        $start_date = Carbon::create($filters['start_date'])->startOfDay();
        $end_date = Carbon::create($filters['end_date'])->endOfDay();
        $filters['start_date'] = $start_date->toDateString();
        $filters['end_date'] = $end_date->toDateString();
        $sales = Order::query()
            ->select([
                DB::raw('SUM(net_total) as total_sales'),
                DB::raw('SUM(CASE WHEN paid = 1 THEN net_total ELSE 0 END) as cash_sales'),
                DB::raw('SUM(CASE WHEN paid = 0 THEN net_total ELSE 0 END) as credit_sales'),
            ])
            ->selectRaw('transaction_date')
            ->confirmedBetween($start_date, $end_date)
            ->groupBy('transaction_date')
            ->get()
            ->keyBy('transaction_date');

        $paymentMode = PaymentMode::Cash->value;
        $returns = SalesReturn::query()
            ->select([
                DB::raw('SUM(total_amount) as total_returns'),
                DB::raw('SUM(CASE WHEN payment_mode = "'.$paymentMode.'" THEN total_amount ELSE 0 END) as cash_returns'),
                DB::raw('SUM(CASE WHEN payment_mode != "'.$paymentMode.'" THEN total_amount ELSE 0 END) as credit_returns'),
            ])
            ->selectRaw('transaction_date')
            ->confirmedBetween($start_date, $end_date)
            // ->whereBetween(SalesReturn::qCol('created_at'), [$start_date, $end_date])
            ->groupBy('transaction_date')
            ->get()
            ->keyBy('transaction_date');

        $sales_log = collect([])
            ->merge($sales->keys())
            ->merge($returns->keys())
            ->sort()
            ->unique();

        $sale_summary = [];
        foreach ($sales_log as $date) {
            $sale = $sales[$date] ?? [];
            $sale_return = $returns[$date] ?? [];

            $cash_sale = $sale['cash_sales'] ?? 0;
            $credit_sale = $sale['credit_sales'] ?? 0;

            $cash_return = $sale_return['cash_returns'] ?? 0;
            $credit_return = $sale_return['credit_returns'] ?? 0;

            $total_credit = $credit_sale - $credit_return;
            $total_cash = $cash_sale - $cash_return;
            $net_sale = $total_credit + $total_cash;
            $sale_summary[] = [
                'date' => Carbon::parse($date)->toDateString(),
                'cash_sale' => $cash_sale,
                'credit_sale' => $credit_sale,

                'cash_return' => $cash_return,
                'credit_return' => $credit_return,

                'total_cash' => $total_cash,
                'total_credit' => $total_credit,
                'net_sale' => $net_sale,
            ];
        }
        $total_cash = collect($sale_summary)->sum('total_cash');
        $total_credit = collect($sale_summary)->sum('total_credit');

        return Inertia::render('Reports/Daily/CashCreditReport',
            [
                'filters' => $filters,
                'sale_summary' => $sale_summary,
                'total_cash' => $total_cash,
                'total_credit' => $total_credit,
            ]);
    }
}
