<?php

namespace App\Http\Controllers\Reports\Products;

use App\Http\Controllers\Controller;
use App\Models\Accounts\Account;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Product;
use App\Models\Purchase\PurchaseItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductByPurchaseDateController extends Controller
{
    public function __invoke(Request $request)
    {

        $filters = $request->only(['product_name', 'vendor', 'brand', 'unit']);
        $productLastPurchase = PurchaseItem::query()
            ->select('product_id')
            ->selectRaw('MAX(created_at) as last_purchase_date')
            ->groupBy('product_id');

        $products = Product::query()
            ->with('vendor:id,name', 'brand:id,name')
            ->select('*')
            ->joinSub($productLastPurchase, 'plp', function ($join) {
                $join->on('plp.product_id', '=', Product::qCol('id'));
            }, type: 'left')
            ->filterContain('name', $filters['product_name'] ?? null)
            ->filterWhere('vendor_id', $filters['vendor'] ?? null)
            ->filterWhere('brand_id', $filters['brand'] ?? null)
            ->when($filters['unit'] ?? null, function (Builder $query, $val) {
                if ($val === 'Thaan') {
                    $query->where('is_box', false);
                }
                if ($val === 'Box') {
                    $query->where('is_box', true);
                }
            })
            ->orderByDesc('last_purchase_date')
            ->get();

        return Inertia::render('Reports/Products/ProductsByPurchaseDateReport', [
            'products' => $products,
            'brands' => fn () => Brand::query()->select(['id', 'name'])->orderBy('name')->get(),
            'vendors' => fn () => Account::query()->select(['id', 'name'])->suppliers()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }
}
