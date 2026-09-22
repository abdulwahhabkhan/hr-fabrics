<?php

namespace App\Http\Controllers\Sales\Order;

use App\Http\Controllers\Controller;
use App\Models\Sales\Order;
use Inertia\Inertia;

class OrderLedgerController extends Controller
{
    public function __invoke(Order $order)
    {
        $this->authorize('ledger', $order);
        $returnUrl = route('sales.orders.index');
        $journal = $order->journal()
            ->with([
                'transactions.accountSummary',
            ])
            ->first();

        return Inertia::render('Accounts/JournalSummary', [
            'back_url' => $returnUrl,
            'journal' => $journal,
            'source_info' => null,
            'detail' => $journal->detail,
            'reference_no' => $order->invoice_no,
            'page_header' => 'Sale Invoice: Ledger Detail',
        ]);
    }
}
