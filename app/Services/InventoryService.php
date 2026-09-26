<?php

namespace App\Services;

use App\Enums\Module;
use App\Enums\PackingType;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use App\Models\Stock\Inventory;
use Carbon\CarbonInterface;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;

class InventoryService
{
    public static function getStockStatus(): array|Collection
    {
        return self::salesReturn();
    }

    public static function salesReturn(): array|Collection
    {
        $return_inventory = Inventory::query()->select(['type', 'record_id'])
            ->selectRaw('SUM(meters) as stock_qty')
            ->groupBy(['type', 'ref_no', 'record_id'])->where('type', Module::SaleOrderReturn->value);

        return SalesReturn::query()
            ->select(['ref_no', 'id', 'total_qty', 'stock_qty', 'type'])
            ->joinSub($return_inventory, 'inventory', function ($join) {
                $join->on('inventory.record_id', '=', SalesReturn::qCol('id'));
            })
            ->whereRaw('total_qty != stock_qty')
            ->get();
    }

    public function inventoryValuationByProduct(?int $brandId = null): Builder
    {
        return Inventory::query()
            ->joinRelation('product')
            ->select([
                Product::qCol('name  as product_name'),
                Product::qCol('finish'),
                Inventory::qCol('cost  as cost'),
            ])
            ->selectRaw('SUM(meters) as total_meters')
            ->selectRaw('SUM(meters * inventories.cost) as total_value')
            ->filterWhere('brand_id', $brandId)
            ->available()
            ->groupBy(['product_name', 'finish', 'cost']);
    }

    public function inventoryValuationByBrand(?int $brandId = null): Builder
    {
        return Inventory::query()
            ->joinRelation('product.brand')
            ->select([
                Brand::qCol('name  as brand_name'),
                Inventory::qCol('cost  as cost'),
            ])
            ->selectRaw('SUM(meters) as total_meters')
            ->selectRaw('SUM(meters * inventories.cost) as total_value')
            ->filterWhere('brand_id', $brandId)
            ->available()
            ->groupBy(['brand_name', 'cost']);
    }

    public function getProductHistory(int $productId): Builder
    {
        $inventory = Inventory::query()->where('product_id', $productId);
        $purchases = $inventory->clone()
            ->select([
                FabricReceiving::qCol('invoice_no'),
                Inventory::qCol('unit'),
                Inventory::qCol('size'),
                Inventory::qCol('qty'),
                FabricReceiving::qCol('transaction_date'),
                Inventory::qCol('meters'),
                FabricReceiving::qCol('supplier_id as account_id'),
                Inventory::qCol('product_id'),
            ])
            ->selectRaw("'Purchase' as type")
            ->joinRelation('purchase');
        $sales = $inventory->clone()
            ->select([
                Order::qCol('invoice_no'),
                Inventory::qCol('unit'),
                Inventory::qCol('size'),
                Inventory::qCol('qty'),
                Order::qCol('transaction_date'),
                Inventory::qCol('meters'),
                Order::qCol('customer_id as account_id'),
                Inventory::qCol('product_id'),
            ])
            ->selectRaw("'Sale' as type")
            ->joinRelation('sale');
        $purchaseReturn = $inventory->clone()
            ->select([
                PurchaseReturn::qCol('invoice_no'),
                Inventory::qCol('unit'),
                Inventory::qCol('size'),
                Inventory::qCol('qty'),
                PurchaseReturn::qCol('transaction_date'),
                Inventory::qCol('meters'),
                PurchaseReturn::qCol('supplier_id as account_id'),
                Inventory::qCol('product_id'),
            ])
            ->selectRaw("'PO Return' as type")
            ->joinRelation('purchaseReturn');
        $saleReturns = $inventory->clone()
            ->select([
                SalesReturn::qCol('invoice_no'),
                Inventory::qCol('unit'),
                Inventory::qCol('size'),
                Inventory::qCol('qty'),
                SalesReturn::qCol('transaction_date'),
                Inventory::qCol('meters'),
                SalesReturn::qCol('customer_id as account_id'),
                Inventory::qCol('product_id'),
            ])
            ->selectRaw("'Sale Return' as type")
            ->joinRelation('saleReturn');

        return $purchases
            ->union($sales)
            ->union($purchaseReturn)
            ->union($saleReturns)
            ->orderBy('transaction_date');

    }

    public function getPurchaseDetails(
        CarbonInterface $start_date,
        CarbonInterface $end_date
    ): Collection {
        return Purchase::query()
            ->select(['ri.product_id', 'ri.unit', 'p.name as product_name'])
            ->selectRaw('sum(ri.qty) as qty')
            ->selectRaw('sum(ri.total_qty) as total_qty')
            ->selectRaw('max(ri.price) as max_price')
            ->confirmedBetween($start_date, $end_date)
            ->groupBy('ri.product_id', 'product_name', 'ri.unit')
            ->joinRelationship('items.product', [
                'items' => function ($join) {
                    $join->as('ri');
                },
                'product' => function ($join) {
                    $join->as('p');
                },
            ])
            ->get();

    }

