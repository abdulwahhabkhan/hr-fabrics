<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DailyExpenseController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = [
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'account' => '',
            'group_by' => '',
        ];

        $query_string = $request->only(['account', 'start_date', 'end_date', 'group_by']);
        if ($query_string) {
            $filters['account'] = $request->account;
            $filters['group_by'] = $request->group_by;
            $filters['start_date'] = Carbon::create($request->start_date)->toDateString();
            $filters['end_date'] = Carbon::create($request->end_date)->toDateString();
        }

        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];
        $account = $filters['account'];
        $group_by = $filters['group_by'];
        $expenses = resolve(AccountService::class)->getExpenses($start_date, $end_date, $account);
        if ($group_by) {
            $expenses = $expenses->groupBy(fn ($r) => $r->account_id)->map(function ($row) {
                $summary = $row->first();
                $expenses = $row->sum('expenses');
                $cr = $row->sum('cr');

                return [
                    'id' => $summary->id,
                    'account_id' => $summary->account_id,
                    'name' => $summary->name,
                    'detail' => '',
                    'posted_at' => $summary->posted_at?->toDateString(),
                    'city' => $summary->city,
                    'expenses' => $expenses,
                    'cr' => $cr,
                ];
            });
        }
        $total_expenses = $expenses->sum('expenses');
        $total_returns = $expenses->sum('cr');

        return Inertia::render('Reports/Daily/DailyExpensesReport',
            [
                'filters' => $filters,
                'rows' => $expenses->values(),
                'total_expenses' => $total_expenses,
                'total_returns' => $total_returns,
                'net_expenses' => round($total_expenses - $total_returns),
            ]);
    }
}
