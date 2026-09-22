<?php

namespace App\Console\Commands\Replay;

use App\Jobs\LogAction;
use App\Models\Purchase\Purchase;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

class PurchaseReplayCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'replay:purchase {purchase}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replay a receipt';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $receiptId = $this->argument('purchase');

        $this->info("Processing Receipt:\t {$receiptId}");
        $receipt = Purchase::find($receiptId);
        Purchase::confirmReceipt($receipt);
        LogAction::dispatch([
            'module' => Purchase::$module,
            'record_id' => $receipt->id,
            'log' => [
                'action' => 'Confirmed',
                'user' => request()->user()->id,
            ],
        ]);
        $this->info("Processing Finished Receipt:\t {$receiptId}");
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'purchase' => 'Please enter purchase Id:',
        ];
    }
}
