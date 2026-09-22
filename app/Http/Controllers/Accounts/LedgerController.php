<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\Account;
use App\Models\Accounts\JournalLedger;
use App\Services\AccountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LedgerController extends Controller
{
    public function index(): Response
    {
        $accounts = Account::select(['id', 'name', 'type', 'address->city as city'])
            ->orderby('name', 'asc')->get();

        return Inertia::render(
            'Accounts/Ledgers/LedgerIndex',
            [
                'accounts' => $accounts,
                'date' => [
                    'start_date' => now()->subYear()->displayDate(),
                    'end_date' => now()->displayDate(),
                ],
            ]
        );
    }

    public function show(Account $account, Request $request, AccountService $service): Response
    {
        $start_date = Carbon::create($request->input('start_date'));
        $end_date = Carbon::create($request->input('end_date'))->endOfDay();
        $balance = $service->balanceInfoByDate($account->id, $start_date);
        $net_balance = $balance->balance();
        $ledgers = JournalLedger::query()
            ->where('account_id', $account->id)
            ->postedBetween($start_date, $end_date)
            ->get()
            ->each(function (JournalLedger $item) use (&$net_balance) {
                $net_balance += $item->dr - $item->cr;
                $item->balance = $net_balance;
                $item->url = $item->detail_link;

                return $item;
            });
        $sum_dr = $ledgers->sum('dr');
        $sum_cr = $ledgers->sum('cr');

        return Inertia::render(
            'Accounts/Ledgers/LedgerView',
            [
                'account' => $account,
                'ledger' => $ledgers,
                'balance' => $balance,
                'net_balance' => $net_balance,
                'start_balance' => $balance->balance(),
                'ledger_sum' => [
                    'total_dr' => $sum_dr,
                    'total_cr' => $sum_cr,
                ],
                'date' => [
                    'start_date' => $start_date->date(),
                    'end_date' => $end_date->date(),
                ],
            ]
        );
    }
}
