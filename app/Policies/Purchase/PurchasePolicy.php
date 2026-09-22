<?php

namespace App\Policies\Purchase;

use App\Enums\StatusText;
use App\Models\Purchase\Purchase;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PurchasePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Purchase $receipt): bool
    {
        return hasPermission('purchases.pos.show', $user);
    }

    public function update(User $user, Purchase $receipt): bool
    {
        return $receipt->status === StatusText::Open && hasPermission('purchases.pos.update', $user);
    }

    public function delete(User $user, Purchase $receipt): bool
    {
        return $receipt->status === StatusText::Open && hasPermission('purchases.pos.destroy', $user);
    }

    public function unlock(User $user, Purchase $receipt): bool
    {
        if ($receipt->status === StatusText::Open) {
            return false;
        }

        return $receipt->updated_at->isSameDay(now());
    }

    public function inventory(User $user, Purchase $receipt): bool
    {
        return $receipt->status === StatusText::Close && hasPermission('purchases.pos.inventory', $user);
    }

    public function ledger(User $user, Purchase $receipt): bool
    {
        return $receipt->status === StatusText::Close && hasPermission('purchases.pos.ledger', $user);
    }

    public function checkInventory(User $user, Purchase $receipt): Response
    {
        if ($receipt->status === StatusText::Open) {
            return Response::deny();
        }

        return $receipt->inventories()->booked()->count() === 0 ?
            Response::allow() :
            Response::deny('Stock is allocated either sales or returned');
    }
}
