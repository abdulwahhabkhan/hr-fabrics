<?php

namespace App\Actions\Inventory;

use App\Enums\PackingType;
use App\Enums\StockLocation;
use App\Models\Stock\Inventory;
use Illuminate\Validation\ValidationException;

class CheckStockAvailability
{
    /**
     * Stock on hand for a product at a location. Box and Suit stock is matched on its packing size,
     * Thaan stock is pooled across sizes because it is issued by meters.
     *
     * @return array{qty: int, meters: float}
     */
    public function available(int $productId, PackingType $unit, StockLocation $location, float $size = 0): array
    {
        $row = Inventory::query()
            ->available()
            ->atLocation($location)
            ->where('product_id', $productId)
            ->where('unit', $unit)
            ->when($unit !== PackingType::Thaan, fn ($query) => $query->where('size', $size))
            ->selectRaw('COALESCE(SUM(qty), 0) as total_qty, COALESCE(SUM(meters), 0) as total_meters')
            ->toBase()
            ->first();

        return [
            'qty' => (int) ($row->total_qty ?? 0),
            'meters' => round((float) ($row->total_meters ?? 0), 2),
        ];
    }

    /**
     * Ensure the requested quantity and meters are covered by the stock at the location.
     *
     * @throws ValidationException
     */
    public function handle(int $productId, PackingType $unit, StockLocation $location, int $qty, float $meters, float $size = 0): void
    {
        $available = $this->available($productId, $unit, $location, $size);
        $sizeLabel = $unit !== PackingType::Thaan ? " of size {$size}" : '';

        if ($unit !== PackingType::Thaan && $qty > $available['qty']) {
            throw ValidationException::withMessages([
                'qty' => "Only {$available['qty']} {$unit->value}{$sizeLabel} available at {$location->label()}.",
            ]);
        }

        if (round($meters, 2) > $available['meters']) {
            throw ValidationException::withMessages([
                'qty' => "Only {$available['meters']} meters{$sizeLabel} available at {$location->label()}.",
            ]);
        }
    }
}
