<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Actions\LogAction\RecordAction;
use App\Exceptions\ActionNotAllowedException;
use App\Models\Sales\Order;
use App\Models\User;
use App\Services\AccountService;
use Throwable;

class ConfirmOrder
{
    /**
     * @throws Throwable
     */
    public function handle(Order $order, User $user): void
    {
        if (! $order->isClosed()) {
            throw ActionNotAllowedException::actionNotAllowed('Confirm Order for Open order '.$order->id);
        }
        $accountService = resolve(AccountService::class);
        resolve(RecordAction::class)->handle($order, $user, 'SO confirmed by user');
        $balance = $accountService->getAccountBalance($order->customer_id);
        $order->update(['balance' => $balance]);
        // SalesTransaction::dispatch($order, $user);
        resolve(ProcessSaleLedger::class)->handle($order, $user);
        resolve(IssueOrderInventory::class)->handle($order);
    }
}
