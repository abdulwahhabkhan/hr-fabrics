<?php

namespace App\Policies\Purchase;

use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class FabricReceivingPolicy
{
    use HandlesAuthorization;

    public function view(User $user, FabricReceiving $stock): bool
    {
        if (hasPermission('purchases.fabric-receivings.show', $user)) {
            return true;
        }

        return false;
    }

    public function edit(User $user, FabricReceiving $stock): bool
    {
        if ($stock->isOpen() && hasPermission('purchases.fabric-receivings.edit', $user)) {
            return true;
        }

        return false;
    }

    public function delete(User $user, FabricReceiving $stock): bool
    {
        if ($stock->isOpen() && hasPermission('purchases.fabric-receivings.destroy')) {
            return true;
        }

        return false;
    }

    public function unlock(User $user, FabricReceiving $stock): bool
    {
        if ($stock->isOpen()) {
            return false;
        }
        if ($user->isAdmin() && $stock->transaction_date?->diffInDays(now(), true) < 7) {
            return true;
        }

        return $stock->transaction_date?->isSameDay(now()) ?? false;
    }

    public function checkInventory(User $user, FabricReceiving $stock): Response
    {
        if ($stock->isOpen()) {
            return Response::deny();
        }

        return $stock->inventories()->booked()->count() === 0 ?
            Response::allow() :
            Response::deny('Stock is allocated either sales or returned');
    }

    public function inventory(User $user, FabricReceiving $stock): bool
    {
        return $stock->isClosed() && $user->can('purchases.fabric-receivings.inventory');
    }

    /**
     * Master correction tools. Kept out of the role permissions on purpose: only the database flag grants it.
     */
    public function masterTools(User $user): bool
    {
        return $user->isMaster();
    }

    /**
     * A master user can reopen a confirmed receiving even when it is invoiced or sold. Stock that went back to the
     * supplier, moved to another location, or was sold and then returned cannot be reallocated, so it is refused.
     */
    public function masterReopen(User $user, FabricReceiving $stock): Response
    {
        if (! $user->isMaster()) {
            return Response::deny('Only master users can reopen receivings');
        }
        if (! $stock->isClosed()) {
            return Response::deny('Receiving is not confirmed');
        }

        $bookedTypes = $stock->inventories()->booked()->distinct()->pluck('outbound_type');
        $purchaseIds = $stock->purchases()->pluck('id');
        $isReturnedToSupplier = $bookedTypes->contains(PurchaseReturn::morphClass())
            || PurchaseReturn::query()->whereIn('purchase_id', $purchaseIds)->exists();
        if ($isReturnedToSupplier) {
            return Response::deny('Stock is returned to the supplier');
        }
        if ($bookedTypes->contains(fn (string $type): bool => $type !== Order::morphClass())) {
            return Response::deny('Stock is transferred or used outside sales');
        }

        $hasSaleReturn = SalesReturn::query()
            ->whereIn('order_no', $stock->saleOrders()->select('invoice_no'))
            ->exists();
        if ($hasSaleReturn) {
            return Response::deny('A sale of this stock is returned');
        }

        return Response::allow();
    }
}
