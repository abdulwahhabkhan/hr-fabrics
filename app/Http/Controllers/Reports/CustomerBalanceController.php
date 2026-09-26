<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Accounts\JournalLedger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerBalanceController extends Controller
{
    public function __invoke(Request $request)
    {
        $suspended = $request->integer('suspended', 0);
        $limit = $request->integer('limit', 0);
        $hasBalance = $request->integer('hasBalance', 1);
        $customers = JournalLedger::query()
            ->select(['account_id', 'name', 'city', 'is_suspended', 'credit_limit'])
            ->selectRaw('sum(dr - cr) balance')
            ->typeCustomer()
            ->when($suspended === 1, function ($query) {
                $query->where('is_suspended', 1);
            })
            ->when($suspended === 2, function ($query) {
                $query->where('is_suspended', 0);
            })
            ->when($limit === 1, function ($query) {
                $query->where(fn ($query) => $query
                    ->where('credit_limit', '>=', 1)
                    ->orWhere('is_suspended', 1));
            })
            ->when($limit === 2, function ($query) {
                $query->where('is_suspended', 0)
                    ->where(fn ($query) => $query
                        ->where('credit_limit', 0)
                        ->orWhereNull('credit_limit'));
            })
            ->when($hasBalance === 1, function ($query) {
                $query->having('balance', '<>', 0);
            })
            ->when($hasBalance === 2, function ($query) {
                $query->having('balance', '<', 1);
            })
            ->orderByRaw('sum(dr - cr) <= 0')
            ->orderBy('balance')
            ->groupBy('account_id', 'name', 'city')
            ->get()
            ->map(fn (JournalLedger $row) => [
                'id' => $row->account_id,
                'name' => $row->name,
                'suspended' => (bool) $row->is_suspended,
                'limit' => $row->credit_limit,
                'balance' => $row->balance,
                'city' => $row->city ?? '',
            ]);
        $totalBalance = $customers->sum('balance');

        return Inertia::render('Reports/Customers/BalanceReport',
            [
                'suspended' => $suspended,
                'limit' => $limit,
                'hasBalance' => $hasBalance,
                'rows' => $customers,
                'total_balance' => $totalBalance,
            ]);
    }
}
