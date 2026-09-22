<?php

namespace App\Http\Controllers\Stock;

use App\Enums\PackingType;
use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Inventory::query();
        $query->join(Product::tName(), 'product_id', '=', Product::qCol('id'));
        $query->available();
        $query->filterContain('name', $request->input('name'));
        $query->filterWhere(Inventory::qCol('unit'), $request->input('unit'));
        $query->filterWhere(Inventory::qCol('size'), $request->input('size'));

        $unitSums = $query->clone()
            ->selectRaw(Inventory::qCol('unit'))
            ->selectRaw('SUM(qty) as qty')
            ->selectRaw('SUM(meters) as meters')
            ->groupByRaw(Inventory::qCol('unit'))
            ->get();
        $query->select([
            Product::qCol('name as sku'),
            Product::qCol('finish'),
            DB::raw(
                '(IF('.Inventory::qCol('unit', false)." = '".PackingType::Thaan->name."',0 , ".
                Inventory::qCol('size', false).')) as size'
            ),
            // (IF(inventories.unit = 'Thaan', 0, inventories.size)) as size
            Inventory::qCol('unit'),
            DB::raw('SUM(qty) as qty'),
            DB::raw('SUM(meters) as meters'),
        ]);
        $query->groupBy(
            Product::qCol('id'),
            Product::qCol('name'),
            Product::qCol('finish'),
            DB::raw('size'),
            Inventory::qCol('unit')
        );
        $query->orderBy('meters');
        $query->having('meters', '!=', 0);
        $items = $query->get();
        $unit_sums = $unitSums;

        return Inertia::render(
            'Stock/Inventory/InventoryIndex',
            [
                'items' => $items,
                'unit_sums' => $unit_sums,
                'filters' => $request->only('name', 'unit', 'size'),
            ]
        );
    }

    public function productStock(Request $request): JsonResponse
    {
        $product = $request->input('product');
        $stock = Inventory::where('sku', '=', $product)->get();

        return response()->json($stock);
    }
}
