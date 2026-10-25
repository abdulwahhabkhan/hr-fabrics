<?php

namespace App\Http\Controllers\Sales\Order;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;
use Illuminate\Http\JsonResponse;

class ProductStockController extends Controller
{
    /**
     * Available stock of a product grouped by location, packing unit and size, used by the sales item form.
     */
    public function __invoke(Product $product): JsonResponse
    {
        $stock = Inventory::query()
            ->available()
            ->costed()
            ->where('product_id', $product->id)
            ->selectRaw('location, unit, size, SUM(qty) as qty, SUM(meters) as meters')
            ->groupBy('location', 'unit', 'size')
            ->orderBy('location')
            ->orderBy('unit')
            ->orderBy('size')
            ->get()
            ->map(fn (Inventory $row): array => [
                'location' => $row->location->value,
                'location_label' => $row->location->label(),
                'unit' => $row->unit->value,
                'size' => (float) $row->size,
                'qty' => (int) $row->qty,
                'meters' => round((float) $row->meters, 2),
            ]);

        return response()->json(['stock' => $stock]);
    }
}
