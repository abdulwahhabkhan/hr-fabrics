<?php

namespace App\Http\Controllers\Reports;

use App\Enums\JournalHead;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Services\AccountService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalReportController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'reports.journal';

    public function index(Request $request, AccountService $accountService, ReportService $reportService): Response
    {
        $filters = $this->filterSession($request, ['date', 'type']);
        if (empty($filters['date'])) {
            $filters['date'] = now()->format('Y-m-d');
        }
        if (empty($filters['type'])) {
            $filters['type'] = 'journal';
        }
        $transactionDate = Carbon::parse($filters['date'])->toImmutable();
        $cashAccount = $accountService->getCashAccount();
        $query = $reportService->dailyJournalQuery($transactionDate, $request['type'] ?? null);
        $query->filterContain('reference_no', $filters['reference_no'] ?? null);
        $query->filterContain('name', $filters['account'] ?? null);
        $data = $query->get();
        // $opening_balance = JournalReportRepository::getCashOpeningBalance($filters['date']);
        // $total_cash_balance = JournalReportRepository::getCashClosingBalance($filters['date']);
        $opening_balance = $accountService->balanceBefore($cashAccount->id, $transactionDate);
        $total_cash_balance = $accountService->balanceOn($cashAccount->id, $transactionDate);
        $cash_sales = $accountService->getCashSales($transactionDate);
        $filters['date'] = $transactionDate->format('d-M-Y');

        return Inertia::render('Reports/Accounts/JournalReport',
            [
                'filters' => $filters,
                'rows' => $data,
                'opening_balance' => $opening_balance,
                'cash_sales' => $cash_sales,
                'closing_balance' => $total_cash_balance,
                'accounts' => [JournalHead::Journal->value, JournalHead::Purchases->value, JournalHead::Sales->value],
            ]);
    }
}
