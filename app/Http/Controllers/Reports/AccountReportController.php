<?php

namespace App\Http\Controllers\Reports;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Http\Resources\Reports\AccountsResource;
use App\Models\Accounts\Account;
use App\Models\Accounts\JournalLedger;
use App\Models\City;
use App\Services\AccountService;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountReportController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'reports.pos';

    public function index(Request $request): Response
    {
        $filters = $this->filterSession($request, ['name', 'city', 'type', 'date']);
        if (empty($filters['date'])) {
            $filters['date'] = today()->toDateString();
        }

        $query = JournalLedger::query();
        $query->select(['account_id', 'name', 'city', 'type']);
        $query->selectRaw('sum(dr) as total_dr');
        $query->selectRaw('sum(cr) as total_cr');
        $query->filterWhere('name', $filters['name'] ?? null);
        $query->filterWhere('city', $filters['city'] ?? null);
        $query->filterWhere('type', $filters['type'] ?? null);
        $query->postedOnBefore($filters['date']);
        $query->groupBy('account_id', 'name', 'city', 'type');
        $query->orderBy('name');
        $query->havingRaw('total_dr - total_cr <> 0');
        $rows = $query->get();

        return Inertia::render('Reports/Accounts/AccountReport',
            [
                'filters' => $filters,
                'accounts' => AccountType::reportFilter(),
                'rows' => AccountsResource::collection($rows),
            ]);
    }

    public function receivablesByCity(Request $request)
    {
        $filters = [];
        $filter_cities = $request->input('filter_cities', []);
        $filters['filter_cities'] = $filter_cities;
        $service = resolve(AccountService::class);
        $receivables = JournalLedger::query()
            ->select(['account_id', 'type', 'name_urdu', 'name', 'city', 'credit_limit', 'is_suspended'])
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->lastCredit()
            ->where('type', AccountType::Customer->value)
            ->whereIn('city', collect($filter_cities)
                ->pluck('name')
                ->filter())
            ->groupBy('account_id')
            // ->havingRaw('total_dr > total_cr and total_cr > 0')
            ->havingRaw('total_dr != total_cr')
            ->orderBy('city')
            ->orderBy('name_urdu')
            ->get()
            ->map(function ($row) use ($service) {
                $overDue = $service->getAccountOverDueQuery($row->account_id, $row->total_cr ?? 0)
                    ->where('created_at', '<', now()->subDays(150)->toDateString())
                    ->latest()->first()?->balance;
                $row->balance_150_days = $overDue ?? 0;

                return $row;
            })
            ->groupBy('city')->map(fn ($customers, $city) => [
                'city' => $city,
                'customers' => $customers,
                'city_total' => $customers->sum(fn ($row) => $row->total_dr - $row->total_cr),
            ])->values();

        $total_balance = $receivables->sum('city_total');
        $cities = City::query()->select(['name'])
            ->orderBy('name')
            ->get()
            ->map(fn (City $r) => [
                'name' => $r->name,
                // 'label' => $r->name,
            ]);

        // dd($cities->pluck('name'));
        return Inertia::render('Reports/Receivable/ReceivableReportByCity', [
            'filters' => $filters,
            'total_balance' => $total_balance,
            'cities' => $cities,
            'rows' => $receivables,
            'today' => today()->toDateString(),
        ]);
    }

    public function receivables(Request $request)
    {
        $filters = [];
        $filter_customers = $request->input('filter_customers', []);
        $filters['filter_customers'] = $filter_customers;
        $filters['filter_type'] = $request->input('filter_type');
        $filter_posted_date = $request->input('filter_posted_date');
        if ($filter_posted_date) {
            $filter_posted_date = Carbon::parse($filter_posted_date);
            $filters['filter_post_date'] = $filter_posted_date->endOfDay()->displayDate();
        }

        $filter_customer = $filter_customers['id'] ?? '';
        $receivables = JournalLedger::query()
            ->select(['account_id', 'type', 'name_urdu', 'name', 'city'])
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->lastCredit()
            ->where('type', AccountType::Customer->value)
            ->when($filter_customer, function (Builder $query) use ($filter_customer) {
                $query->where('account_id', $filter_customer);
            })
            ->when($filter_posted_date, function (Builder $query, $filter) {
                $query->where('posted_at', '<', $filter);
            })
            ->groupBy('account_id')
            // ->havingRaw('total_dr > total_cr and total_cr > 0')
            // ->havingRaw('total_dr != total_cr')

            ->when($filters['filter_type'], function (Builder $query, $value) {
                if ($value === 'advance') {
                    $query->havingRaw('total_dr < total_cr');
                }
                if ($value === 'receivable') {
                    $query->havingRaw('total_dr > total_cr');
                }
            })
            ->when(! $filters['filter_type'], function (Builder $query) {
                $query->havingRaw('total_dr != total_cr');
            })
            // ->orderBy('city')
            ->orderByRaw('sum(dr) - sum(cr)')
            ->get()
            ->values();

        $total_balance = $receivables->sum(fn ($row) => $row->total_dr - $row->total_cr);

        $customers = Account::query()
            ->selectForSales()
            ->customers()
            ->orderByName()
            ->get();
        $average_days = Account::query()
            ->customers()
            ->where('balance', '>', 0)
            ->avg(DB::raw('datediff(now(), balance_date)'));

        return Inertia::render('Reports/Receivable/ReceivableReport', [
            'filters' => $filters,
            'total_balance' => $total_balance,
            'today' => $filter_posted_date ?? today(),
            'rows' => $receivables,
            'customers' => $customers,
            'average' => [
                'date' => today()->format('Y-m-d'),
                'average_days' => round($average_days),
            ],
        ]);
    }
}
