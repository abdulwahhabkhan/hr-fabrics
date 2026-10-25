<?php

namespace App\Policies;

use App\Models\Stock\StockTransfer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockTransferPolicy
{
    use HandlesAuthorization;

    public function update(User $user, StockTransfer $stockTransfer): bool
    {
        return $stockTransfer->isOpen() && hasPermission('stocks.stock-transfers.update', $user);
    }

    public function confirm(User $user, StockTransfer $stockTransfer): bool
    {
        return $stockTransfer->isOpen() && hasPermission('stocks.stock-transfers.confirm', $user);
    }

    public function delete(User $user, StockTransfer $stockTransfer): bool
    {
        return $stockTransfer->isOpen() && hasPermission('stocks.stock-transfers.destroy', $user);
    }

    /**
     * A confirmed transfer can be reversed on the day it was confirmed.
     */
    public function unlock(User $user, StockTransfer $stockTransfer): bool
    {
        return $stockTransfer->isClosed() && (bool) $stockTransfer->confirmed_at?->isSameDay(today());
    }

    public function inventory(User $user, StockTransfer $stockTransfer): bool
    {
        return $stockTransfer->isClosed() && hasPermission('stocks.stock-transfers.inventory', $user);
    }
}
