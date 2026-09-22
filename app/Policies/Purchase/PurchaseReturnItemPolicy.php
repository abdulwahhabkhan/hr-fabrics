<?php

namespace App\Policies\Purchase;

use App\Models\Purchase\PurchaseReturnItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseReturnItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool {}

    public function view(User $user, PurchaseReturnItem $purchaseReturnItem): bool {}

    public function create(User $user): bool {}

    public function update(User $user, PurchaseReturnItem $purchaseReturnItem): bool {}

    public function delete(User $user, PurchaseReturnItem $purchaseReturnItem): bool {}

    public function restore(User $user, PurchaseReturnItem $purchaseReturnItem): bool {}

    public function forceDelete(User $user, PurchaseReturnItem $purchaseReturnItem): bool {}
}
