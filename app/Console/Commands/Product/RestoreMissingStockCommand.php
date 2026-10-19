<?php

namespace App\Console\Commands\Product;

use App\Enums\PackingType;
use App\Models\Purchase\FabricReceivingItem;
use DB;
use Illuminate\Console\Command;
use Throwable;

class RestoreMissingStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:restore-missing-stock {--dry-run : Only list the shortfalls without changing inventory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore inventory deleted from confirmed fabric receiving lines by unlocked sale orders';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(): int
    {
        $items = FabricReceivingItem::query()
            ->whereHas('fabricReceiving', fn ($query) => $query->confirmed())
            ->with(['fabricReceiving', 'product'])
            ->withSum('inventory', 'meters')
            ->withSum('inventory', 'qty')
            ->get();

        $rows = [];
        foreach ($items as $item) {
            $unit = PackingType::from($item->unit);
            $missingMeters = round($item->total_qty - (float) $item->inventory_sum_meters, 2);
            $missingQty = $unit === PackingType::Thaan ? 0 : $item->qty - (int) $item->inventory_sum_qty;
            if ($missingMeters === 0.0 && $missingQty === 0) {
                continue;
            }

            if ($missingMeters < 0 || $missingQty < 0) {
                $this->warn("Line $item->id ({$item->getRelation('product')?->name}) has more inventory than received, skipped.");

                continue;
            }

            $rows[] = [
                $item->id, $item->fabricReceiving->invoice_no, $item->product_id, $item->getRelation('product')?->name,
                $unit->value, $missingQty, $missingMeters,
            ];

            if (! $this->option('dry-run')) {
                DB::transaction(fn () => $this->restore($item, $unit, $missingQty, $missingMeters));
            }
        }

        if ($rows === []) {
            $this->info('No missing stock found.');

            return self::SUCCESS;
        }

        $this->table(['Line', 'Invoice', 'Product ID', 'Product', 'Unit', 'Missing Qty', 'Missing Meters'], $rows);
        $this->info($this->option('dry-run') ? 'Dry run, nothing changed.' : count($rows).' lines restored.');

        return self::SUCCESS;
    }

    /**
     * Add the missing stock to the line's available inventory row, or create one when the line has none.
     */
    private function restore(FabricReceivingItem $item, PackingType $unit, int $missingQty, float $missingMeters): void
    {
        $available = $item->inventory()->available()->first();
        if ($available !== null) {
            $available->meters += $missingMeters;
            $available->qty += $missingQty;
            $available->save();

            return;
        }

        $sibling = $item->inventory()->first();
        $item->inventory()->create([
            'stockable_id' => $item->fabric_receiving_id,
            'product_id' => $item->product_id,
            'unit' => $unit,
            'size' => $item->size,
            'qty' => $unit === PackingType::Thaan && $item->size > 0 ? (int) round($missingMeters / $item->size) : $missingQty,
            'meters' => $missingMeters,
            'cost' => $sibling?->cost,
            'info' => $sibling?->info,
            'transaction_date' => $item->fabricReceiving->transaction_date,
        ]);
    }
}
