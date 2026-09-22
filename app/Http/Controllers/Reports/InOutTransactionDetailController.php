<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Accounts\Period\TransactionBook;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\InOutTransactionDetailRequest;
use App\Models\Accounts\JournalLedger;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class InOutTransactionDetailController extends Controller
{
    public function __invoke(InOutTransactionDetailRequest $request): Response
    {
        $filters = [
            'category' => $request->validated('category'),
            'side' => $request->validated('side'),
            'start_date' => $request->validated('start_date') ?: today()->toDateString(),
            'end_date' => $request->validated('end_date') ?: today()->toDateString(),
        ];
        /** @var TransactionBook $transactionBook */
        $transactionBook = resolve(TransactionBook::class, [
            'startDate' => Carbon::create($filters['start_date']),
            'endDate' => Carbon::create($filters['end_date']),
        ]);

        $rows = $transactionBook->categoryQuery($filters['category'])
            ->where($filters['side'], '>', 0)
            ->orderBy('name')
            ->orderBy('posted_at')
            ->orderBy('id')
            ->get([
                'id', 'account_id', 'resource_type', 'resource_id', 'head', 'name', 'type', 'detail', 'posted_at', 'dr',
                'cr',
            ])
            ->map(fn (JournalLedger $row): array => [
                'id' => $row->id,
                'account_id' => $row->account_id,
                'name' => $row->name,
                'type' => $row->type,
                'head' => $row->head,
                'detail' => $row->detail,
                'posted_at' => $row->posted_at->toDateString(),
                'dr' => $row->dr,
                'cr' => $row->cr,
                'url' => $row->detail_link,
            ]);

        $accountTotals = $rows->groupBy('account_id')
            ->map(fn ($accountRows) => [
                'account_id' => $accountRows->first()['account_id'],
                'name' => $accountRows->first()['name'],
                'total' => $accountRows->sum($filters['side']),
            ])
            ->values();

        $categoryLabel = TransactionBook::categories()[$filters['category']];
        $sideLabel = $filters['side'] === 'dr' ? 'If Debited' : 'If Credited';

        return Inertia::render('Reports/InOutTransactionDetail', [
            'filters' => $filters,
            'label' => "{$categoryLabel} ({$sideLabel})",
            'rows' => $rows,
            'accountTotals' => $accountTotals,
            'total' => $rows->sum($filters['side']),
        ]);
    }
}
