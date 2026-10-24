<?php

namespace App\Http\Controllers\Reports;

use App\Enums\JournalHead;
use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Single-day report combining the daily summary (sales, bank book, expenses)
 * with the day's journal entries across all accounts.
 */
class DailyActivityController extends Controller
{
    public function __invoke(Request $request, ReportService $reportService, AccountService $accountService): Response
    {
        $reportDate = $request->date('date')?->toImmutable() ?? CarbonImmutable::today();
        $startOfDay = $reportDate->startOfDay();
        $endOfDay = $reportDate->endOfDay();

        $expenses = $accountService->getExpenses($startOfDay, $endOfDay, null);
        $totalExpenses = $expenses->sum('expenses');
        $totalExpensesCredit = $expenses->sum('cr');
        $cashAccount = $accountService->getCashAccount();

        return Inertia::render('Reports/Summary/DailyActivityReport', [
            'filters' => ['date' => $reportDate->format('d-M-Y')],
            'bank_details' => $reportService->bankBookDetail($startOfDay, $endOfDay),
            'expenses' => $expenses->values(),
            'expenses_total_expenses' => $totalExpenses,
            'expenses_total_cr' => $totalExpensesCredit,
            'net_expenses' => $totalExpenses - $totalExpensesCredit,
            'sale_summary' => $reportService->salesSummary($startOfDay, $endOfDay),
            'journal_rows' => $reportService->dailyJournalQuery($reportDate, JournalHead::Journal->value)->get(),
            'opening_balance' => $accountService->balanceBefore($cashAccount->id, $reportDate),
            'cash_sales' => $accountService->getCashSales($reportDate),
            'closing_balance' => $accountService->balanceOn($cashAccount->id, $reportDate),
        ]);
    }
}
