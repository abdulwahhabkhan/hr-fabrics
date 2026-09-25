<?php

/** @noinspection PhpPossiblePolymorphicInvocationInspection */

namespace App\Models\Traits;

use App\Models\Accounts\Journal;
use App\Models\Model;
use Carbon\CarbonImmutable;
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
    /**
     * @return MorphOne<Journal, $this>
     */
    public function journal(): MorphOne
    {
        return $this->morphOne(Journal::class, 'resource');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function confirmedBefore(Builder $query, $date): Builder
    {
        $query->confirmed();
        if ($date instanceof CarbonInterface) {
            $date = $date->toDateString();
        }

        return $query->where('transaction_date', '<', $date);

    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function confirmedAfter(Builder $query, CarbonInterface|string $date): Builder
    {
        $query->confirmed();
        if ($date instanceof CarbonInterface) {
            $date = $date->toDateString();
        }

        return $query->where('transaction_date', '>', $date);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function confirmedBetween(
        Builder $query,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate
    ): Builder {
        $query->confirmed();
        if ($startDate instanceof CarbonInterface) {
            $startDate = $startDate->toDateString();
        }
        if ($endDate instanceof CarbonInterface) {
            $endDate = $endDate->toDateString();
        }

        return $query->where(function (Builder $query) use ($endDate, $startDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function confirmedOn(Builder $query, $date): Builder
    {
        $query->confirmed();
        if ($date instanceof CarbonInterface) {
            $date = $date->toDateString();
        }

        return $query->where('transaction_date', '=', $date);
    }

    /**
     * @return Attribute<CarbonImmutable|null, never>
     */
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
