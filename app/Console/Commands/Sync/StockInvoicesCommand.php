<?php

namespace App\Console\Commands\Sync;

use App\Enums\StatusText;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use Illuminate\Console\Command;

class StockInvoicesCommand extends Command
{
    protected $signature = 'sync:stock-invoices';

    protected $description = 'updated the invoiced flag for stock';

    public function handle(): void
    {
        $this->freshInvoices();
    }

    public function freshInvoices(): void
    {
        $receipts = Purchase::query()
            ->where('status', StatusText::Close)->get();
        $receipts->each(function (Purchase $receipt) {
            FabricReceiving::query()
                ->whereIn('id', str($receipt->stock_ids)->explode(','))
                ->update(['invoiced' => true]);
        });
    }
}
