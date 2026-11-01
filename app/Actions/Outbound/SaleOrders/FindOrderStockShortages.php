<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Actions\Inventory\CheckStockAvailability;
use App\Enums\PackingType;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use Illuminate\Support\Collection;

final class FindOrderStockShortages
{
    public function __construct(private readonly CheckStockAvailability $checkStockAvailability) {}

    /**
     * Describe every product the order cannot be supplied with from the stock at its location. Lines drawing on the
     * same stock are totalled, so two lines that each fit but together exceed it are reported.
     *
     * @return list<string>
     */
    public function handle(Order $order): array
    {
        return $order->items()->with('product')->get()
            ->groupBy(fn (OrderItem $item): string => implode('|', [
                $item->product_id,
                $item->location->value,
                $item->unit->value,
                $item->unit === PackingType::Thaan ? 0 : (float) $item->size,
            ]))
            ->map(fn (Collection $lines): ?string => $this->shortage($lines))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, OrderItem>  $lines
     */
    private function shortage(Collection $lines): ?string
    {
        $item = $lines->first();
        $shortage = $this->checkStockAvailability->shortage(
            $item->product_id,
            $item->unit,
            $item->location,
            (int) $lines->sum('qty'),
            (float) $lines->sum('total_qty'),
            (float) $item->size,
        );

        if ($shortage === null) {
            return null;
        }

        return ($item->product->name ?? "Product {$item->product_id}").": {$shortage}";
    }
}
