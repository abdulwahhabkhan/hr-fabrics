<?php

namespace App\Models\Traits;

use App\Models\Accounts\Account;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToAccount
{
    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function accountSummary(): BelongsTo
    {
        return $this->account()->select(['id', 'name', 'name_urdu', 'address->city as city']);
    }
}
