<?php

namespace App\Http\Controllers\Traits;

use App\Models\Accounts\Account;
use Illuminate\Support\Collection;

trait AccountsToOptions
{
    /**
     * @return Collection<int, Account>
     */
    protected function agentOptions(): Collection
    {
        return Account::agents()->selectAC()->get();
    }

    /**
     * @return Collection<int, Account>
     */
    protected function supplierOptions(): Collection
    {
        return Account::suppliers()->select([
            'id as supplier_id',
            'name as supplier_name',
        ])->orderBy('name')->get();
    }
}
