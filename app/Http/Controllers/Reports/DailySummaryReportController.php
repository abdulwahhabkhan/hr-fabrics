<?php

namespace App\Http\Controllers\Reports;

use App\Enums\PaymentMode;
use App\Http\Controllers\Controller;
use App\Models\Accounts\Account;
use App\Models\Accounts\JournalLedger;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailySummaryReportController extends Controller
{
    public function dailySales(Request $request): Response
    {
        $filters = ['type' => 'city', 'account' => '', 'start_date' => today(), 'end_date' => today()];
        $query_string = $request->only(['start_date', 'end_date', 'type', 'account']);
        if ($query_string) {
            $filters['start_date'] = $request->start_date;
            $filters['end_date'] = $request->end_date;
            $filters['account'] = $request->account;
            $filters['type'] = $request->type;
        }
        $search = $filters['account'];
        $type = $filters['type'];
        $sale_column = 'address->'.$type;
        $receipt_column = $type;
        if (mb_strtolower($type) === 'customer') {
            $sale_column = 'name';
            $receipt_column = 'name';
        }
        $start_date = Carbon::create($filters['start_date'])->startOfDay();
        $end_date = Carbon::create($filters['end_date'])->endOfDay();
        $sales = Order::query()
            ->select([
                Account::qCol($sale_column.' as account', false),
                DB::raw('SUM(net_total) as total_sales'),
                DB::raw('SUM(CASE WHEN paid = 1 THEN net_total ELSE 0 END) as cash_sales'),
                DB::raw('SUM(CASE WHEN paid = 0 THEN net_total ELSE 0 END) as credit_sales'),
            ])
            ->join(Account::tName(), 'customer_id', '=', Account::qCol('id'))
            ->confirmedBetween($start_date, $end_date)
            ->when($search, function ($query, $search) use ($sale_column) {
                $query->where($sale_column, 'like', '%'.$search.'%');
            })
            ->groupBy(['account'])
            ->get();
        $total_sales = $sales->sum('total_sales');
        $sales = $sales->keyBy(['account'])->toArray();
        $modeCash = PaymentMode::Cash->value;
        $returns = SalesReturn::query()
            ->select([
                Account::qCol($sale_column.' as account', false),
                DB::raw('SUM(total_amount) as total_returns'),
                DB::raw('SUM(CASE WHEN payment_mode = "'.$modeCash.'" THEN total_amount ELSE 0 END) as cash_returns'),
                DB::raw('SUM(CASE WHEN payment_mode != "'.$modeCash.'" THEN total_amount ELSE 0 END) as credit_returns'),
            ])
            ->join(Account::tName(), 'customer_id', '=', Account::qCol('id'))
            ->confirmedBetween($start_date, $end_date)
            ->when($search, function ($query, $search) use ($sale_column) {
                $query->where($sale_column, 'like', '%'.$search.'%');
            })
            ->groupBy(['account'])
            ->get();
        $total_returns = $returns->sum('total_returns');
        $receipts = JournalLedger::query()
            ->select([
                $receipt_column.' as account', DB::raw('SUM(cr) as receipts'),
            ])
            ->whereBetween('posted_at', [$start_date, $end_date])
            ->typeReceivable()
            ->headJournal()
            ->where('cr', '>', 0)
            ->when($search, function ($query, $search) use ($receipt_column) {
                $query->where($receipt_column, 'like', '%'.$search.'%');
            })
            ->groupBy(['account'])
            ->get();
        $returns->each(function ($row) use (&$sales) {
            $sale = $sales[$row->account] ?? [];
            $sales[$row->account] = array_merge($sale, $row->toArray());
        });

        $receipts->each(function ($row) use (&$sales) {
            $sale = $sales[$row->account] ?? [];
            $sales[$row->account] = array_merge($sale, $row->toArray());
        });
        $total_receipts = $receipts->sum('receipts');

        return Inertia::render('Reports/Daily/DailySummaryReport',
            [
                'filters' => $filters,
                'rows' => collect($sales)->values(),
                'total_sales' => $total_sales,
                'total_receipts' => $total_receipts,
                'total_returns' => $total_returns,
            ]);
    }
}
