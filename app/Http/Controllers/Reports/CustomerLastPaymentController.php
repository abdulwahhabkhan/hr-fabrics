<?php

namespace App\Http\Controllers\Reports;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Accounts\Account;
use App\Models\Accounts\JournalLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerLastPaymentController extends Controller
{
    public function __invoke(Request $request)
    {
        $sort = $request->input('sort', '0');

        /*$customers = JournalLedger::query()
            ->select(['account_id', 'name', 'city'])
            ->selectRaw('sum(dr - cr) balance')
            ->selectRaw("max(case when cr > 0 then posted_at else '' end) payment_date")
            ->where('type', '=', AccountType::Customer->value)
            ->groupBy(['account_id', 'name', 'city'])
            ->having('balance', '>', 0)
            ->when($sort == 0, function ($query) {
                $query->orderBy('payment_date');
            })
            ->when($sort > 0, function ($query) {
                $query->orderByDesc('payment_date');
            })
            ->get()->map(function ($row) {
                $row['days'] = ceil(Carbon::parse($row->payment_date)->diffInDays(now()));

                return $row;
            });*/

        $customers = Account::query()
            ->customers()
            ->withBalance()
            ->when($sort === 0, function ($query) {
                $query->orderBy('balance_date');
            })
            ->when($sort > 0, function ($query) {
                $query->orderByDesc('balance_date');
            })
            ->get()
            ->map(fn (Account $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'balance' => $row->balance,
                'city' => $row->address['city'] ?? '',
                'days' => ceil($row->balance_date?->diffInDays(now())),
                'payment_date' => $row->balance_date?->toDateString(),
            ]);

        return Inertia::render('Reports/Customers/LastPayment',
            [
                'sort' => $sort,
                'rows' => $customers,
            ]);
    }
}
