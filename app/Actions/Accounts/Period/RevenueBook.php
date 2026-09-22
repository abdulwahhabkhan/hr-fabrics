<?php

namespace App\Actions\Accounts\Period;

use App\Models\Accounts\JournalLedger;
use Illuminate\Support\Carbon;

final readonly class RevenueBook
{
    public function __construct(
        private ?Carbon $startDate = null,
        private ?Carbon $endDate = null
    ) {}

    public function totalCashSales(): float
    {
        $data = JournalLedger::query()
            ->cashAccount()
            ->salesHead()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()])
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }

    public function totalCustomerReceipts(): float
    {
        $data = JournalLedger::query()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->headJournal()
            ->typeCustomer()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()])
            ->first();

        return (float) $data->total_cr - (float) $data->total_dr;
    }

    public function totalPayableReceived(): float
    {
        return 0;
        $data = JournalLedger::query()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->headJournal()
            ->typeSupplier()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()])
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }
}
