<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\JournalLedger;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use App\Models\Stock\Inventory;
use App\Services\InventoryService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomeStatementController extends Controller
{
    protected string $sessionKey = 'accounts.income-statement';

    public function index(Request $request): Response
    {
        $seasonStart = Carbon::parse(config('store.session_start'));
        $legacyVersionEndDate = Carbon::parse(config('store.legacy_version_end_date'));
        if ($seasonStart->lessThan($legacyVersionEndDate)) {
            $seasonStart = $legacyVersionEndDate;
        }
        $filters = [
            'start_date' => $seasonStart->toDateString(),
            'end_date' => today()->toDateString(),
            'account' => '',
        ];
        $query_string = $request->only(['account', 'start_date', 'end_date']);
        if ($query_string) {
            $filters['account'] = $request->account;
            $filters['start_date'] = Carbon::create($request->start_date)->toDateString();
            $filters['end_date'] = Carbon::create($request->end_date)->toDateString();
        }
        $filters['min_date'] = $legacyVersionEndDate->toDateString();
        if ($legacyVersionEndDate->greaterThan($filters['start_date'])) {
            return Inertia::render('Reports/Accounts/IncomeStatementReport',
                [
                    'filters' => $filters,
                    'exception' => 'Start date should be greater than '.$legacyVersionEndDate->displayDate().' or check legacy version for old data!',
                ]);
        }
        $start_date = $filters['start_date'];
        $start_datetime = Carbon::create($start_date)->startOfDay();
        $end_date = $filters['end_date'];
        $end_datetime = Carbon::create($end_date)->endOfDay();
        $total_sales = Order::query()
            ->confirmedBetween($start_datetime, $end_datetime)
            ->sum('net_total');
        $total_sales_returns = (int) SalesReturn::query()
            ->confirmedBetween($start_datetime, $end_datetime)
            ->sum('total_amount');

        $total_purchases = Purchase::query()
            ->confirmedBetween($start_datetime, $end_datetime)
            ->sum('total');
        $total_purchases_returns = PurchaseReturn::query()
            ->confirmedBetween($start_datetime, $end_datetime)
            ->sum('total_amount');
        $product_cost = PurchaseItem::query()
            ->select([
                'product_id', 'unit',
            ])
            ->selectRaw('MAX(price) as last_price')
            ->groupBy(['product_id', 'unit']);

        $product_cost_last = PurchaseItem::query()
            ->select([
                'product_id', 'unit',
            ])
            ->selectRaw('MAX(price) as last_price')
            ->groupBy(['product_id', 'unit']);

        $opening_inventory = (int) Inventory::query()
            ->availableBefore($start_datetime)
            ->stockValue(true)
            ->first()->stock_value ?? 0;

        $closing_inventory = (int) Inventory::query()
            ->availableAfter($end_datetime)
            ->stockValue(true)
            ->first()->stock_value ?? 0;

        $sales = [];
        $sales[] = ['desc' => 'Total Revenue', 'amount' => $total_sales];
        $sales[] = ['desc' => 'Sales Return', 'amount' => $total_sales_returns * -1];

        $net_sales = $total_sales - $total_sales_returns;
        $sales[] = ['desc' => 'Net Sales', 'total' => $net_sales];
        $purchases = [];
        $purchases[] = [
            'desc' => 'Opening Inventory ',
            'amount' => $opening_inventory,
        ];
        $purchases[] = ['desc' => 'Purchases', 'amount' => $total_purchases];

        $purchases[] = [
            'desc' => 'Purchase Return',
            'amount' => $total_purchases_returns * -1,
        ];

        $cost_of_goods = (
            $opening_inventory
            + $total_purchases
        ) - (
            $total_purchases_returns
            + $closing_inventory
        );

        $purchases[] = [
            'desc' => 'Closing Inventory',
            'amount' => ($closing_inventory) * -1,
            'total' => $cost_of_goods,
        ];
        $other_incomes = JournalLedger::query()
            ->select(['name as desc', DB::raw('SUM(cr) as amount')])
            ->typeOtherReceivables()
            ->whereBetween('posted_at', [$start_date, $end_date])
            ->where('cr', '>', 0)
            ->groupBy(['name'])->get();
        $other_incomes_total = $other_incomes->sum('amount');
        $expenses = JournalLedger::query()
            ->select(['name as desc', DB::raw('SUM(dr-cr) as amount')])
            ->typeExpense()
            ->whereBetween('posted_at', [$start_date, $end_date])
            // ->where('dr', '>', 0)
            ->groupBy(['name'])
            ->having('amount', '<>', 0)
            ->get();

        $total_expenses = $expenses->sum('amount');

        $gross_profit = round($net_sales - $cost_of_goods);
        $net_profit = round($gross_profit - $total_expenses);

        return Inertia::render('Reports/Accounts/IncomeStatementReport',
            [
                'filters' => $filters,
                'detail_url' => route('accounts.income-statement.detail',
                    [
                        'start_date' => $filters['start_date'],
                        'end_date' => $filters['end_date'],
                    ]
                ),
                'sales' => $sales,
                'purchases' => $purchases,
                'expenses' => $expenses,
                'other_incomes' => $other_incomes,
                'net_profit' => $net_profit,
                'gross_profit' => $gross_profit,
                'total_expenses' => -1 * $total_expenses,
                'total_other_income' => $other_incomes_total ?? 0,
                'configs' => [
                    'open_inventory' => $opening_inventory,
                    'purchases' => $total_purchases,
                    'purchase_return' => $total_purchases_returns,
                    'closing_inventory' => $closing_inventory,
                ],
            ]);
    }

    public function detail(Request $request, InventoryService $service): Response
    {
        $filters = [];
        $filters['start_date'] = Carbon::create($request->input('start_date'))->toDateString();
        $filters['end_date'] = Carbon::create($request->input('end_date'))->toDateString();
        $start_datetime = Carbon::create($filters['start_date'])->startOfDay();
        $end_datetime = Carbon::create($filters['end_date'])->endOfDay();
        $purchases = $service->getPurchaseDetails($start_datetime, $end_datetime);
        $sales = $service->getSalesDetails($start_datetime, $end_datetime);

        $inventory = $service->getInventoryFullDetail($start_datetime, $end_datetime);

        return Inertia::render('Reports/Accounts/IncomeStatementReportDetail',
            [
                'filters' => $filters,
                'purchases' => $purchases,
                'sales' => $sales,
                'gross_profit' => $sales->sum('total_profit'),
                'total_sales' => $sales->sum('total_sales'),
                'total_cost' => $sales->sum('total_cost'),
                'inventory' => $inventory,
            ]);
    }
}
