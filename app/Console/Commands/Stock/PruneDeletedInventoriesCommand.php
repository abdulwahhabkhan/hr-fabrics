<?php

namespace App\Console\Commands\Stock;

use App\Models\Stock\Inventory;
use Illuminate\Console\Command;

class PruneDeletedInventoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventories:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently remove inventory records soft-deleted more than a year ago';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $count = Inventory::query()
            ->trashedBefore(now()->subYear())
            ->forceDelete();

        $this->info("$count inventory records pruned.");
    }
}
