<?php

namespace App\Policies\Purchase;

use App\Enums\ReturnStatus;
use App\Models\Purchase\PurchaseReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseReturnPolicy
{
    use HandlesAuthorization;

    public function view(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return hasPermission('purchases.por.show', $user);
    }

    public function update(User $user, PurchaseReturn $purchaseReturn): bool
    {
        if (! hasPermission('purchases.por.update')) {
            return false;
        }
        if ($purchaseReturn->status === ReturnStatus::Open) {
            return true;
        }

        return false;
    }

    public function unlock(User $user, PurchaseReturn $purchaseReturn): bool
    {
        if ($purchaseReturn->status === ReturnStatus::Open) {
            return false;
        }

        return $purchaseReturn->status === ReturnStatus::Closed && $purchaseReturn->updated_at->isSameDay(now());
    }

    public function ledger(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $purchaseReturn->status === ReturnStatus::Closed && hasPermission('purchases.por.ledger', $user);

    }

    public function inventory(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $purchaseReturn->status === ReturnStatus::Closed && hasPermission('purchases.por.inventory', $user);
    }
}
