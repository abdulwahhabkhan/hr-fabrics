<?php

namespace App\Models\Traits;

use App\Models\Accounts\Account;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToAccount
{
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function accountSummary(): BelongsTo
    {
        return $this->account()->select(['id', 'name', 'name_urdu', 'address->city as city']);
    }
}
