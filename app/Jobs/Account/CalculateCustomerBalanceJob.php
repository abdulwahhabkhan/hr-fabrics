<?php

namespace App\Jobs\Account;

use App\Models\Accounts\Account;
use App\Models\Accounts\JournalDetail;
use App\Services\AccountService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateCustomerBalanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Account $customer)
    {
        $this->connection = 'database';
    }

    /**
     * Execute the job.
     */
    public function handle(AccountService $service): void
    {
        $account_id = $this->customer->id;
        $total = $service->getAccountTotal($account_id);

        $total_credit = $total->total_credit;
        $balance = $total->total_debit - $total->total_credit;

        if ($balance <= 1) { // if not credit, reset the balance info
            $this->customer->update([
                'balance' => 0,
                'balance_date' => null,
            ]);

            return;
        }

        $history = JournalDetail::overDue($account_id, $total_credit)
            ->with('journal')
            ->first();
        $balance_date = $history?->journal?->posted_at;

        $this->customer->update([
            'balance' => abs($balance),
            'balance_date' => $balance_date,
        ]);
    }
}
