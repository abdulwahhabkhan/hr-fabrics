<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Actions\Accounts\LedgerEntry;
use App\Enums\JournalHead;
use App\Enums\OrderPaid;
use App\Models\Accounts\Account;
use App\Models\Sales\Order;
use App\Models\User;
use App\Services\AccountService;
use Exception;

class ProcessSaleLedger
{
    /**
     * @throws Exception
     */
    public function handle(Order $order, User $user): void
    {
        $cashAccount = resolve(AccountService::class)->getCashAccount();
        resolve(LedgerEntry::class)
            ->setTransactionDate($order->transaction_date)
            ->setUser($user)
            ->setHead(JournalHead::Sales)
            ->init($order)
            ->debit($order->customer_id, $order->net_total)
            ->when($order->paid === OrderPaid::Paid, function ($ledger) use ($cashAccount, $order) {
                $ledger->credit($order->customer_id, $order->net_total);
                $ledger->debit($cashAccount->id, $order->net_total);
            })
            ->when($order->agent_id && $order->commission > 0, function ($ledger) use ($order) {
                $ledger->credit($order->agent_id, $order->commission);
                $expenseAccount = Account::find($order->agent_id)->expense_account;
                if ($expenseAccount) {
                    $ledger->debit($expenseAccount, $order->commission);
                }
            })
            ->logAction();
    }
}
