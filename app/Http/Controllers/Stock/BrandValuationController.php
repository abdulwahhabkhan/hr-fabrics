<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Brand;
use App\Repositories\InventoryRepository;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandValuationController extends Controller
{
    public function __invoke(Request $request, InventoryService $inventoryService)
    {
        $items = [];
        $total_value = 0;
        if ($filters = $request->only('brand_id', 'type')) {
            if ($brandId = $request->input('brand_id')) {
                $inventory = $inventoryService->inventoryValuationByProduct($brandId);
            } else {
                $inventory = $inventoryService->inventoryValuationByBrand();

            }
            $items = $inventory->get();
            // $items = InventoryRepository::inventoryValuation($filters);
            $total_value = $items->sum('total_value');
        }

        return Inertia::render(
            'Stock/Inventory/InventoryValuationByBrand',
            [
                'items' => $items,
                'total_value' => $total_value,
                'brands' => Brand::all(),
                'filters' => $filters,
            ]
        );
    }
}
