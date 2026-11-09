<?php

namespace App\Actions\Stock\Conversions;

use App\Actions\Inventory\CheckStockAvailability;
use App\Enums\Module;
use App\Enums\PackingType;
use App\Enums\StockLocation;
use App\Models\Catalog\Product;
use App\Models\Stock\Conversion;
use App\Models\Stock\Inventory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateConversion
{
    public function __construct(
        private readonly CheckStockAvailability $checkStockAvailability,
        private readonly CostConversion $costConversion,
    ) {}

    /**
     * Cut thaan stock into suits or thaans of another length. The consumed thaan lines are booked out to the
     * conversion oldest first, and one new lot holding the produced stock is opened at the same location.
     *
     * @param  array{product_id: int|string, location: string, from: array{qty: int|string, size: float|string}, to: array{unit: string, qty: int|string, size: float|string}}  $data
     *
     * @throws Throwable
     */
    public function handle(array $data, User $user): Conversion
    {
        $product = Product::query()->findOrFail((int) $data['product_id']);
        $location = StockLocation::from($data['location']);
        $from = [
            'unit' => PackingType::Thaan->value,
            'size' => (float) $data['from']['size'],
            'qty' => (int) $data['from']['qty'],
        ];
        $to = [
            'unit' => PackingType::from($data['to']['unit'])->value,
            'size' => (float) $data['to']['size'],
            'qty' => (int) $data['to']['qty'],
        ];

        return DB::transaction(function () use ($product, $location, $from, $to, $user) {
            $conversion = Conversion::query()->create([
                'product_id' => $product->id,
                'sku' => $product->name,
                'location' => $location,
                'from' => $from,
                'to' => $to,
                'transaction_date' => today(),
                'created_by' => $user->id,
            ]);

            $shortage = $this->checkStockAvailability->shortage(
                $product->id,
                PackingType::Thaan,
                $location,
                $from['qty'],
                $conversion->fromMeters(),
            );
            if ($shortage !== null) {
                throw ValidationException::withMessages(['from.qty' => $shortage]);
            }

            $this->consume($conversion);
            $this->produce($conversion);
            $this->costConversion->handle($conversion);

            return $conversion;
        });
    }

    /**
     * Book the thaan meters out to the conversion, oldest lots first, splitting the last lot when only part of it is cut.
     *
     * @throws ValidationException
     */
    private function consume(Conversion $conversion): void
    {
        $lots = Inventory::query()
            ->available()
            ->atLocation($conversion->location)
            ->where('product_id', $conversion->product_id)
            ->where('unit', PackingType::Thaan)
            ->fifo()
            ->lockForUpdate()
            ->get();

        $remainingQty = (int) $conversion->from['qty'];
        $remainingMeters = $conversion->fromMeters();

        foreach ($lots as $lot) {
            if ($remainingMeters <= 0) {
                break;
            }

            $meters = min($remainingMeters, round($lot->meters, 2));
            $qty = min($lot->qty, $remainingQty);

            $line = $lot;
            if ($meters < round($lot->meters, 2)) {
                $line = $lot->replicate();
                $line->meters = $meters;

                $lot->qty = max(0, $lot->qty - $qty);
                $lot->meters = round($lot->meters - $meters, 2);
                $lot->save();
            }

            $line->qty = $qty;
            $line->outbound()->associate($conversion);
            $line->outbound_item_id = $conversion->id;
            $line->outbound_on = $conversion->transaction_date;
            $line->save();

            $remainingQty = max(0, $remainingQty - $qty);
            $remainingMeters = round($remainingMeters - $meters, 2);
        }

        if ($remainingMeters > 0) {
            throw ValidationException::withMessages([
                'from.qty' => "Not enough thaan stock at {$conversion->location->label()} to cut {$conversion->fromMeters()} meters.",
            ]);
        }
    }

    /**
     * Open the lot holding the converted stock.
     */
    private function produce(Conversion $conversion): void
    {
        $lot = new Inventory();
        $lot->stockable_item_id = $conversion->id;
        $lot->product_id = $conversion->product_id;
        $lot->unit = PackingType::from($conversion->to['unit']);
        $lot->size = (float) $conversion->to['size'];
        $lot->qty = (int) $conversion->to['qty'];
        $lot->meters = $conversion->toMeters();
        $lot->location = $conversion->location;
        $lot->transaction_date = $conversion->transaction_date;
        $lot->info = [
            'module' => Module::Conversion->value,
            'type' => Module::Conversion->value,
        ];

        $conversion->producedInventories()->save($lot);
    }
}
