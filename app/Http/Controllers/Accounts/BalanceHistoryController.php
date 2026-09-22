<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\Account;
use App\Repositories\AccountRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BalanceHistoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $customer = $request->input('customer');

        $data = [];
        $net_balance = 0;
        $days = '-';
        $customer_id = $customer['id'] ?? 0;
        if (! empty($customer) && $customer_id) {

            $data = resolve(AccountRepository::class)->getAccountOverDueCredit($customer_id);

            $net_balance = abs($data['balance'] ?? 0);
            $posted_date = $data['transaction']['posted_at'] ?? null;
            if ($posted_date) {
                $days = ceil(($posted_date)->diffInDays());
            }

            /*$history = JournalLedger::query()
                ->selectRaw('SUM(dr) debit, SUM(cr) credit, date_format(posted_at, "%M, %Y") month')
                ->where('account_id', $customer_id)
                ->groupByRaw('date_format(posted_at, "%m %y")')
                ->orderByRaw('date_format(posted_at, "%y %m")')
                ->get();
            $total_credit = $history->sum('credit');
            $payment = 0;
            $monthly = 0;
            $data = $history->map(
                function ($row) use ($total_credit, &$payment, &$monthly) {
                    $payment += $row->debit;
                    $balance = $total_credit - $payment;
                    if ($balance < 0 && $row->debit > 0) {
                        $net_balance = $balance * -1;
                        $monthly_balance = $net_balance - $monthly;
                        $monthly += $monthly_balance;
                        $month = $row->month;
                        $month .= ' '.(today()->parse($row->month));
                        return [
                            'month' => $month,
                            'balance' => $monthly_balance,
                        ];
                    } else {
                        return false;
                    }
                })->ray()->filter()->values();

            $net_balance = collect($data)->sum('balance') ?? 0;*/
        }
        $monthly_credit = 0;
        $journals = collect($data['journal'] ?? [])
            ->groupBy('month')
            ->map(function ($item) use (&$monthly_credit) {
                $items = collect($item);
                $balance = $items->max('balance');
                $net_balance = $balance - $monthly_credit;
                $monthly_credit += $net_balance;

                return [
                    'month' => $items->first()['month'] ?? '',
                    'balance' => $net_balance,
                    'monthly_credit' => $monthly_credit,
                ];
            })
            ->values();

        return Inertia::render(
            'Accounts/Balance/HistoryIndex',
            [
                'accounts' => fn () => Account::select(['id', 'name', 'address->city as city'])
                    ->customers()
                    ->orderby('name', 'asc')->get(),
                'customer' => $customer,
                'data' => $data,
                'rows' => $journals,
                'days' => $days,
                'net_balance' => $net_balance,
            ]
        );
    }
}
