<?php

namespace App\Repositories;

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
use DB;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/** @deprecated */
class InventoryRepository
{
    public static function inventoryValuation(array $filters): Collection
    {
        return self::getFreshInventoryValuationByBrand($filters);
    }

    public static function getFreshInventoryValuationByBrand(array $filters): Collection
    {
        $product_cost = PurchaseItem::query()
            ->select([
                'product_id',
                'unit',
            ])
            ->selectRaw('MAX(price) as last_price')
            ->groupBy('product_id', 'unit');

        $query = Inventory::query();
        $query->joinSub(
            $product_cost,
            'product_cost',
            function (JoinClause $join) {
                $join->on('product_cost.product_id', '=', Inventory::qCol('product_id'));
                $join->on('product_cost.unit', '=', Inventory::qCol('unit'));
            }
        );
        $query->join(Product::tName(), function (JoinClause $join) use ($filters) {
            $join->on(Inventory::qCol('product_id'), '=', Product::qCol('id'));
            if ($filters['brand_id']) {
                $join->where('brand_id', $filters['brand_id']);
            }

            return $join;
        });
        $query->select([
            ! $filters['brand_id'] ?
                Brand::qCol('name')
                : Product::qCol('name'),

            DB::raw('SUM(meters) as total_meters'),
            DB::raw('MAX(last_price) as last_price'),
            DB::raw('SUM(CASE WHEN product_cost.unit = "'.PackingType::Box->value.
                '" THEN  qty * last_price ELSE meters * last_price END) as product_value'
            ),
        ]);
        if (! $filters['brand_id']) {
            $query->selectRaw("'' as finish");
            $query->join(Brand::tName(), fn (JoinClause $join) => $join->on(Product::qCol('brand_id'), '=', Brand::qCol('id')));
            $query->groupBy('brand_id', 'finish');
            $query->groupBy(Brand::qCol('name'));
        } else {
            $query->selectRaw('finish');
            $query->groupBy(Inventory::qCol('product_id'));
            $query->groupBy(Product::qCol('name'), 'finish');
        }
        $query->orderBy('name');

        return $query->get();
    }

    /** @deprecated */
    public static function getProductHistory(int $product_id): Collection
    {
        $sales = Inventory::query()
            ->select([
                'type',
                'ref_no',
                'unit',
                'size',
                'qty',
                'info',
                Inventory::qCol('created_at'),
                'meters',
                'customer_id as account_id',
                'product_id',
            ])
            ->join(Order::tName(), fn (JoinClause $join) => $join->on('ref_no', '=', Order::qCol('id')))
            ->where('type', Module::SaleOrder->value)
            ->where('product_id', $product_id);
        $salesReturns = Inventory::query()
            ->select([
                Inventory::qCol('type'),
                SalesReturn::qCol('invoice_no'),
                Inventory::qCol('unit'),
                Inventory::qCol('size'),
                Inventory::qCol('qty'),
                Inventory::qCol('info'),
                Inventory::qCol('created_at as created_at'),
                Inventory::qCol('meters'),
                'customer_id as account_id',
                'product_id',
            ])
            ->join(SalesReturn::tName(), fn (JoinClause $join) => $join->on(Inventory::qCol('ref_no'), '=',
                SalesReturn::qCol('invoice_no')))
            ->where('type', Module::SaleOrderReturn->value)
            ->where('product_id', $product_id);

        $purchases = Inventory::query()
            ->select([
                Inventory::qCol('type'),
                FabricReceiving::qCol('invoice_no as ref_no'),
                Inventory::qCol('unit'),
                Inventory::qCol('size'),
                Inventory::qCol('qty'),
                Inventory::qCol('info'),
                Inventory::qCol('created_at as created_at'),
                Inventory::qCol('meters'),
                'supplier_id as account_id',
                'product_id',
            ])
            ->join(FabricReceiving::tName(), fn (JoinClause $join) => $join->on(
                Inventory::qCol('ref_no'),
                '=',
                FabricReceiving::qCol('id')
            ))
            ->where('type', Module::Stock->value)
            ->where('product_id', $product_id);

        $purchaseReturns = Inventory::query()
            ->select([
                Inventory::qCol('type'),
                PurchaseReturn::qCol('ref_no'),
                Inventory::qCol('unit'),
                Inventory::qCol('size'),
                Inventory::qCol('qty'),
                Inventory::qCol('info'),
                Inventory::qCol('created_at as created_at'),
                Inventory::qCol('meters'),
                'supplier_id as account_id',
                'product_id',
            ])
            ->join(PurchaseReturn::tName(), fn (JoinClause $join) => $join->on(
                Inventory::qCol('ref_no'),
                '=',
                PurchaseReturn::qCol('ref_no')
            ))
            ->where('type', Module::PurchaseReturn->value)
            ->where('product_id', $product_id);

        return $sales
            ->unionAll($salesReturns)
            ->unionAll($purchases)
            ->unionAll($purchaseReturns)
            ->orderBy('created_at')
            ->with(['product', 'account'])
            ->get();
        /*return Inventory::query()
            ->with(['product'])
            ->where('product_id', $product_id)
            ->orderBy('created_at')
            ->get();*/
    }

    public function availableInventory($product_id, $unit, $size): ?Inventory
    {
        $query = Inventory::query()
            ->available()
            ->where('product_id', $product_id)
            ->where('unit', $unit)
            ->when($unit !== PackingType::Thaan->name, function ($query) use ($size) {
                $query->where('size', $size);
            })
            ->selectRaw('SUM(qty) total_qty, SUM(meters) as total_meters');

        return $query->first();
    }

    public function getPurchaseDetails($start_date, $end_date): Collection
    {
        return Purchase::query()
            ->select(['ri.product_id', 'ri.unit', 'p.name as product_name'])
            ->selectRaw('sum(ri.qty) as qty')
            ->selectRaw('sum(ri.total_qty) as total_qty')
            ->selectRaw('max(ri.price) as max_price')
            ->confirmed()
            ->whereBetween(Purchase::qCol('created_at'), [$start_date, $end_date])
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

    public function getSalesDetails($start_date, $end_date): Collection
    {
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

    public function getInventoryFullDetail($start_date, $end_date): Collection
    {
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

    public function getOpeningInventoryByDate($datetime, $details = false): Collection|int
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

    public function getPurchasePrice($date): Collection
    {
        $product_cost = PurchaseItem::query()
            ->from(PurchaseItem::tName().' as ri')
            ->select(['product_id', 'unit'])
            ->selectRaw('MAX(price) as last_price')
            ->groupBy(['product_id', 'unit']);
        $product_cost->where('created_at', '<=', $date);

        return $product_cost->get();
    }
}
