<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Purchase\Purchase;
use Inertia\Inertia;

class PurchaseLedgerController extends Controller
{
    public function __invoke(Purchase $receipt)
    {
        $this->authorize('ledger', $receipt);
        $returnUrl = route('purchases.pos.index');
        $journal = $receipt->journal()
            ->with([
                'transactions.accountSummary',
            ])
            ->first();

        return Inertia::render('Accounts/JournalSummary', [
            'back_url' => $returnUrl,
            'journal' => $journal,
            'source_info' => null,
            'detail' => $journal->detail,
            'reference_no' => $receipt->invoice_no,
            'page_header' => 'Fabric Receiving Invoice: Ledger Detail',
        ]);
    }
}
