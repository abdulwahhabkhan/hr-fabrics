<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Accounts\Balance\BankBook;
use App\Actions\Accounts\Balance\CashBook;
use App\Actions\Accounts\Period\TransactionBook;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class InOutTransactionSummaryController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = [
            'start_date' => $request->input('start_date') ?: today()->toDateString(),
            'end_date' => $request->input('end_date') ?: today()->toDateString(),
        ];

        $summary_start_date = Carbon::create($filters['start_date']);
        $summary_end_date = Carbon::create($filters['end_date']);
        $bankOpeningBalance = new BankBook()->openingBalance($summary_start_date);
        $bankClosingBalance = new BankBook()->closingBalance($summary_end_date);
        $cashOpeningBalance = new CashBook()->openingBalance($summary_start_date);
        $cashClosingBalance = new CashBook()->closingBalance($summary_end_date);
        /** @var TransactionBook $transactionBook */
        $transactionBook = resolve(TransactionBook::class, [
            'startDate' => $summary_start_date,
            'endDate' => $summary_end_date,
        ]);
        $totalAdvances = $transactionBook->totalAdvances();
        $totalLiability = $transactionBook->totalLiability();
        $totalCharity = $transactionBook->totalCharity();
        $totalDrawings = $transactionBook->totalDrawings();
        $totalExpenses = $transactionBook->totalExpenses();
        $totalCommission = $transactionBook->totalCommission();
        $totalSuppliers = $transactionBook->totalSuppliers();
        $totalPayables = $transactionBook->totalPayables();
        $totalOthersReceivables = $transactionBook->totalOtherReceivables();
        $totalCustomers = $transactionBook->totalCustomers();
        $cashSales = $transactionBook->totalCashSales();
        $totalReceived = $cashSales->total_dr + $totalAdvances->total_cr
            + $totalCharity->total_cr
            + $totalDrawings->total_cr
            + $totalExpenses->total_cr
            + $totalCommission->total_cr
            + $totalSuppliers->total_cr
            + $totalOthersReceivables->total_cr
            + $totalCustomers->total_cr
            + $totalPayables->total_cr
            + $totalLiability->total_cr;
        $totalPaid = $totalAdvances->total_dr
            + $totalCharity->total_dr
            + $totalDrawings->total_dr
            + $totalExpenses->total_dr
            + $totalCommission->total_dr
            + $totalSuppliers->total_dr
            + $totalOthersReceivables->total_dr
            + $totalCustomers->total_dr
            + $totalPayables->total_dr
            + $totalLiability->total_dr;

        //
        $expenses = $totalExpenses->toArray();
        $expenses['total_dr'] += $totalCommission->total_dr;
        $expenses['total_cr'] += $totalCommission->total_cr;

        return Inertia::render('Reports/InOutTransactionsSummary', [
            'filters' => $filters,
            'bank_opening_balance' => $bankOpeningBalance,
            'bank_closing_balance' => $bankClosingBalance,
            'cash_opening_balance' => $cashOpeningBalance,
            'cash_closing_balance' => $cashClosingBalance,
            'opening_balance' => $bankOpeningBalance + $cashOpeningBalance,
            'closing_balance' => $bankClosingBalance + $cashClosingBalance,
            'total_advances' => $totalAdvances->toArray(),
            'total_charity' => $totalCharity->toArray(),
            'total_drawings' => $totalDrawings,
            'total_expenses' => $expenses,
            'total_others_receivables' => $totalOthersReceivables,
            'total_customers' => $totalCustomers,
            'total_payables' => $totalPayables,
            'total_suppliers' => $totalSuppliers,
            'total_received' => $totalReceived,
            'total_paid' => $totalPaid,
            'total_cash_sales' => $cashSales,
            'total_liability' => $totalLiability,
        ]);
    }
}
