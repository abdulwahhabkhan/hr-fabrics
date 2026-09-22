<?php

namespace App\Services;

use App\Models\Catalog\Product;
use App\Models\Purchase\PurchaseItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    /**
     * @return Collection<int, Product>
     */
    public function autocompleteProducts(): Collection
    {
        $products = Cache::get(Product::class);
        if (! $products) {
            $products = Product::query()
                ->select(['products.*'])
                ->selectRaw('brands.name as brand_name')
                ->orderBy('products.name', 'ASC')
                ->joinRelation('brand')
                ->get();
            Cache::put(Product::class, $products, 2 * 60 * 60);
        }

        return $products;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getPORProducts(): Collection
    {
        return Product::query()
            ->addSelect([
                'purchased_price' => PurchaseItem::query()
                    ->select('price')
                    ->where('price', '>', 0)
                    ->whereColumn('product_id', 'products.id')
                    ->orderBy('id', 'desc')
                    ->limit(1),

            ])
            ->orderBy('name', 'ASC')->get();
    }

    public function availableProducts()
    {
        $products = Cache::get(Product::$availableCacheKey);
        if (! $products) {
            $products = Product::query()
                ->joinRelation('brand')
                ->available()
                ->select(['products.*'])
                ->selectRaw('brands.name as brand_name')
                ->orderBy('products.name', 'ASC')->get();
            Cache::put(Product::$availableCacheKey, $products, 60);
        }

        return $products;
    }
}
