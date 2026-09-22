<?php

namespace App\Policies;

use App\Models\Stock\StoreTransfer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StoreTransferPolicy
{
    use HandlesAuthorization;

    public function view(User $user, StoreTransfer $storeTransfer): bool
    {
        return hasPermission('stocks.store-transfers.show', $user);
    }

    public function update(User $user, StoreTransfer $storeTransfer): bool
    {
        if (! hasPermission('stocks.store-transfers.update', $user)) {
            return false;
        }

        return $storeTransfer->is_opened;
    }

    public function unlock(User $user, StoreTransfer $storeTransfer): bool
    {
        if (! $storeTransfer->is_closed) {
            return false;
        }

        return $storeTransfer->updated_at->isSameDay(today());
    }

    public function ledger(User $user, StoreTransfer $storeTransfer): bool
    {
        return $storeTransfer->is_closed && hasPermission('stocks.store-transfers.ledger', $user);
    }

    public function inventory(User $user, StoreTransfer $storeTransfer): bool
    {
        return $storeTransfer->is_closed && hasPermission('stocks.store-transfers.inventory', $user);
    }
}
