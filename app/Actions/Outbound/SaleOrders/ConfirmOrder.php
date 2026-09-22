<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Actions\Inventory\IssueInventory;
use App\Actions\LogAction\RecordAction;
use App\Exceptions\ActionNotAllowedException;
use App\Exceptions\UnableToAllocateStockException;
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
        $this->issueInventory($order);
    }

    /**
     * @throws UnableToAllocateStockException
     */
    private function issueInventory(Order $order): void
    {
        foreach ($order->items as $item) {
            resolve(IssueInventory::class)
                ->setOutbound($order)
                ->setTransactionDate($order->transaction_date)
                ->setOutboundItemId($item->id)
                ->setProductId($item->product_id)
                ->setSize($item->size)
                ->setQuantity($item->qty)
                ->setUnit($item->unit)
                ->setMeters($item->total_qty)
                ->issue();
        }

    }
}
