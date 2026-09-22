<?php

namespace App\Http\Controllers\Sales\Return;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesReturn;
use Inertia\Inertia;

class ReturnLedgerController extends Controller
{
    public function __invoke(SalesReturn $return)
    {
        $this->authorize('ledger', $return);
        $returnUrl = route('sales.returns.index');
        $journal = $return->journal()
            ->with([
                'transactions.accountSummary',
            ])
            ->first();

        return Inertia::render('Accounts/JournalSummary', [
            'back_url' => $returnUrl,
            'journal' => $journal,
            'source_info' => null,
            'detail' => $journal->detail,
            'reference_no' => $return->invoice_no,
            'page_header' => 'Sale Return Invoice: Ledger Detail',
        ]);
    }
}
