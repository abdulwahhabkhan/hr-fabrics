<?php

namespace App\Actions\Accounts\Balance;

use App\Enums\AccountType;
use App\Models\Accounts\JournalLedger;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final readonly class BankBook
{
    private string $type;

    public function __construct()
    {
        $this->type = AccountType::Bank->value;
    }

    public function openingBalance(CarbonInterface $date): float
    {
        /** @var JournalLedger $data */
        $data = $this->baseSQL()->clone()
            ->where('posted_at', '<', $date->toDateString())
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }

    public function closingBalance(CarbonInterface $date): float
    {
        /** @var JournalLedger $data */
        $data = $this->baseSQL()->clone()
            ->where('posted_at', '<=', $date->toDateString())
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }

    private function baseSQL(): Builder
    {
        return JournalLedger::query()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->where('type', $this->type);
    }
}
