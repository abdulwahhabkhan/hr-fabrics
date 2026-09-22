<?php

namespace App\Services;

use App\Models\Accounts\JournalLedger;
use Carbon\CarbonInterface;
use DB;

class JournalService
{
    public function getCashOpeningBalance(CarbonInterface $date): int
    {
        $total = JournalLedger::query()
            ->cashAccountName()
            ->postedBefore($date->startOfDay())
            ->select([
                DB::raw('SUM(dr) as debit'),
                DB::raw('SUM(cr) as credit'),
            ])->first();

        return $total->debit - $total->credit;
    }

    public function getCashClosingBalance(CarbonInterface $date): int
    {
        $total = JournalLedger::query()
            ->cashAccountName()
            ->postedBefore($date->endOfDay())
            ->select([
                DB::raw('SUM(dr) as debit'),
                DB::raw('SUM(cr) as credit'),
            ])->first();

        return $total->debit - $total->credit;
    }

    public function getCashSales(CarbonInterface $date): int
    {
        return JournalLedger::query()
            ->cashAccountName()
            ->salesHead()
            ->whereBetween('posted_at', [$date->startOfDay(), $date->endOfDay()])
            ->sum(DB::raw('dr - cr'));
    }
}
