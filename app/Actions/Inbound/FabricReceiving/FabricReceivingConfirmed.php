<?php

namespace App\Actions\Inbound\FabricReceiving;

use App\Actions\Inbound\Purchase\UpdateStockPrice;
use App\Actions\Outbound\SaleOrders\IssueOrderInventory;
use App\Actions\Outbound\SaleOrders\SyncOrderAllocation;
use App\Enums\Module;
use App\Enums\PackingType;
use App\Enums\TransactionType;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Sales\Order;
use App\Models\Stock\Inventory;
use DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

final class FabricReceivingConfirmed
{
    /**
     * Rebuild the receiving's stock lines. A receiving reopened by a master user can still have stock booked to
     * sale orders; those orders are released and booked again so they pick up the corrected stock, and the cost of
     * any confirmed voucher it is invoiced on is applied to the new lines.
     *
     * @throws Throwable
     */
    public function handle(FabricReceiving $stock): void
    {
        if (! $stock->isClosed()) {
            return;
        }
        DB::transaction(function () use ($stock) {
            $orders = $this->releaseSaleOrders($stock);
            $items = $stock->items()->get();
            $info = [
                'action' => 'add',
                'type' => TransactionType::Stock->value,
                'module' => Module::Stock->value,
                'invoice_no' => $stock->invoice_no,
            ];
            $stock->items()->update(['status' => 1]);
            $stock->inventories()->delete();
            $inventories = [];
            foreach ($items as $item) {
                $inventory = new Inventory();
                $inventory->stockable_item_id = $item->id;
                $inventory->product_id = $item->product_id;
                $inventory->size = $item->size;
                $inventory->unit = PackingType::from($item->unit);
                $inventory->location = $item->location;
                $inventory->qty = $item->qty;
                $inventory->meters = $item->total_qty;
                $inventory->info = $info;
                $inventory->transaction_date = $stock->transaction_date;
                $inventories[] = $inventory;

            }
            $stock->inventories()->saveMany($inventories);

            $updateStockPrice = resolve(UpdateStockPrice::class);
            $stock->purchases()->confirmed()->get()
                ->each(fn (Purchase $purchase) => $updateStockPrice->handle($purchase));

            $issueOrderInventory = resolve(IssueOrderInventory::class);
            foreach ($orders as $order) {
                $issueOrderInventory->handle($order);
            }
        });
    }

    /**
     * Free every line booked by the sale orders that hold this receiving's stock, mark the open ones unallocated,
     * and return the confirmed ones in the order they were sold so they can be booked again first-in first-out.
     *
     * @return Collection<int, Order>
     *
     * @throws ValidationException
     */
    private function releaseSaleOrders(FabricReceiving $stock): Collection
    {
        $bookedOutsideSales = $stock->inventories()
            ->booked()
            ->where('outbound_type', '!=', Order::morphClass())
            ->exists();
        if ($bookedOutsideSales) {
            throw ValidationException::withMessages([
                'status' => 'Stock of this receiving is returned or transferred, it cannot be confirmed again.',
            ]);
        }

        $orders = $stock->saleOrders()->with('items')->orderBy('transaction_date')->orderBy('id')->get();
        Inventory::query()
            ->where('outbound_type', Order::morphClass())
            ->whereIn('outbound_id', $orders->modelKeys())
            ->update([
                'outbound_type' => null,
                'outbound_id' => null,
                'outbound_item_id' => null,
                'outbound_on' => null,
            ]);

        [$closed, $open] = $orders->partition(fn (Order $order): bool => $order->isClosed());
        $syncOrderAllocation = resolve(SyncOrderAllocation::class);
        $open->each(fn (Order $order) => $syncOrderAllocation->handle($order));

        return $closed->values();
    }
}
