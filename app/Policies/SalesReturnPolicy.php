<?php

namespace App\Policies;

use App\Enums\ReturnStatus;
use App\Models\Sales\SalesReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SalesReturnPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SalesReturn $salesReturn): bool
    {
        return hasPermission('sales.returns.show', $user);
    }

    public function create(User $user): bool
    {
        return hasPermission('sales.returns.create', $user);
    }

    public function update(User $user, SalesReturn $salesReturn): bool
    {
        return $salesReturn->status === ReturnStatus::Open && hasPermission('sales.returns.update', $user);
    }

    public function delete(User $user, SalesReturn $salesReturn): bool
    {
        return hasPermission('sales.returns.destroy', $user);
    }

    public function unlock(User $user, SalesReturn $salesReturn): bool
    {
        if (! $salesReturn->isClosed()) {
            return false;
        }

        return $salesReturn->updated_at->isSameDay(today());
    }

    public function checkInventory(User $user, SalesReturn $salesReturn): Response
    {
        if (! $salesReturn->isClosed()) {
            return Response::deny();
        }

        return $salesReturn->inventories()->booked()->count() === 0 ?
            Response::allow() :
            Response::deny('Return is allocated sales');
    }

    public function ledger(User $user, SalesReturn $salesReturn): bool
    {
        return $salesReturn->status === ReturnStatus::Closed && hasPermission('sales.returns.ledger', $user);
    }

    public function inventory(User $user, SalesReturn $salesReturn): bool
    {
        return $salesReturn->status === ReturnStatus::Closed && hasPermission('sales.returns.inventory', $user);
    }
}
