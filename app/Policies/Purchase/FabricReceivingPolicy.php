<?php

namespace App\Policies\Purchase;

use App\Models\Purchase\FabricReceiving;
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
}
