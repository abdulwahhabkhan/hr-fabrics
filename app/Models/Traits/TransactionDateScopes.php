<?php

namespace App\Models\Traits;

use App\Models\Model;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/** @mixin Model */
trait TransactionDateScopes
{
    #[Scope]
    protected function transactionBetween(Builder $query, CarbonInterface $startDate, CarbonInterface $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    #[Scope]
    protected function transactionBefore(Builder $query, CarbonInterface $date): Builder
    {
        return $query->where('transaction_date', '<=', $date);
    }

    #[Scope]
    protected function transactionAfter(Builder $query, CarbonInterface $date): Builder
    {
        return $query->where('transaction_date', '>=', $date);
    }
}
