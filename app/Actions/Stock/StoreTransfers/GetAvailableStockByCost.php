<?php

namespace App\Actions\Stock\StoreTransfers;

use App\Models\Stock\Inventory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GetAvailableStockByCost
{
    public function handle(int $productId, string $unit): Collection
    {
        return Inventory::query()
            ->where('product_id', $productId)
            ->where('unit', $unit)
            ->available()
            ->selectRaw('size, COALESCE(cost, 0) as cost')
            ->selectRaw('SUM(qty) as available_qty')
            ->selectRaw('SUM(meters) as available_meters')
            ->groupBy('size', DB::raw('COALESCE(cost, 0)'))
            ->orderBy('size')
            ->orderBy('cost')
            ->get();
    }
}
