<?php

namespace App\Console\Commands\Product;

use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;
use Illuminate\Console\Command;

class UpdateAvailabilityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-availability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check and update product availability';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // set all products to unavailable
        Product::query()->update(['is_available' => false]);
        $availableProducts = Inventory::query()
            ->available()
            ->select('product_id')
            ->distinct()
            ->groupBy('product_id');
        Product::query()->whereIn('id', $availableProducts)
            ->update(['is_available' => true]);
    }
}
