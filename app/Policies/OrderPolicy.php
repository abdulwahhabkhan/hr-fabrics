<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Sales\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Order $order): bool
    {
        return hasPermission('sales.orders.show', $user);
    }

    public function update(User $user, Order $order): bool
    {
        if (! hasPermission('sales.orders.update', $user)) {
            return false;
        }

        return $order->status === OrderStatus::Open;
    }

    public function delete(User $user, Order $order): bool
    {
        return hasPermission('sales.orders.destroy', $user);
    }

    public function bilti(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::Close;
    }

    public function unlock(User $user, Order $order): bool
    {
        if (! $order->isClosed()) {
            return false;
        }

        return $order->updated_at->isSameDay(today());
    }

    public function gatePass(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::Close && hasPermission('sales.orders.gate-pass', $user);
    }

    public function ledger(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::Close && hasPermission('sales.orders.ledger', $user);
    }

    public function inventory(User $user, Order $order): bool
    {
        return $order->status === OrderStatus::Close && hasPermission('sales.orders.inventory', $user);
    }
}
