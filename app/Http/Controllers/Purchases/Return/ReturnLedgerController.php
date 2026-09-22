<?php

namespace App\Http\Controllers\Purchases\Return;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseReturn;
use Inertia\Inertia;

class ReturnLedgerController extends Controller
{
    public function __invoke(PurchaseReturn $return)
    {
        $this->authorize('ledger', $return);
        $returnUrl = route('purchases.por.index');
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
            'page_header' => 'Fabric PO Return: Ledger Detail',
        ]);
    }
}
