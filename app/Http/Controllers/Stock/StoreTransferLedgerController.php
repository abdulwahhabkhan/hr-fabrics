<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StoreTransfer;
use Inertia\Inertia;
use Inertia\Response;

class StoreTransferLedgerController extends Controller
{
    public function __invoke(StoreTransfer $storeTransfer): Response
    {
        $this->authorize('ledger', $storeTransfer);
        $journal = $storeTransfer->journal()
            ->with([
                'transactions.accountSummary',
            ])
            ->first();

        return Inertia::render('Accounts/JournalSummary', [
            'back_url' => route('stocks.store-transfers.index'),
            'journal' => $journal,
            'source_info' => null,
            'detail' => $journal?->detail,
            'reference_no' => $storeTransfer->transfer_no,
            'page_header' => 'Store Transfer: Ledger Detail',
        ]);
    }
}
