<?php

namespace App\Policies\Purchase;

use App\Models\Purchase\PurchaseReturnItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseReturnItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, PurchaseReturnItem $purchaseReturnItem): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PurchaseReturnItem $purchaseReturnItem): bool
    {
        return false;
    }

    public function delete(User $user, PurchaseReturnItem $purchaseReturnItem): bool
    {
        return false;
    }

    public function restore(User $user, PurchaseReturnItem $purchaseReturnItem): bool
    {
        return false;
    }

    public function forceDelete(User $user, PurchaseReturnItem $purchaseReturnItem): bool
    {
        return false;
    }
}
