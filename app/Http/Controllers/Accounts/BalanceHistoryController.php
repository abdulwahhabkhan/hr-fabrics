<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\Account;
use App\Services\AccountService;
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

            $data = resolve(AccountService::class)->getAccountOverDueCredit($customer_id);

            $net_balance = abs($data['balance'] ?? 0);
            $posted_date = $data['transaction']['posted_at'] ?? null;
            if ($posted_date) {
                $days = ceil(($posted_date)->diffInDays());
            }
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
