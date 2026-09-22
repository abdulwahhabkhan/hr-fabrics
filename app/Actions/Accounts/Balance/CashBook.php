<?php

namespace App\Actions\Accounts\Balance;

use App\Models\Accounts\JournalLedger;
use App\Services\AccountService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class CashBook
{
    public function __construct() {}

    public function openingBalance(CarbonInterface $date): float
    {
        /** @var JournalLedger $data */
        $data = $this->baseSQL()
            ->where('posted_at', '<', $date->toDateString())
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }

    public function closingBalance(CarbonInterface $date): float
    {
        /** @var JournalLedger $data */
        $data = $this->baseSQL()
            ->where('posted_at', '<=', $date->toDateString())
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }

    private function baseSQL(): Builder
    {
        $cashAccountId = resolve(AccountService::class)->getCashAccount()?->id;

        return JournalLedger::query()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->where('account_id', $cashAccountId);
    }
}
