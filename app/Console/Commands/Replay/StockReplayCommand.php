<?php

namespace App\Console\Commands\Replay;

use App\Actions\Inbound\FabricReceiving\FabricReceivingConfirmed;
use App\Jobs\ConversionTransaction;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Stock\Conversion;
use App\Models\Stock\Inventory;
use Illuminate\Console\Command;

class StockReplayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'replay:stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove inventory and re-load the inventory';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Inventory::query()->truncate();

        $this->replayStockReceiving();
        $this->replayStockReturn();

        $this->replayStockConversion();

        $this->replaySalesOrders();
        $this->replaySalesReturns();

        return 0;
    }

    public function replayStockReceiving()
    {
        $stocks = FabricReceiving::all();
        $this->info('Receiving Total : '.count($stocks));
        $stockConfirmed = resolve(FabricReceivingConfirmed::class);
        foreach ($stocks as $stock) {
            $stockConfirmed->handle($stock);
        }
    }

    public function replayStockReturn()
    {
        $returns = PurchaseReturn::all();
        $this->info('Receiving Returns Total : '.count($returns));
        foreach ($returns as $return) {
            $items = $return->items;
            foreach ($items as $item) {
                PurchaseReturn::processInventory($item, $return);
            }
        }
    }

    public function replayStockConversion()
    {
        $rows = Conversion::all();
        $this->info('Stock Conversion Total : '.count($rows));
        foreach ($rows as $row) {
            ConversionTransaction::dispatch($row);
        }
    }

    public function replaySalesOrders() {}

    public function replaySalesReturns() {}
}
