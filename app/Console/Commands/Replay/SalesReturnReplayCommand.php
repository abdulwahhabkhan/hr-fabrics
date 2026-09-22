<?php

namespace App\Console\Commands\Replay;

use App\Models\Sales\SalesReturn;
use Illuminate\Console\Command;

class SalesReturnReplayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'replay:sales.return';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push sales returns.items to items table';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $returns = SalesReturn::query()->get();
        foreach ($returns as $return) {
            $return->updateItems();
        }
    }
}