    public function getSalesDetails(
        CarbonInterface $start_date,
        CarbonInterface $end_date
    ): \Illuminate\Support\Collection {
        $product_cost = PurchaseItem::query()
            ->from(PurchaseItem::tName().' as ri')
            ->select(['product_id', 'unit'])
            ->selectRaw('MAX(price) as cost')
            ->groupBy(['product_id', 'unit']);

        return Order::query()
            ->select([
                'oi.product_id', 'oi.price', 'oi.unit as unit',
                'p.name as product_name', 'product_cost.cost',
            ])
            ->confirmedBetween($start_date, $end_date)
            ->selectRaw('sum(oi.qty) as qty_sold')
            ->selectRaw('sum(oi.total_qty) as total_qty_sold')
            ->selectRaw('sum(oi.total_amount) as total_sales')
            ->joinRelationship('items.product', [
                'items' => function ($join) {
                    $join->as('oi');
                },
                'product' => function ($join) {
                    $join->as('p');
                },
            ])
            ->leftJoinSub(
                $product_cost->where('created_at', '<=', $end_date),
                'product_cost',
                function (JoinClause $join) {
                    $join->whereRaw('product_cost.product_id = oi.product_id');
                    $join->whereRaw('product_cost.unit = oi.unit');
                }
            )
            ->groupBy('oi.product_id', 'product_name', 'oi.unit', 'oi.price', 'product_cost.cost')
            ->get()
            ->map(function ($row) {
                if ($row['unit'] === PackingType::Thaan->value or $row['unit'] === PackingType::Suit->value) {
                    $row['sale_qty'] = $row['total_qty_sold'];
                    $cost = round($row['cost'] * $row['total_qty_sold']);
                } else {
                    $row['sale_qty'] = $row['qty_sold'];
                    $cost = round($row['cost'] * $row['qty_sold']);
                }

                $row['total_cost'] = $cost;
                $row['total_profit'] = $row['total_sales'] - $cost;

                return $row;
            });

    }

    public function getInventoryFullDetail(
        CarbonInterface $start_date,
        CarbonInterface $end_date
    ): \Illuminate\Support\Collection {
        $opening_inventory = $this->getOpeningInventoryByDate($start_date, true)
            ->map(function ($item) {
                if ($item->unit === PackingType::Box->value) {
                    $item['inventory_qty'] = $item->total_qty;
                    $item['inventory_amount'] = round($item->total_qty * $item->price, 2);
                } else {
                    $item['inventory_qty'] = $item->total_meters;
                    $item['inventory_amount'] = round($item->total_meters * $item->price, 2);
                }
                $item['opening_price'] = $item->price;

                return $item;
            });

        $closing_inventory = $this->getOpeningInventoryByDate($end_date, true)
            ->map(function ($item) {
                if ($item->unit === PackingType::Box->value) {
                    $item['closing_qty'] = $item->total_qty;
                    $item['closing_amount'] = round($item->total_qty * $item->price, 2);
                } else {
                    $item['closing_qty'] = $item->total_meters;
                    $item['closing_amount'] = round($item->total_meters * $item->price, 2);
                }
                $item['closing_price'] = $item->price;

                return $item;
            })->keyBy(fn ($r) => $r->product_id.'_'.$r->unit);
        foreach ($opening_inventory as $r) {
            $closing = $closing_inventory[$r->product_id.'_'.$r->unit] ?? [];
            if ($closing) {
                $r['closing_qty'] = $closing['closing_qty'];
                $r['closing_price'] = $closing['closing_price'];
                $r['closing_amount'] = $closing['closing_amount'];
            }

        }

        return $opening_inventory->filter(fn ($r
        ) => ($r->price !== $r->closing_price) && ($r->closing_qty > 0 || $r->qty > 0))->values();
    }

    /**
     * @return ($details is true ? Collection<int, Product> : int)
     */
    public function getOpeningInventoryByDate(CarbonInterface $datetime, bool $details = false): Collection|int
    {
        $product_cost = PurchaseItem::query()
            ->from(PurchaseItem::tName().' as ri')
            ->select(['product_id', 'unit'])
            ->selectRaw('MAX(price) as last_price')
            ->groupBy(['product_id', 'unit']);
        if ($details) {

            $inventorySummary = Inventory::query()
                ->select(['product_id', 'unit'])
                ->selectRaw('sum(meters) as total_meters')
                ->selectRaw('sum(qty) as total_qty')
                ->where('created_at', '<=', $datetime)
                ->groupBy(['product_id', 'unit']);

            return Product::query()
                ->select(['product_cost.product_id as product_id', 'name', 'product_cost.unit', 'last_price as price'])
                ->selectRaw('total_qty')
                ->selectRaw('total_meters')
                ->joinSub(
                    $inventorySummary,
                    'inventories',
                    function (JoinClause $join) {
                        $join->whereRaw('products.id = inventories.product_id');
                    }

                )
                ->joinSub(
                    $product_cost->where('created_at', '<=', $datetime),
                    'product_cost',
                    function (JoinClause $join) {
                        $join->whereRaw('product_cost.product_id = inventories.product_id');
                        $join->whereRaw('product_cost.unit = inventories.unit');
                    }
                )
                ->get();
        }

        return (int) Inventory::query()
            ->joinSub(
                $product_cost->where('created_at', '<=', $datetime),
                'product_cost',
                'product_cost.product_id',
                '=',
                Inventory::qCol('product_id')
            )
            ->where('created_at', '<=', $datetime)
            ->sum(DB::raw('CASE WHEN product_cost.unit = "'.PackingType::Box->value.'" THEN  qty * last_price ELSE meters * last_price END'));

    }

    public function availableInventory(int $product_id, PackingType $unit, float $size): ?Inventory
    {
        $query = Inventory::query()
            ->available()
            ->where('product_id', $product_id)
            ->where('unit', $unit)
            ->when($unit !== PackingType::Thaan, function ($query) use ($size) {
                $query->where('size', $size);
            })
            ->selectRaw('SUM(qty) total_qty, SUM(meters) as total_meters');

        return $query->first();
    }
}
