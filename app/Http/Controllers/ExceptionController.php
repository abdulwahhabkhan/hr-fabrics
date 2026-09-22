<?php

namespace App\Http\Controllers;

use App\Actions\LogAction\RecordAction;
use App\Models\Accounts\Journal;
use App\Services\InventoryService;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ExceptionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Exceptions/ExceptionIndex', [
            'account_exceptions' => $this->getAccountExceptions()->count(),
            'stock_exceptions' => collect(InventoryService::getStockStatus())->count(),
        ]);
    }

    public function accounts(): Response
    {
        return Inertia::render('Exceptions/AccountExceptions', [
            'ledgers' => $this->getAccountExceptions(),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function journalDetail(Request $request): Response|RedirectResponse
    {
        $ledgers_total = Journal::query()
            ->where('resource_type', $request->input('type'))
            ->where('resource_id', $request->input('resource_id'))->count();

        if ($ledgers_total > 1 && $request->input('action') === 'delete') {
            $ledger = Journal::query()
                ->where('resource_type', $request->input('type'))
                ->where('id', $request->input('id'))
                ->first();
            DB::transaction(function () use ($request, $ledger) {
                resolve(RecordAction::class)->handle($ledger, $request->user(), 'Sale Order UnLocked');
                $ledger->transactions()->delete();
                $ledger->delete();
            });

            return redirect()->route('exceptions.accounts.detail',
                ['resource_id' => $ledger->resource_id, 'type' => $ledger->resource_type]
            );
        }
        $ledgers = Journal::query()
            ->where('resource_type', $request->input('type'))
            ->where('resource_id', $request->input('resource_id'))
            ->with(['user', 'transactions', 'transactions.Account'])
            ->get();

        return Inertia::render('Exceptions/JournalDetail', [
            'ledgers' => $ledgers,
            'filters' => $request->only(['type', 'resource_id']),
        ]);
    }

    public function getAccountExceptions()
    {
        return Journal::query()
            ->select([
                'resource_id',
                'resource_type',
                DB::raw('count(*) as total'),
            ])
            ->whereNotNull('resource_id')->whereNotNull('resource_type')
            ->groupBy(['resource_type', 'resource_id'])
            ->havingRaw('total > 1')
            ->get();
    }

    public function stockExceptions(): Response
    {
        return Inertia::render('Exceptions/StockExceptions', [
            'exceptions' => InventoryService::getStockStatus(),
        ]);
    }
}
