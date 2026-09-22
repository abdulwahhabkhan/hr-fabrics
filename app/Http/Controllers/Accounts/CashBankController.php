<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\JournalLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CashBankController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
        ];

        $query_string = $request->only(['start_date', 'end_date']);
        if ($query_string) {
            $filters['start_date'] = Carbon::create($request->start_date)->toDateString();
            $filters['end_date'] = Carbon::create($request->end_date)->toDateString();
        }
        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];
        $openingBalances = JournalLedger::query()
            ->select(['account_id', 'name'])
            ->selectRaw('sum(dr) as total_in')
            ->selectRaw('sum(cr) as total_out')
            ->cashBank()
            ->postedBefore($start_date)
            ->groupBy('account_id', 'name')
            ->get();
        $transactionSummary = JournalLedger::query()
            ->select(['account_id', 'name'])
            ->selectRaw('sum(dr) as total_in')
            ->selectRaw('sum(cr) as total_out')
            ->cashBank()
            ->postedBetween($start_date, $end_date)
            ->groupBy('account_id', 'name')
            ->get()->keyBy('account_id');
        $rows = [];
        foreach ($openingBalances as $balance) {
            $openingBalance = $balance->total_in - $balance->total_out;
            $transaction = $transactionSummary[$balance->account_id] ?? [];
            $totalIn = 0;
            $totalOut = 0;
            $inOutBalance = 0;
            if ($transaction) {
                $totalIn = $transaction->total_in;
                $totalOut = $transaction->total_out;
                $inOutBalance = $transaction->total_in - $transaction->total_out;
            }
            $closingBalance = $openingBalance + $inOutBalance;
            $rows[] = [
                'account_id' => $balance->account_id,
                'name' => $balance->name,
                'opening_balance' => $openingBalance,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'in_out_balance' => $inOutBalance,
                'closing_balance' => $closingBalance,
            ];
        }

        return Inertia::render('Accounts/CashBank/CashBankSummary', [
            'filters' => $filters,
            'rows' => $rows,
        ]);
    }

    public function detail(Request $request)
    {
        $query_string = $request->only(['start_date', 'end_date', 'account_id']);

        return Inertia::render('Accounts/CashBank/Detail', []);
    }
}
