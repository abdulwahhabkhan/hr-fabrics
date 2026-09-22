<?php

/** @noinspection PhpPossiblePolymorphicInvocationInspection */

namespace App\Models\Traits;

use App\Models\Accounts\Journal;
use App\Models\Model;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Model
 */
trait MorphToJournal
{
    public function journal(): MorphOne
    {
        return $this->morphOne(Journal::class, 'resource');
    }

    #[Scope]
    public function confirmedBefore(Builder $query, $date): Builder
    {
        $query->confirmed();
        if ($date instanceof CarbonInterface) {
            $date = $date->toDateString();
        }

        return $query->where('transaction_date', '<', $date);

    }

    #[Scope]
    public function confirmedAfter(Builder $query, $date): Builder
    {
        $query->confirmed();
        if ($date instanceof CarbonInterface) {
            $date = $date->toDateString();
        }

        return $query->where('transaction_date', '>', $date);
    }

    #[Scope]
    public function confirmedBetween(Builder $query, $startDate, $endDate): Builder
    {
        $query->confirmed();
        if ($startDate instanceof CarbonInterface) {
            $startDate = $startDate;
        }
        if ($endDate instanceof CarbonInterface) {
            $endDate = $endDate;
        }

        return $query->where(function (Builder $query) use ($endDate, $startDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        });
    }

    #[Scope]
    public function confirmedOn(Builder $query, $date): Builder
    {
        $query->confirmed();
        if ($date instanceof CarbonInterface) {
            $date = $date->toDateString();
        }

        return $query->where('transaction_date', '=', $date);
    }

    protected function transactionDisplayDate(): Attribute
    {
        return Attribute::get(function () {
            if ($this->transaction_date ?? null) {
                return $this->transaction_date;
            }

            return $this->created_at;
        });
    }
}
