<?php

namespace App\Models\Traits;

use App\Models\Accounts\Account;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait BelongsToCustomer
{
    /**
     * @return BelongsTo<Account, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'customer_id')->customers();
    }

    #[Scope]
    protected function withCustomer(Builder $query): Builder
    {
        $query->select(
            $this->qualifyColumn('*'),
            Account::qCol('name as customer_name'),
            Account::qCol('address->city', false).'  as city'
        );

        return $query->join(Account::tName(), 'customer_id', '=',
            Account::qCol('id'));
    }
}
