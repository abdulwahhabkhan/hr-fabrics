<?php

namespace App\Http\Controllers\Reports;

use App\Enums\JournalHead;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Services\AccountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalReportController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'reports.journal';

    public function index(Request $request, AccountService $accountService): Response
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
        $query = Journal::query();
        $query->join(JournalDetail::tName(), Journal::qCol('id'), 'journal_id');
        $query->join(Account::tName(), Account::qCol('id'), 'account_id');
        $query->when($request['type'] ?? null, function ($query, $search) use ($cashAccount) {
            $query->where('head', '=', $search);
            if ($search === 'journal') {
                $query->where(Account::qCol('id'), '!=', $cashAccount->id);
            }
        });
        $query->filterContain('reference_no', $filters['reference_no'] ?? null);
        $query->filterContain('name', $filters['account'] ?? null);
        $query->filterWhere('posted_at', $filters['date'] ?? null);

        $query->select(
            [
                Journal::qCol('*'),
                JournalDetail::qCol('account_id'),
                JournalDetail::qCol('dr as debit'),
                JournalDetail::qCol('cr as credit'),
                Account::qCol('name'),
                Account::qCol('address->city as city', false),
            ]
        );
        $query->orderBy(Journal::qCol('created_at'), 'desc');
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
