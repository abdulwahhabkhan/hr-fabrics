<?php

namespace App\Actions\Outbound\SaleReturns;

use App\Actions\Accounts\LedgerEntry;
use App\Enums\JournalHead;
use App\Enums\PaymentMode;
use App\Models\Accounts\Account;
use App\Models\Sales\SalesReturn;
use App\Models\User;
use App\Services\AccountService;
use Exception;

class ProcessSaleReturnLedger
{
    /**
     * @throws Exception
     */
    public function handle(SalesReturn $return, User $user): void
    {
        resolve(LedgerEntry::class)
            ->setTransactionDate($return->transaction_date)
            ->setUser($user)
            ->setHead(JournalHead::Sales)
            ->init($return)
            ->credit($return->customer_id, $return->total_amount)
            ->when($return->payment_mode === PaymentMode::Cash->value, function ($ledger) use ($return) {
                $ledger->debit($return->customer_id, $return->total_amount);
                $ledger->credit(resolve(AccountService::class)->getCashAccount()->id, $return->total_amount);
            })
            ->when($return->agent_id && $return->commission > 0, function ($ledger) use ($return) {
                $ledger->debit($return->agent_id, $return->commission);
                $expenseAccount = Account::find($return->agent_id)->expense_account;
                if ($expenseAccount) {
                    $ledger->credit($expenseAccount, $return->commission);
                }
            })
            ->logAction();
    }
}
