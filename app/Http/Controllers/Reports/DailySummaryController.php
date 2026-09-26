<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DailySummaryController extends Controller
{
    public function __invoke(Request $request, ReportService $reportService, AccountService $accountService)
    {
        $filter['start_date'] = today()->format('Y-m-d');
        $filter['end_date'] = today()->format('Y-m-d');
        $query_string = $request->only(['start_date', 'end_date']);
        if ($query_string) {
            $filter['start_date'] = $request->input('start_date');
            $filter['end_date'] = $request->input('end_date');
        }
        $summary_start_date = Carbon::create($filter['start_date'])->toImmutable();
        $summary_end_date = Carbon::create($filter['end_date'])->toImmutable();
        $service = resolve(ReportService::class);
        $startOfDay = $summary_start_date->startOfDay();
        $endOfDay = $summary_end_date->endOfDay();
        $bankDetails = $service->bankBookDetail($startOfDay, $endOfDay);

        $expenses = $accountService->getExpenses($startOfDay, $endOfDay, null);
        $total_expenses = $expenses->sum('expenses');
        $total_expenses_cr = $expenses->sum('cr');
        $sale_summary = $service->salesSummary($startOfDay, $endOfDay);
        $net_expenses = $total_expenses - $total_expenses_cr;
        $filter['start_date'] = $summary_start_date->format('d-M-Y');
        $filter['end_date'] = $summary_end_date->format('d-M-Y');

        return Inertia::render('Reports/Summary/DailyReport', [
            'bank_details' => $bankDetails,
            'filters' => $filter,
            'expenses' => $expenses->values(),
            'expenses_total_expenses' => $total_expenses,
            'expenses_total_cr' => $total_expenses_cr,
            'net_expenses' => $net_expenses,
            'sale_summary' => $sale_summary,
        ]);
    }
}
