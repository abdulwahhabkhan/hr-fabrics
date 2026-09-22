<?php

namespace App\Actions\Accounts\Period;

use App\Models\Accounts\JournalLedger;
use Illuminate\Support\Carbon;

final readonly class ExpenseBook
{
    public function __construct(
        private ?Carbon $startDate = null,
        private ?Carbon $endDate = null
    ) {}

    public function totalAdvances(): float
    {
        $data = JournalLedger::query()
            ->selectRaw('sum(dr) as total_dr')
            ->typeAdvances()
            ->postedBetween($this->startDate?->toDateString(), $this->endDate?->toDateString())
            ->first();

        return (float) $data->total_dr;
    }

    public function totalDrawings(): float
    {
        $data = JournalLedger::query()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->typeDrawings()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()])
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }

    public function totalCharity(): float
    {
        $data = JournalLedger::query()
            ->selectRaw('sum(dr) as total_dr')
            ->typeCharity()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()])
            ->first();

        return (float) $data->total_dr;
    }

    public function totalExpenses(): float
    {
        $data = JournalLedger::query()
            ->typeExpense()
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }

    public function totalSupplierPayments(): float
    {
        $data = JournalLedger::query()
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->headJournal()
            ->typeSupplier()
            ->whereBetween('posted_at', [$this->startDate?->toDateString(), $this->endDate?->toDateString()])
            ->first();

        return (float) $data->total_dr - (float) $data->total_cr;
    }
}
