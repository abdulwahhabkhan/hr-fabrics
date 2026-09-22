<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\JournalLedger;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use App\Repositories\InventoryRepository;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegacyIncomeStatementController extends Controller
{
    protected string $sessionKey = 'accounts.income-statement';

    public function index(Request $request): Response
    {
        $seasonStart = Carbon::parse(config('store.session_start'));
        $legacyVersionEndDate = Carbon::parse(config('store.legacy_version_end_date'));

        $filters = [
            'start_date' => $seasonStart->toDateString(),
            'end_date' => $legacyVersionEndDate->toDateString(),
            'account' => '',
        ];

        $query_string = $request->only(['account', 'start_date', 'end_date']);
        if ($query_string) {
            $filters['account'] = $request->account;
            $filters['start_date'] = Carbon::create($request->start_date)->toDateString();
            $filters['end_date'] = Carbon::create($request->end_date)->toDateString();
        }
        $filters['max_date'] = $legacyVersionEndDate->toDateString();
        if ($legacyVersionEndDate->lessThan($filters['end_date'])) {
            return Inertia::render('Reports/Accounts/Legacy/IncomeStatementReport',
                [
                    'filters' => $filters,
                    'exception' => 'End date should not be greater than '.$legacyVersionEndDate->displayDate().' or check income statement for latest data!',
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

        $inventory_unit = 'inventories_bk.unit';
        $opening_inventory = (int) DB::table('inventories_bk')
            ->joinSub(
                $product_cost->where('created_at', '<=', $start_datetime),
                'product_cost',
                'product_cost.product_id',
                '=',
                'inventories_bk.product_id'
            )
            ->whereRaw('product_cost.unit = '.$inventory_unit)
            ->where('created_at', '<=', $start_datetime)
            ->sum(DB::raw('CASE WHEN '.$inventory_unit.' = "Box" THEN  qty * last_price ELSE meters * last_price END'));

        $closing_inventory = (int) DB::table('inventories_bk')
            ->joinSub(
                $product_cost_last->where('created_at', '<=', $end_datetime),
                'product_cost',
                'product_cost.product_id',
                '=',
                'inventories_bk.product_id'
            )
            ->whereRaw('product_cost.unit = '.$inventory_unit)
            ->where('created_at', '<=', $end_datetime)
            ->sum(DB::raw('CASE WHEN '.$inventory_unit.' = "Box" THEN  qty * last_price ELSE meters * last_price END'));

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

        return Inertia::render('Reports/Accounts/Legacy/IncomeStatementReport',
            [
                'filters' => $filters,
                'detail_url' => route('accounts.legacy.profit-loss.detail',
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

    public function detail(Request $request): Response
    {
        $repo = resolve(InventoryRepository::class);
        $filters = [];
        $query_string = $request->only(['start_date', 'end_date']);
        if ($query_string) {
            $filters['start_date'] = Carbon::create($request->input('start_date'))->toDateString();
            $filters['end_date'] = Carbon::create($request->input('end_date'))->toDateString();
        }
        $start_date = $filters['start_date'];
        $start_datetime = Carbon::create($start_date)->startOfDay();
        $end_date = $filters['end_date'];
        $end_datetime = Carbon::create($end_date)->endOfDay();
        $purchases = $repo->getPurchaseDetails($start_datetime, $end_datetime);
        $sales = $repo->getSalesDetails($start_datetime, $end_datetime);

        $inventory = $repo->getInventoryFullDetail($start_datetime, $end_datetime);

        return Inertia::render('Reports/Accounts/Legacy/IncomeStatementReportDetail',
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
