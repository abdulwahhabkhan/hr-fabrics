<?php

namespace App\Http\Controllers\Reports\Products;

use App\Enums\StockLocation;
use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ProductACResource;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductHistoryController extends Controller
{
    public function __invoke(Request $request, ProductService $productService, InventoryService $inventoryService)
    {
        $items = [];
        $products = $productService->autocompleteProducts()->keyBy('id');
        $total_meters = 0;
        $filters = $request->only('product_id', 'location');
        if ($request->filled('product_id')) {
            $items = $inventoryService->getProductHistory(
                (int) $request->input('product_id'),
                StockLocation::tryFrom((string) $request->input('location'))?->value
            )
                ->with('account:id,name', 'product:id,name,finish')
                ->get();
            $total_meters = $items->sum('meters');
        }

        return Inertia::render(
            'Stock/Inventory/ProductHistory',
            [
                'items' => $items,
                'total_meters' => $total_meters,
                'product' => $request->input('product', []),
                'products' => ProductACResource::collection($products),
                'filters' => $filters,
            ]
        );
    }
}
